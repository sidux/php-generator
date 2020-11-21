<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use phpDocumentor\Reflection\Type as DocType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Type;

/**
 * @internal
 */
trait TypeAwareTrait
{
    /**
     * @var array<string, Type>
     */
    private array $types = [];

    public function removeType(string $type): self
    {
        if (isset($this->types[$type])) {
            unset($this->types[$type]);
        }

        return $this;
    }

    /**
     * @return array<string, Type>
     */
    public function getDocTypes(): array
    {
        return $this->getTypes();
    }

    public function getTypeHint(): ?string
    {
        $types  = $this->getTypes();
        $prefix = $this->isNullable() ? '?' : '';
        if (isset($types[Type::NULL])) {
            unset($types[Type::NULL]);
        }

        if (1 === \count($types)) {
            $type = reset($types);
            if (!$type->isCollection() && $type->isHintable()) {
                return $prefix . $type;
            }
        }

        if (isset($types[Type::ITERABLE])) {
            unset($types[Type::ITERABLE]);
            foreach ($types as $index => $type) {
                if (!$type->isCollection()) {
                    return null;
                }
            }

            return $prefix . $this->types[Type::ITERABLE];
        }

        return null;
    }

    /**
     * @return array<string, Type>|\ArrayAccess
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array<string|int, null|string|NamespaceAware|Type|ReflectionType|Type> $types
     */
    public function setTypes(array $types): self
    {
        $this->types = [];

        return $this->addTypes($types);
    }

    public function isNullable(): bool
    {
        foreach ($this->getTypes() as $type) {
            if ((string)$type === Type::NULL) {
                return true;
            }
        }

        return false;
    }

    public function addTypeFromNamespaceAware(NamespaceAware $stuct): self
    {
        $type = $stuct->getQualifiedName();

        return $this->addTypeFromString($type);
    }

    public function addTypeFromPhpType(Type $type): self
    {
        if ($type->isCollection()) {
            $this->addType(Type::ITERABLE);
        }
        if ($type->isNullable()) {
            $this->addType(Type::NULL);
        }
        if ($this instanceof TypeAware) {
            $type->setParent($this);
        }
        $this->types[(string)$type] = $type;
        $this->resolve();

        return $this;
    }

    public function addTypeFromReflectionType(ReflectionType $ref): self
    {
        $type = Type::fromReflectionType($ref);

        return $this->addTypeFromPhpType($type);
    }

    public function addTypeFromString(string $type): self
    {
        if (!$type) {
            return $this;
        }
        $phpType = new Type($type);

        return $this->addTypeFromPhpType($phpType);
    }

    public function addTypeFromType(DocType $type): self
    {
        return $this->addTypeFromString((string)$type);
    }

    public function addTypeFromNull(): self
    {
        return $this;
    }

    /**
     * @param array<string|int, null|string|NamespaceAware|Type|ReflectionType|DocType> $types
     */
    public function addTypes(array $types): self
    {
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }

    /**
     * @param null|string|NamespaceAware|Type|ReflectionType|DocType $type
     */
    public function addType($type): self
    {
        if (null === $type) {
            return $this;
        }

        if (\is_string($type)) {
            return $this->addTypeFromString($type);
        }

        if ($type instanceof ReflectionType) {
            return $this->addTypeFromReflectionType($type);
        }

        if ($type instanceof Type) {
            return $this->addTypeFromPhpType($type);
        }

        if ($type instanceof DocType) {
            return $this->addTypeFromType($type);
        }

        if ($type instanceof NamespaceAware) {
            return $this->addTypeFromNamespaceAware($type);
        }

        /* @phpstan-ignore-next-line */
        throw new \RuntimeException('Unsupported type ' . \gettype($type));
    }
}
