<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\StringHelper;
use Sidux\PhpGenerator\Model\Contract\PhpElement;
use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Part;

/**
 * @method static self from(ReflectionMethod|ReflectionFunction|string|array $from)
 */
final class PhpMethod extends PhpMember implements PhpElement, TypeAware
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

    public const DEFAULT_VISIBILITY = PhpStruct::VISIBILITY_PUBLIC;

    private ?string $body = '';

    /**
     * @var array<string, PhpParameter>
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
        $output .= implode(', ', $this->getParameters());
        $output .= ')';
        $output .= $this->getTypeHint() ? ': ' . $this->getTypeHint() : null;
        $output .= $this->hasBody() ? "\n{\n" : ";\n";
        $output .= $this->hasBody() && $this->getBody() ? StringHelper::indent($this->getBody()) . "\n" : null;
        $output .= $this->hasBody() ? "}\n" : null;

        return $output;
    }

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public static function fromArray(array $from): self
    {
        [$className, $methodName] = $from;
        if (\is_object($className)) {
            $className = \get_class($className);
        }
        $ref = ReflectionMethod::createFromName($className, $methodName);

        return self::fromReflectionMethod($ref);
    }

    public static function fromReflectionFunction(ReflectionFunction $ref): self
    {
        $method = new self($ref->getName());
        $method->setParameters(array_map(PhpParameter::class . '::from', $ref->getParameters()));
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
        $method->setParameters(array_map(PhpParameter::class . '::from', $ref->getParameters()));
        $method->setStatic($ref->isStatic());
        $method->setVariadic($ref->isVariadic());
        $isInterface = $ref->getDeclaringClass()->isInterface();

        if ($isInterface) {
            $method->setVisibility(PhpStruct::VISIBILITY_PUBLIC);
        } elseif ($ref->isPrivate()) {
            $method->setVisibility(PhpStruct::VISIBILITY_PRIVATE);
        } elseif ($ref->isProtected()) {
            $method->setVisibility(PhpStruct::VISIBILITY_PROTECTED);
        } else {
            $method->setVisibility(PhpStruct::VISIBILITY_PUBLIC);
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
        if ($this->abstract && ($this->final || $this->visibility === PhpStruct::VISIBILITY_PRIVATE)) {
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
            return $parent->getType() !== PhpStruct::TYPE_INTERFACE;
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
     * @return array<string, PhpParameter>|\ArrayAccess
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, PhpParameter>|\ArrayAccess $parameters
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

    /**
     * @param PhpParameter|string $parameter
     *
     * @return PhpParameter
     */
    public function addParameter($parameter): PhpParameter
    {
        if (!$parameter instanceof PhpParameter) {
            $parameter = new PhpParameter($parameter);
        }
        $parameter->setParent($this);
        $this->parameters[$parameter->getName()] = $parameter;

        return $parameter;
    }

    /**
     * @psalm-return value-of<PhpStruct::VISIBILITIES>
     */
    public function getDefaultVisibility(): string
    {
        return $this->getParent() ? $this->getParent()->getDefaultMethodVisibility() : self::DEFAULT_VISIBILITY;
    }

    /**
     * @param string|PhpProperty $initProperty
     */
    public function initProperty($initProperty): self
    {
        if (!$this->getParent()) {
            throw new \RuntimeException('This method has no parent class');
        }
        if ($this->getParent()->hasProperty($initProperty)) {
            $property = $this->getParent()->getProperty($initProperty);
        } else {
            $property = $this->getParent()->addProperty($initProperty);
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
     * @param string[]|PhpProperty[] $initProperties
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
}
