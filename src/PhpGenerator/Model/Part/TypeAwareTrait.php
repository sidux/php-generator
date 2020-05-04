<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\PhpStruct;
use Sidux\PhpGenerator\Model\PhpType;

/**
 * @internal
 * @method self addType(NamespaceAware|PhpType|string $type)
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

        if (count($types) === 1) {
            $type = reset($types);
            if ($type && !$type->isCollection() && $type->isHintable()) {
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
     * @param array<string, PhpType|string|PhpStruct> $types
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

    public function addTypeFromNamespaceAware(PhpStruct $stuct): self
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
        $type->setParent($this);
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
        $phpType = new PhpType($type);

        return $this->addTypeFromPhpType($phpType);
    }

    public function addTypeFromType(Type $type): self
    {
        return $this->addTypeFromString((string)$type);
    }

    /**
     * @param null $type
     */
    public function addTypeFromNull($type): self
    {
        return $this;
    }

    /**
     * @param array<string, PhpType|PhpStruct|string> $types
     */
    public function addTypes(array $types): self
    {
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }
}
