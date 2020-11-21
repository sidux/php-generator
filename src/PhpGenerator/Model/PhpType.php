<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\PhpHelper;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Contract\PhpElement;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Part;

class PhpType implements PhpElement, NamespaceAware
{
    use Part\NamespaceAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    public const INTERNAL_TYPES = [
        'string',
        'bool',
        'int',
        'float',
        'array',
        'iterable',
        'callable',
        'object',
        'self',
        'void',
        'parent',
        'null',
    ];

    public const
        STRING = 'string',
        INT = 'int',
        FLOAT = 'float',
        BOOL = 'bool',
        ARRAY = 'array',
        OBJECT = 'object',
        CALLABLE = 'callable',
        ITERABLE = 'iterable',
        VOID = 'void',
        NULL = 'null',
        SELF = 'self',
        PARENT = 'parent';

    private bool $internal = false;

    private bool $collection = false;

    private bool $nullable = false;

    private ?TypeAware $parent = null;

    /**
     * @param string|NamespaceAware $type
     */
    public function __construct($type)
    {
        if ($type instanceof NamespaceAware) {
            $type = $type->getQualifiedName();
        }

        $type = (string)$type;
        if (0 === strncmp($type, '?', 1)) {
            $this->nullable = true;
            $type           = str_replace('?', '', $type);
        }
        if (!PhpHelper::isNamespaceIdentifier($type)) {
            throw new \InvalidArgumentException("Value '$type' is not a valid type.");
        }
        if ('[]' === substr($type, -2, 2)) {
            $this->collection = true;
            $type             = str_replace('[]', '', $type);
        }
        if (\in_array($type, self::INTERNAL_TYPES, true)) {
            $this->internal = true;
        }
        $this->setQualifiedName($type);
    }

    public function __toString(): string
    {
        $qualifiedName = $this->getQualifiedName();
        if ($this->getStructParent() && !$this->isInternal()) {
            $qualifiedName = '\\' . $qualifiedName;
        }

        return $this->isResolved() ? $this->name : $qualifiedName;
    }

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public static function fromReflectionType(ReflectionType $ref): self
    {
        $name = (string)$ref;
        if (!$ref->isBuiltin() && false === strpos($name, '\\')) {
            $name = '\\' . $name;
        }
        $type           = self::create($name);
        $type->internal = $ref->isBuiltin();
        $type->nullable = $ref->allowsNull();

        return $type;
    }

    public static function fromString(string $qualifiedName): self
    {
        return self::create($qualifiedName);
    }

    public static function getType($value): ?string
    {
        if (\is_object($value)) {
            return \get_class($value);
        }

        if (\is_int($value)) {
            return self::INT;
        }

        if (\is_float($value)) {
            return self::FLOAT;
        }

        if (\is_string($value)) {
            return self::STRING;
        }

        if (\is_bool($value)) {
            return self::BOOL;
        }

        if (\is_array($value)) {
            return self::ARRAY;
        }

        return null;
    }

    public function getStructParent(): ?PhpStruct
    {
        $parent = $this->getParent();
        while ($parent
            && method_exists($parent, 'getParent')) {
            $parent = $parent->getParent();
        }

        if (!$parent) {
            return null;
        }

        if (!$parent instanceof PhpStruct) {
            return null;
        }

        return $parent;
    }

    public function isResolved(): bool
    {
        $parent = $this->getStructParent();
        if (!$parent) {
            return false;
        }

        if (substr_count($this->getQualifiedName(), '\\') < 2) {
            return false;
        }

        if ($parent->getNamespace() === $this->getNamespace()) {
            return true;
        }

        return $parent->hasResolveTypes();
    }

    public function getParent(): ?TypeAware
    {
        return $this->parent;
    }

    public function setParent(?TypeAware $parent): void
    {
        $this->parent = $parent;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isHintable(): bool
    {
        return !$this->isCollection() && !\in_array($this->name, [self::NULL, self::PARENT], true);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getName(): string
    {
        return $this->__toString();
    }

    public function resolve(): self
    {
        $struct = $this->getStructParent();
        if ($struct && $this->isResolved() && $struct->getNamespace() !== $this->getNamespace()) {
            $struct->addNamespaceUse($this);
        }

        return $this;
    }
}
