<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

/**
 * @internal
 */
trait ReferenceAwareTrait
{
    private bool $reference = false;

    public function isReference(): bool
    {
        return $this->reference;
    }

    public function setReference(bool $reference = true): self
    {
        $this->reference = $reference;

        return $this;
    }
}
