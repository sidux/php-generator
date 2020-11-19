<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\PhpType;

/**
 * @internal
 */
trait TypeAwareTrait
{
    /**
     * @var array<string, PhpType>
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
     * @return array<string, PhpType>
     */
    public function getDocTypes(): array
    {
        return $this->getTypes();
    }

    public function getTypeHint(): ?string
    {
        $types  = $this->getTypes();
        $prefix = $this->isNullable() ? '?' : '';
        if (isset($types[PhpType::NULL])) {
            unset($types[PhpType::NULL]);
        }

        if (1 === \count($types)) {
            $type = reset($types);
            if (!$type->isCollection() && $type->isHintable()) {
                return $prefix . $type;
            }
        }

        if (isset($types[PhpType::ITERABLE])) {
            unset($types[PhpType::ITERABLE]);
            foreach ($types as $index => $type) {
                if (!$type->isCollection()) {
                    return null;
                }
            }

            return $prefix . $this->types[PhpType::ITERABLE];
        }

        return null;
    }

    /**
     * @return array<string, PhpType>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array<string|int, null|string|NamespaceAware|PhpType|ReflectionType|Type> $types
     */
    public function setTypes(array $types): self
    {
        $this->types = [];

        return $this->addTypes($types);
    }

    public function isNullable(): bool
    {
        foreach ($this->getTypes() as $type) {
            if ((string)$type === PhpType::NULL) {
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

    public function addTypeFromPhpType(PhpType $type): self
    {
        if ($type->isCollection()) {
            $this->addType(PhpType::ITERABLE);
        }
        if ($type->isNullable()) {
            $this->addType(PhpType::NULL);
        }
        if ($this instanceof TypeAware) {
            $type->setParent($this);
        }
        $struct = $type->getStructParent();
        if ($struct && $type->isResolved()) {
            $struct->addNamespaceUse($type);
        }
        $this->types[(string)$type] = $type;

        return $this;
    }

    public function addTypeFromReflectionType(ReflectionType $ref): self
    {
        $type = PhpType::fromReflectionType($ref);

        return $this->addTypeFromPhpType($type);
    }

    public function addTypeFromString(string $type): self
    {
        if (!$type) {
            return $this;
        }
        $phpType = new PhpType($type);

        return $this->addTypeFromPhpType($phpType);
    }

    public function addTypeFromType(Type $type): self
    {
        return $this->addTypeFromString((string)$type);
    }

    public function addTypeFromNull(): self
    {
        return $this;
    }

    /**
     * @param array<string|int, null|string|NamespaceAware|PhpType|ReflectionType|Type> $types
     */
    public function addTypes(array $types): self
    {
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }

    /**
     * @param null|string|NamespaceAware|PhpType|ReflectionType|Type $type
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

        if ($type instanceof PhpType) {
            return $this->addTypeFromPhpType($type);
        }

        if ($type instanceof Type) {
            return $this->addTypeFromType($type);
        }

        if ($type instanceof NamespaceAware) {
            return $this->addTypeFromNamespaceAware($type);
        }

        /* @phpstan-ignore-next-line */
        throw new \RuntimeException('Unsupported type ' . \gettype($type));
    }
}
