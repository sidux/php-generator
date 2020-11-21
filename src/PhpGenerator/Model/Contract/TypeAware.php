<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use phpDocumentor\Reflection\Type as DocType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Model\Type;

interface TypeAware
{
    public function removeType(string $type);

    /**
     * @return array<string, Type>
     */
    public function getDocTypes(): array;

    public function getTypeHint(): ?string;

    /**
     * @return array<string, Type>
     */
    public function getTypes(): array;

    /**
     * @param array<string|int, null|string|NamespaceAware|Type|ReflectionType|DocType> $types
     */
    public function setTypes(array $types);

    public function isNullable(): bool;

    /**
     * @param array<string|int, null|string|NamespaceAware|Type|ReflectionType|DocType> $types
     */
    public function addTypes(array $types);

    /**
     * @param null|string|NamespaceAware|Type|ReflectionType|DocType $type
     */
    public function addType($type);
}
