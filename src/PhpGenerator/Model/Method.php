<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\StringHelper;
use Sidux\PhpGenerator\Model\Contract\Element;
use Sidux\PhpGenerator\Model\Contract\Member;
use Sidux\PhpGenerator\Model\Contract\TypeAware;

/**
 * @method static self from(ReflectionMethod|ReflectionFunction|string|array $from)
 */
final class Method extends Member implements Element, TypeAware
{
    use Part\AbstractAwareTrait;
    use Part\CommentAwareTrait;
    use Part\FinalAwareTrait;
    use Part\NameAwareTrait;
    use Part\ReferenceAwareTrait;
    use Part\TypeAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\StaticAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    public const DEFAULT_VISIBILITY = Struct::PUBLIC;

    private ?string $body = '';

    /**
     * @var array<string, Parameter>
     */
    private array $parameters = [];

    private bool $variadic = false;

    public function __toString(): string
    {
        $this->validate();

        $output = '';
        $output .= $this->commentsToString();
        $output .= $this->isAbstract() ? 'abstract ' : null;
        $output .= $this->isFinal() ? 'final ' : null;
        $output .= $this->getParent() && $this->getVisibility() ? $this->getVisibility() . ' ' : null;
        $output .= $this->isStatic() ? 'static ' : null;
        $output .= 'function ';
        $output .= $this->isReference() ? '&' : null;
        $output .= $this->getName();
        $output .= '(';

        if (\count($this->getParameters()) >= 2) {
            $output .= "\n";
            $output .= StringHelper::indent(implode(",\n", $this->getParameters()));
            $output .= "\n";
        } else {
            $output .= implode(', ', $this->getParameters());
        }
        $output .= ')';
        $output .= $this->getTypeHint() ? ': ' . $this->getTypeHint() : null;
        $output .= $this->hasBody() ? $this->isBodyEmpty() ? " {\n" : "\n{\n" : ";\n";
        $output .= $this->hasBody() && $this->getBody() ? StringHelper::indent($this->getBody()) . "\n" : null;
        $output .= $this->hasBody() ? "}\n" : null;

        return $output;
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    public static function fromArray(array $from): self
    {
        [$className, $methodName] = $from;
        if (\is_object($className)) {
            $className = $className::class;
        }
        $ref = ReflectionMethod::createFromName($className, $methodName);

        return self::fromReflectionMethod($ref);
    }

    public static function fromReflectionFunction(ReflectionFunction $ref): self
    {
        $method = new self($ref->getName());
        /** @var  array<string, Parameter> $parameters */
        $parameters = array_map(Parameter::class . '::from', $ref->getParameters());
        $method->setParameters($parameters);
        $method->setVariadic($ref->isVariadic());
        $method->setBody($ref->getBodyCode());
        $method->setReference($ref->returnsReference());
        $method->setComment($ref->getDocComment());
        if ($ref->hasReturnType()) {
            $method->addType($ref->getReturnType());
            $method->addTypes($ref->getDocBlockReturnTypes());
        }

        return $method;
    }

    public static function fromReflectionMethod(ReflectionMethod $ref): self
    {
        $method = new self($ref->getName());
        /** @var  array<string, Parameter> $parameters */
        $parameters = array_map(Parameter::class . '::from', $ref->getParameters());
        $method->setParameters($parameters);
        $method->setStatic($ref->isStatic());
        $method->setVariadic($ref->isVariadic());
        $isInterface = $ref->getDeclaringClass()->isInterface();

        if ($isInterface) {
            $method->setVisibility(Struct::PUBLIC);
        } elseif ($ref->isPrivate()) {
            $method->setVisibility(Struct::PRIVATE);
        } elseif ($ref->isProtected()) {
            $method->setVisibility(Struct::PROTECTED);
        } else {
            $method->setVisibility(Struct::PUBLIC);
        }

        $method->setFinal($ref->isFinal());
        $method->setAbstract($ref->isAbstract() && !$isInterface);
        $method->setBody($ref->getBodyCode());
        $method->setReference($ref->returnsReference());
        $method->setComment($ref->getDocComment());
        if ($ref->hasReturnType()) {
            $method->addType($ref->getReturnType());
            $method->addTypes($ref->getDocBlockReturnTypes());
        }

        return $method;
    }

    public static function fromString(string $from): self
    {
        $parts = explode('::', $from);

        if (2 === \count($parts)) {
            [$className, $methodName] = $parts;
            $ref = ReflectionMethod::createFromName($className, $methodName);

            return self::fromReflectionMethod($ref);
        }

        if (1 === \count($parts)) {
            [$methodName] = $parts;
            $ref = ReflectionFunction::createFromName($methodName);

            return self::fromReflectionFunction($ref);
        }

        throw new  \InvalidArgumentException("Invalid method/function name $from");
    }

    public function validate(): self
    {
        if ($this->abstract && ($this->final || $this->visibility === Struct::PRIVATE)) {
            throw new \DomainException('Cannot be abstract and final or private.');
        }

        return $this;
    }

    public function removeParameter(string $name): self
    {
        unset($this->parameters[$name]);

        return $this;
    }

    public function hasBody(): bool
    {
        if ($this->isAbstract()) {
            return false;
        }

        $parent = $this->getParent();
        if ($parent) {
            return $parent->getType() !== Struct::_INTERFACE;
        }

        return true;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $code): self
    {
        $this->body = $code;

        return $this;
    }

    /**
     * @return array<string, Parameter>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param Parameter[] $parameters
     */
    public function setParameters(array $parameters): self
    {
        foreach ($parameters as $param) {
            $this->addParameter($param);
        }

        return $this;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function setVariadic(bool $variadic = true): self
    {
        $this->variadic = $variadic;

        return $this;
    }

    public function addBody(string $code): self
    {
        $this->body .= ($this->body ? "\n" : '') . $code;

        return $this;
    }

    public function addMember(Parameter $parameter): self
    {
        $this->addParameter($parameter);

        return $this;
    }

    /**
     * @param Parameter|string $parameter
     *
     * @return Parameter
     */
    public function addParameter($parameter): Parameter
    {
        if (!$parameter instanceof Parameter) {
            $parameter = new Parameter($parameter);
        }
        $parameter->setParent($this);
        $this->parameters[$parameter->getName()] = $parameter;

        return $parameter;
    }

    public function addPromotedParameter(string|PromotedParameter $promotedParameter, string $visibility = Struct::PUBLIC): self
    {
        if (!$promotedParameter instanceof PromotedParameter) {
            $promotedParameter = new PromotedParameter($promotedParameter, $visibility);
        }
        $promotedParameter->setParent($this);
        $this->parameters[$promotedParameter->getName()] = $promotedParameter;

        return $this;
    }

    /**
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultVisibility(): string
    {
        $parent = $this->getParent();
        if ($parent) {
            return $parent->getDefaultMethodVisibility();
        }

        return self::DEFAULT_VISIBILITY;
    }

    /**
     * @param string|Property $initProperty
     */
    public function initProperty($initProperty): self
    {
        $parent = $this->getParent();
        if (!$parent) {
            throw new \RuntimeException('This method has no parent class');
        }
        if ($parent->hasProperty($initProperty)) {
            $property = $parent->getProperty($initProperty);
        } else {
            $property = $parent->addProperty($initProperty);
        }
        $parameter = $this->addParameter($property->getName())
                          ->addTypes($property->getTypes())
        ;
        if ($property->getValue()) {
            $parameter->setValue($property->getValue());
        }

        $this->addBody("\$this->{$property->getName()} = \${$property->getName()};");

        return $this;
    }

    /**
     * @param string[]|Property[] $initProperties
     */
    public function initProperties(array $initProperties): self
    {
        foreach ($initProperties as $initProperty) {
            $this->initProperty($initProperty);
        }

        return $this;
    }

    public function resolve(): self
    {
        foreach ($this->getTypes() as $type) {
            $type->resolve();
        }
        foreach ($this->getParameters() as $parameter) {
            $parameter->resolve();
        }

        return $this;
    }

    public function isBodyEmpty(): bool
    {
        return isset($this->body);
    }
}
