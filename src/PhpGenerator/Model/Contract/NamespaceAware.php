<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

interface NamespaceAware
{
    public function getName(): string;

    public function getNamespace(): string;

    public function getQualifiedName(): string;
}
