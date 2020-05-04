<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionParameter;
use Sidux\PhpGenerator\Model\Contract\ValueAware;
use Sidux\PhpGenerator\Model\Part;
use Sidux\PhpGenerator\Helper;


/**
 * @method static self from(ReflectionParameter|string|array $from)
 * @method null|PhpMethod getParent()
 * @method self setParent(null|PhpMethod $method)
 */
final class PhpParameter implements ValueAware
{
    use Part\NameAwareTrait;
    use Part\ValueAwareTrait;
    use Part\TypeAwareTrait;
    use Part\ParentAwareTrait;
    use Part\ReferenceAwareTrait;
    use Helper\Traits\StaticCreateAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    public function __construct(string $name)
    {
        $this->setName($name);
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

        return static::fromReflectionParameter($ref);
    }

    public static function fromReflectionParameter(ReflectionParameter $ref): self
    {
        $param = new static($ref->getName());
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

        return static::fromReflectionParameter($ref);
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
}
