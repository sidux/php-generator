<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use Sidux\PhpGenerator\Model\PhpStruct;

interface PhpMember
{
    public function getParent(): ?PhpStruct;

    public function setParent(?PhpStruct $parent);
}
