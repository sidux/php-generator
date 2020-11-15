<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\Reflection\ReflectionProperty;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Model\Contract\PhpElement;
use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Contract\TypeAware;
use Sidux\PhpGenerator\Model\Contract\ValueAware;
use Sidux\PhpGenerator\Model\Part;

/**
 * @method static self from(ReflectionProperty|array|string $from)
 */
final class PhpProperty implements ValueAware, PhpMember, PhpElement, TypeAware
{
    use Helper\Traits\MethodOverloadAwareTrait;
    use Part\NameAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\CommentAwareTrait;
    use Part\ValueAwareTrait;
    use Part\TypeAwareTrait;
    use Part\StaticAwareTrait;

    private ?PhpStruct $parent = null;

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

    public static function fromArray(array $from): self
    {
        $ref = ReflectionProperty::createFromInstance($from[0], $from[1]);

        return self::fromReflectionProperty($ref);
    }

    public static function fromPrototype(string $property): PhpProperty
    {
        preg_match('/(?:([\w|\\\\\[\]]+) )?\$?(\w+)(?:\s*=\s*(.+))?/', $property, $parts);

        $type  = trim($parts[1]) ?: '';
        $name  = trim($parts[2]);
        $value = $parts[3] ?? null;

        $propertyObj = new self($name);
        $propertyObj->setTypes(explode('|', $type));

        if (null !== $value) {
            $propertyObj->setValue(new PhpValue($value));
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
            $prop->setVisibility(PhpStruct::VISIBILITY_PRIVATE);
        } elseif ($ref->isProtected()) {
            $prop->setVisibility(PhpStruct::VISIBILITY_PROTECTED);
        } else {
            $prop->setVisibility(PhpStruct::VISIBILITY_PUBLIC);
        }

        return $prop;
    }

    public static function fromString(string $from): self
    {
        [$className, $propertyName] = explode('::', $from);
        $ref = ReflectionProperty::createFromName($className, $propertyName);

        return self::fromReflectionProperty($ref);
    }

    public function getParent(): ?PhpStruct
    {
        return $this->parent;
    }

    public function setParent(?PhpStruct $parent): void
    {
        $this->parent = $parent;
    }
}
