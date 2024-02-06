<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

trait ReadOnlyAwareTrait
{
    private bool $readOnly = false;

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }
}
