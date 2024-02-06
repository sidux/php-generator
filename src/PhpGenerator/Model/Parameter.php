<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionParameter;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Model\Contract\Element;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Contract\ValueAware;
use Sidux\PhpGenerator\Model\Part\ReadOnlyAwareTrait;

/**
 * @method static self from(ReflectionParameter|string|array $from)
 */
class Parameter implements ValueAware, Element, TypeAware
{
    use Part\NameAwareTrait;
    use Part\ValueAwareTrait;
    use Part\TypeAwareTrait;
    use Part\ReferenceAwareTrait;
    use Part\VisibilityAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;
    use ReadOnlyAwareTrait;

    final public const DEFAULT_VISIBILITY = Struct::PRIVATE;

    private ?Method $parent = null;

    private bool $isPromoted = false;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getParent(): ?Method
    {
        return $this->parent;
    }

    public function setParent(?Method $parent): void
    {
        $this->parent = $parent;
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function __toString(): string
    {
        $output = '';
        $output .= $this->isPromoted() ? $this->getVisibility() . ' ' : null;
        $output .= $this->isReadOnly() ? 'readonly ' : null;
        $output .= (string)$this->getTypeHint() ? $this->getTypeHint() . ' ' : null;
        $output .= $this->isReference() ? '&' : null;
        $output .= $this->isVariadic() ? '...' : null;
        $output .= '$' . $this->getName();
        $output .= $this->isInitialized() && !$this->isVariadic() ? ' = ' . $this->getValue() : '';

        return $output;
    }

    public static function fromArray(array $from): self
    {
        [$classInstance, $methodName, $parameterName] = $from;
        $ref = ReflectionParameter::createFromClassInstanceAndMethod($classInstance, $methodName, $parameterName);

        return self::fromReflectionParameter($ref);
    }

    public static function fromReflectionParameter(ReflectionParameter $ref): self
    {
        $param = new self($ref->getName());
        $param->setReference($ref->isPassedByReference());
        $param->addType($ref->getType());
        $param->addTypes($ref->getDocBlockTypes());
        if (!$ref->isVariadic() && ($ref->isOptional() || $ref->isDefaultValueAvailable())) {
            $param->setValue(new Value($ref->getDefaultValue(), $ref->isDefaultValueConstant()));
        }


        return $param;
    }

    public static function fromString(string $from): self
    {
        [$className, $methodName, $parameterName] = explode('::', $from);
        $ref = ReflectionParameter::createFromClassNameAndMethod($className, $methodName, $parameterName);

        return self::fromReflectionParameter($ref);
    }

    public function isVariadic(): bool
    {
        $method = $this->getParent();
        if ($method && $method->isVariadic()) {
            $parameters = $method->getParameters();
            $lastParam  = end($parameters);

            return $this->getName() === $lastParam->getName();
        }

        return false;
    }

    public function resolve(): self
    {
        foreach ($this->getTypes() as $type) {
            $type->resolve();
        }

        return $this;
    }

    public function getDefaultVisibility(): string
    {
        $parent = $this->getParent();
        if ($parent) {
            return $parent->getDefaultParameterVisibility();
        }

        return self::DEFAULT_VISIBILITY;
    }

    public function setPromoted(): self
    {
        if ($this->getParent() && $this->getParent()->getName() !== Method::CONSTRUCTOR) {
            throw new \RuntimeException('Promotion is only allowed for constructor parameters');
        }

        $this->isPromoted = true;

        return $this;
    }

    public function isPromoted(): bool
    {
        return $this->isPromoted;
    }
}
