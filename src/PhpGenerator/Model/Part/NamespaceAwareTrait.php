<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Helper\PhpHelper;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;

/**
 * @internal
 */
trait NamespaceAwareTrait
{
    use NameAwareTrait;

    protected ?string $namespace = null;

    protected string $qualifiedName;

    public function __construct(string $qualifiedName)
    {
        $this->setQualifiedName($qualifiedName);
    }

    public function fromNamespaceAware(NamespaceAware $namespaceAware): self
    {
        return new static($namespaceAware->getQualifiedName());
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function hasNamespace(): bool
    {
        return $this->namespace && $this->namespace !== '\\';
    }

    public function getQualifiedName(): string
    {
        return ltrim($this->qualifiedName, '\\');
    }

    public function setNamespace(string $namespace): self
    {
        if (!$namespace) {
            return $this;
        }
        if (!PhpHelper::isNamespaceIdentifier($namespace)) {
            throw new \InvalidArgumentException("Value '$namespace' is not valid namespace.");
        }
        $this->namespace = $namespace;

        return $this;
    }

    public function setQualifiedName(string $qualifiedName): self
    {
        $this->qualifiedName = $qualifiedName;

        $this->setName(PhpHelper::extractShortName($qualifiedName));
        $this->setNamespace(PhpHelper::extractNamespace($qualifiedName));

        return $this;
    }
}
