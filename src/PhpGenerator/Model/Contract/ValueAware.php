<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use Sidux\PhpGenerator\Model\Value;

interface ValueAware
{
    public function setValue($value, bool $literal = false);

    public function removeValue();

    public function getValue(): ?Value;

    public function isInitialized(): bool;

    public function setInitialized(bool $initialized = true);
}
