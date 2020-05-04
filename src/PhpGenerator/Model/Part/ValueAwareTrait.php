<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;


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

    /**
     * @param mixed $value
     * @param bool $literal
     *
     * @return ValueAwareTrait
     */
    public function setValue($value, $literal = false): self
    {
        $literal = $literal || $value === null;
        $this->setInitialized();
        if (!$value instanceof PhpValue) {
            $value = new PhpValue($value, $literal);
        }
        $this->value = $value;
        //todo remove this from here
        if ($this->value->isNull()) {
            $this->addType('null');
        }

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
}
