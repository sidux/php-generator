<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Helper\VarPrinter;
use Sidux\PhpGenerator\Model\Contract\ValueAware;

class Value
{
    private $value;

    private bool $literal;

    private ?ValueAware $parent = null;

    /**
     * @param mixed $value
     */
    public function __construct($value, bool $literal = true)
    {
        $this->value   = $value;
        $this->literal = $literal;
    }

    public function __toString(): string
    {
        if (\is_string($this->value) && $this->isLiteral()) {
            return (string)$this->value;
        }

        return VarPrinter::dump($this->value);
    }

    public function getParent(): ?ValueAware
    {
        return $this->parent;
    }

    public function setParent(?ValueAware $parent): void
    {
        $this->parent = $parent;
    }

    public function isLiteral(): bool
    {
        return $this->literal;
    }

    public function isNull(): bool
    {
        return (null === $this->value && $this->isLiteral()) || ('null' === $this->value && !$this->isLiteral());
    }

    public function getValue()
    {
        return $this->value;
    }
}
