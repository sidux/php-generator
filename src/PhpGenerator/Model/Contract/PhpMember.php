<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use Sidux\PhpGenerator\Model\PhpStruct;

abstract class PhpMember
{
    protected ?PhpStruct $parent = null;

    public function getParent(): ?PhpStruct
    {
        return $this->parent;
    }

    public function setParent(?PhpStruct $parent): void
    {
        $this->parent = $parent;
    }
}
