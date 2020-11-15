<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

/**
 * @internal
 */
trait StaticAwareTrait
{
    private bool $static = false;

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function setStatic(bool $static = true): self
    {
        $this->static = $static;

        return $this;
    }
}
