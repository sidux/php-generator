<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\StringHelper;
use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Part;

/**
 * @method static self from(ReflectionMethod|string|array $from)
 */
final class PhpMethod implements PhpMember
{
    use Part\AbstractAwareTrait;
    use Part\CommentAwareTrait;
    use Part\FinalAwareTrait;
    use Part\NameAwareTrait;
    use Part\ReferenceAwareTrait;
    use Part\TypeAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\StaticAwareTrait;
    use Part\ParentAwareTrait;
    use Helper\Traits\StaticCreateAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

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

    public static function fromArray(array $from): self
    {
        [$className, $methodName] = $from;
        if (\is_object($className)) {
            $className = \get_class($className);
        }
        $ref = ReflectionMethod::createFromName($className, $methodName);

        return static::fromReflectionMethod($ref);
    }

    public static function fromReflectionFunction(ReflectionFunction $ref): self
    {
        $method = new self($ref->getName());
        $method->setParameters(array_map([PhpParameter::class, 'from'], $ref->getParameters()));
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
        $method->setParameters(array_map([PhpParameter::class, 'from'], $ref->getParameters()));
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

        if (\count($parts) === 2) {
            [$className, $methodName] = $parts;
            $ref = ReflectionMethod::createFromName($className, $methodName);

            return static::fromReflectionMethod($ref);
        }

        if (\count($parts) === 1) {
            [$methodName] = $parts;
            $ref = ReflectionFunction::createFromName($methodName);

            return static::fromReflectionFunction($ref);
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

    /**
     * @param string $name without $
     */
    public function removeParameter(string $name): self
    {
        unset($this->parameters[$name]);

        return $this;
    }

    public function hasBody(): bool
    {
        return !($this->isAbstract() || ($this->getParent() && $this->getParent()->getType() === PhpStruct::TYPE_INTERFACE));
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $code = null): self
    {
        $this->body = $code;

        return $this;
    }

    /**
     * @return PhpParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param PhpParameter[] $parameters
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
}
