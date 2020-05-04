<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

/**
 * @internal
 */
trait AbstractAwareTrait
{
    private bool $abstract = false;

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function setAbstract(bool $abstract = true): self
    {
        $this->abstract = $abstract;

        return $this;
    }
}
