<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Part;

class PhpQualifiedName extends PhpMember implements NamespaceAware
{
    use Part\NamespaceAwareTrait;

    protected ?string $alias;

    public function __construct(string $qualifiedName, ?string $alias = null)
    {
        $this->setQualifiedName($qualifiedName);
        $this->setAlias($alias);
        $parent = $this->getParent();
        if ($parent && $parent->hasResolveTypes()) {
            $parent->addNamespaceUse($qualifiedName, $alias);
        }
    }

    public function __toString(): string
    {
        if (!$this->qualifiedName) {
            return $this->name;
        }
        $qualifiedName = $this->getParent() ? $this->qualifiedName : ltrim($this->qualifiedName, '\\');

        return $this->alias ?? ($this->isResolved() ? $this->name : $qualifiedName);
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function isResolved(): bool
    {
        $parent = $this->getParent();
        if (!$parent) {
            return false;
        }

        if (substr_count($this->getQualifiedName(), '\\') < 2) {
            return false;
        }

        if ($parent->getNamespace() === $this->getNamespace()) {
            return true;
        }

        return $parent->hasResolveTypes();
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }
}
