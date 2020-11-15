<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\Reflection\ReflectionType;
use Sidux\PhpGenerator\Model\PhpType;

interface TypeAware
{
    public function removeType(string $type);

    /**
     * @return array<string, PhpType>
     */
    public function getDocTypes(): array;

    public function getTypeHint(): ?string;

    /**
     * @return array<string, PhpType>
     */
    public function getTypes(): array;

    /**
     * @param array<string|int, null|string|NamespaceAware|PhpType|ReflectionType|Type> $types
     */
    public function setTypes(array $types);

    public function isNullable(): bool;

    /**
     * @param array<string|int, null|string|NamespaceAware|PhpType|ReflectionType|Type> $types
     */
    public function addTypes(array $types);

    /**
     * @param null|string|NamespaceAware|PhpType|ReflectionType|Type $type
     */
    public function addType($type);
}
