<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\Element;

class TraitUse extends QualifiedName implements Element
{
    private array $resolutions = [];

    public function __toString(): string
    {
        $output = '';
        $output .= 'use ' . parent::__toString();
        $output .= ($this->resolutions ? " {\n    " . implode(";\n   ", $this->resolutions) . ";\n}\n" : ";\n");

        return $output;
    }

    public function getResolutions(): array
    {
        return $this->resolutions;
    }

    public function setResolutions(array $resolutions): void
    {
        $this->resolutions = $resolutions;
    }
}
