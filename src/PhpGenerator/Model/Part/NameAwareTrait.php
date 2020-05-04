<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Helper\PhpHelper;

/**
 * @internal
 */
trait NameAwareTrait
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        if (!PhpHelper::isIdentifier($name)) {
            throw new \InvalidArgumentException("Value '$name' is not valid name.");
        }
        $this->name = $name;

        return $this;
    }
}
