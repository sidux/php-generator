<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

interface ValueAware
{
    public function removeValue();

    public function getValue();

    public function isInitialized(): bool;

    public function setInitialized(bool $initialized = true);

    public function setValue($value);
}
