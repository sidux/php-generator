<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\PhpHelper;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Part;

class PhpType
{
    use Part\ParentAwareTrait;
    use Helper\Traits\StaticCreateAwareTrait;
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

    /**
     * @var PhpName|string
     */
    private $value;

    private bool $internal = false;

    private bool $collection = false;

    private bool $nullable = false;

    /**
     * @param string|NamespaceAware|PhpName
     */
    public function __construct($type)
    {
        if ($type instanceof NamespaceAware) {
            $type = $type->getQualifiedName();
        }
        if (strpos($type, '?') === 0) {
            $this->nullable = true;
            $type           = str_replace('?', '', $type);
        }
        if (!PhpHelper::isNamespaceIdentifier($type)) {
            throw new \InvalidArgumentException("Value '$type' is not a valid type.");
        }
        if (substr($type, -2, 2) === '[]') {
            $this->collection = true;
        }
        if (in_array($type, self::INTERNAL_TYPES, true)) {
            $this->internal = true;
        } else {
            $type = new PhpName($type);
            $type->setParent($this);
        }

        $this->value = $type;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public static function fromReflectionType(ReflectionType $ref): self
    {
        $name = (string)$ref;
        if (!$ref->isBuiltin() && strpos($name, '\\') === false) {
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
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_int($value)) {
            return self::INT;
        }

        if (is_float($value)) {
            return self::FLOAT;
        }

        if (is_string($value)) {
            return self::STRING;
        }

        if (is_bool($value)) {
            return self::BOOL;
        }

        if (is_array($value)) {
            return self::ARRAY;
        }

        return null;
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
        return !$this->isCollection() && !in_array($this->value, [self::NULL, self::PARENT], true);
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
    
    public function getQualifiedName()
    {
        if ($this->value instanceof PhpName) {
            return $this->value->getQualifiedName();
        }
        
        return $this->value;
    }
}
