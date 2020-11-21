<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Value;

/**
 * @internal
 */
trait ValueAwareTrait
{
    private ?Value $value = null;

    private bool $initialized = false;

    public function removeValue(): self
    {
        $this->value = null;
        $this->setInitialized(false);

        return $this;
    }

    public function getValue(): ?Value
    {
        return $this->value;
    }

    public function setValue($value, bool $literal = false): self
    {
        $literal = $literal || (null === $value);
        $this->setInitialized();
        if (!$value instanceof Value) {
            $value = new Value($value, $literal);
        }
        $this->value = $value;
        $this->handleValueType();

        return $this;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function setInitialized(bool $initialized = true): self
    {
        $this->initialized = $initialized;

        if (null === $this->value) {
            $this->value = new Value(null);
        }

        return $this;
    }

    private function handleValueType(): void
    {
        if (!$this->value || !$this instanceof TypeAware) {
            return;
        }
        if ($this->value->isNull()) {
            $this->addType('null');
        }
        if ($this->value->isLiteral()) {
            return;
        }
        if (\is_string($this->value->getValue())) {
            $this->addType('string');
        }
        if (\is_int($this->value->getValue())) {
            $this->addType('int');
        }
        if (\is_float($this->value->getValue())) {
            $this->addType('float');
        }
        if (\is_array($this->value->getValue())) {
            $this->addType('array');
        }
    }
}
