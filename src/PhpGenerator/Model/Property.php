<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionProperty;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Model\Contract\Element;
use Sidux\PhpGenerator\Model\Contract\Member;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Contract\ValueAware;

/**
 * @method static self from(ReflectionProperty|array|string $from)
 */
final class Property extends Member implements ValueAware, Element, TypeAware
{
    use Helper\Traits\MethodOverloadAwareTrait;
    use Part\NameAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\CommentAwareTrait;
    use Part\ValueAwareTrait;
    use Part\TypeAwareTrait;
    use Part\StaticAwareTrait;

    public const DEFAULT_VISIBILITY = Struct::PRIVATE;

    public function __toString(): string
    {
        $output = '';
        $output .= $this->commentsToString();
        $output .= $this->getVisibility() . ' ';
        $output .= $this->isStatic() ? 'static ' : null;
        $output .= $this->getTypeHint() ? $this->getTypeHint() . ' ' : null;
        $output .= '$' . $this->getName();
        $output .= $this->isInitialized() ? ' = ' . $this->getValue() : '';
        $output .= ';';
        $output .= "\n";

        return $output;
    }

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public static function fromArray(array $from): self
    {
        $ref = ReflectionProperty::createFromInstance($from[0], $from[1]);

        return self::fromReflectionProperty($ref);
    }

    public static function fromPrototype(string $property): Property
    {
        preg_match('/(?:([\w|\\\\\[\]]+) )?\$?(\w+)(?:\s*=\s*(.+))?/', $property, $parts);

        $type  = trim($parts[1]) ?: '';
        $name  = trim($parts[2]);
        $value = $parts[3] ?? null;

        $propertyObj = new self($name);
        $propertyObj->setTypes(explode('|', $type));

        if (null !== $value) {
            $propertyObj->setValue(new Value($value));
        }

        return $propertyObj;
    }

    public static function fromReflectionProperty(ReflectionProperty $ref): self
    {
        $prop = new self($ref->getName());
        if ($ref->getAst()->props[0]->default) {
            $prop->setValue($ref->getDefaultValue());
        }
        $prop->addType($ref->getType());
        $prop->setStatic($ref->isStatic());
        foreach ($ref->getDocBlockTypes() as $type) {
            $prop->addType((string)$type);
        }
        $prop->setComment($ref->getDocComment());

        if ($ref->isPrivate()) {
            $prop->setVisibility(Struct::PRIVATE);
        } elseif ($ref->isProtected()) {
            $prop->setVisibility(Struct::PROTECTED);
        } else {
            $prop->setVisibility(Struct::PUBLIC);
        }

        return $prop;
    }

    public static function fromString(string $from): self
    {
        [$className, $propertyName] = explode('::', $from);
        $ref = ReflectionProperty::createFromName($className, $propertyName);

        return self::fromReflectionProperty($ref);
    }

    /**
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultVisibility(): string
    {
        $parent = $this->getParent();
        if ($parent) {
            return $parent->getDefaultPropertyVisibility();
        }

        return self::DEFAULT_VISIBILITY;
    }

    public function resolve(): self
    {
        foreach ($this->getTypes() as $type) {
            $type->resolve();
        }

        return $this;
    }
}
