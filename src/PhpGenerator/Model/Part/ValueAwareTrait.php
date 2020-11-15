<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\PhpValue;

/**
 * @internal
 */
trait ValueAwareTrait
{
    private ?PhpValue $value = null;

    private bool $initialized = false;

    public function removeValue(): self
    {
        $this->value = null;
        $this->setInitialized(false);

        return $this;
    }

    public function getValue(): ?PhpValue
    {
        return $this->value;
    }

    public function setValue($value, bool $literal = false): self
    {
        $literal = $literal || (null === $value);
        $this->setInitialized();
        if (!$value instanceof PhpValue) {
            $value = new PhpValue($value, $literal);
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
            $this->value = new PhpValue(null);
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
