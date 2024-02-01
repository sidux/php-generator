<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

final class PromotedParameter extends Parameter
{
    private string $visibility;

    private bool $readOnly = false;

    public function __construct(string $name, string $visibility)
    {
        $this->visibility = $visibility;
        parent::__construct($name);
    }

    public static function create(string $name, string $visibility = Struct::PUBLIC): PromotedParameter
    {
        return new self($name, $visibility);
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setReadOnly(bool $state = true): PromotedParameter
    {
        $this->readOnly = $state;
        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * @throws \Exception
     */
    public function validate(): void
    {
        if ($this->readOnly && count($this->getTypes()) === 0) {
            throw new \Exception("Property \${$this->getName()}: Read-only properties are only supported on typed property.");
        }
    }
}
