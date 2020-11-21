<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionParameter;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Model\Contract\PhpElement;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Contract\ValueAware;
use Sidux\PhpGenerator\Model\Part;


/**
 * @method static self from(ReflectionParameter|string|array $from)
 */
final class PhpParameter implements ValueAware, PhpElement, TypeAware
{
    use Part\NameAwareTrait;
    use Part\ValueAwareTrait;
    use Part\TypeAwareTrait;
    use Part\ReferenceAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    private ?PhpMethod $parent = null;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getParent(): ?PhpMethod
    {
        return $this->parent;
    }

    public function setParent(?PhpMethod $parent): void
    {
        $this->parent = $parent;
    }

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public function __toString(): string
    {
        $output = '';
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
            $param->setValue(new PhpValue($ref->getDefaultValue(), $ref->isDefaultValueConstant()));
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
}
