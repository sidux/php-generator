<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

interface PhpElement
{
    public function __toString();

    public function getName(): string;
}
