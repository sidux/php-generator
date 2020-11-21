<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use Sidux\PhpGenerator\Model\Struct;

abstract class Member
{
    protected ?Struct $parent = null;

    public function getParent(): ?Struct
    {
        return $this->parent;
    }

    public function setParent(?Struct $parent): void
    {
        $this->parent = $parent;
    }
}
