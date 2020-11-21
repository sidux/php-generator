<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionObject;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Sidux\PhpGenerator\Helper;
use Sidux\PhpGenerator\Helper\PhpHelper;
use Sidux\PhpGenerator\Helper\StringHelper;
use Sidux\PhpGenerator\Model\Contract\Element;
use Sidux\PhpGenerator\Model\Contract\Member;
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Part;

/**
 * @method static self from(ReflectionClass|ReflectionObject|string|object $from)
 */
final class Struct implements NamespaceAware, Element
{
    use Part\CommentAwareTrait;
    use Part\NamespaceAwareTrait;
    use Part\FinalAwareTrait;
    use Part\AbstractAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    public const TYPES = [
        Struct::TYPE_CLASS,
        Struct::TYPE_INTERFACE,
        Struct::TYPE_TRAIT,
    ];

    public const
        TYPE_CLASS = 'class',
        TYPE_INTERFACE = 'interface',
        TYPE_TRAIT = 'trait';

    public const VISIBILITIES = [
        Struct::VISIBILITY_PUBLIC,
        Struct::VISIBILITY_PROTECTED,
        Struct::VISIBILITY_PRIVATE,
    ];

    public const
        VISIBILITY_PUBLIC = 'public',
        VISIBILITY_PROTECTED = 'protected',
        VISIBILITY_PRIVATE = 'private';

    /**
     * @var array<string, Constant>
     */
    private array $consts = [];

    /**
     * @var array<string, QualifiedName>
     */
    private array $extends = [];

    /**
     * @var array<string, QualifiedName>
     */
    private array $implements = [];

    /**
     * @var array<string, Method>
     */
    private array $methods = [];

    /**
     * @var array<string, Property>
     */
    private array $properties = [];

    private bool $resolveTypes = true;

    private bool $strictTypes = true;

    /**
     * @var array<string, TraitUse>
     */
    private array $traits = [];

    /**
     * @psalm-var value-of<Struct::TYPES>
     */
    private string $type = self::TYPE_CLASS;

    /**
     * @var array<string, NamespaceUse>
     */
    private array $namespaceUses = [];

    /**
     * @psalm-var value-of<Struct::VISIBILITIES>
     */
    private string $defaultConstVisibility = Constant::DEFAULT_VISIBILITY;

    /**
     * @psalm-var value-of<Struct::VISIBILITIES>
     */
    private string $defaultPropertyVisibility = Property::DEFAULT_VISIBILITY;

    /**
     * @psalm-var value-of<Struct::VISIBILITIES>
     */
    private string $defaultMethodVisibility = Method::DEFAULT_VISIBILITY;

    public function __clone()
    {
        $clone = static function ($item) {
            return clone $item;
        };

        $this->namespaceUses = array_map($clone, $this->namespaceUses);
        $this->traits        = array_map($clone, $this->traits);
        $this->consts        = array_map($clone, $this->consts);
        $this->properties    = array_map($clone, $this->properties);
        $this->methods       = array_map($clone, $this->methods);
    }

    public function __toString(): string
    {
        $this->validate();

        $output = "<?php\n\n";

        $output .= $this->hasStrictTypes() ? "declare(strict_types=1);\n\n" : null;
        $output .= $this->hasNamespace() ? "namespace $this->namespace;\n\n" : null;
        $output .= $this->getNamespaceUses() ? implode("\n", $this->getNamespaceUses()) . "\n\n" : null;
        $output .= $this->commentsToString();
        $output .= $this->isAbstract() ? 'abstract ' : null;
        $output .= $this->isFinal() ? 'final ' : null;
        $output .= $this->type . ' ';
        $output .= $this->getName();
        $output .= $this->getExtends() ? ' extends ' . implode(', ', $this->getExtends()) : null;
        $output .= $this->getImplements() ? ' implements ' . implode(', ', $this->getImplements()) : null;
        $output .= "\n{\n";

        $members = array_filter(
            [
                implode('', $this->getTraits()),
                implode("\n", $this->getConstants()),
                implode("\n", $this->getProperties()),
                implode("\n", $this->getMethods()),
            ]
        );
        $output  .= $members ? StringHelper::indent(implode("\n", $members)) : null;
        $output  .= "}\n";

        return $output;
    }

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public static function fromFile(string $fileName): self
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector  = new ClassReflector(new SingleFileSourceLocator($fileName, $astLocator));
        $classes    = $reflector->getAllClasses();
        if (!$classes) {
            throw new \InvalidArgumentException("No class found in file $fileName");
        }
        if (\count($classes) > 1) {
            throw new \InvalidArgumentException("Multiple classes found in file $fileName");
        }

        return self::fromReflectionClass($classes[0]);
    }

    public static function fromObject(object $object): self
    {
        $from = ReflectionClass::createFromInstance($object);

        return self::fromReflectionClass($from);
    }

    public static function fromReflectionClass(ReflectionClass $from): self
    {
        if ($from->isAnonymous()) {
            throw new \InvalidArgumentException('Anonymous classes are not supported.');
        }
        $class = new self($from->getName());

        $class->setComment($from->getDocComment());

        if ($from->isTrait()) {
            $class->setType(self::TYPE_TRAIT);
        } elseif ($from->isInterface()) {
            $class->setType(self::TYPE_INTERFACE);
        } else {
            $class->setType($class::TYPE_CLASS);
        }
        $class->setFinal($from->isFinal() && $class->getType() === $class::TYPE_CLASS);
        $class->setAbstract($from->isAbstract() && $class->getType() === $class::TYPE_CLASS);

        $interfaces = [];
        //bug fix getImmediateInterfaces should not return parent interfaces
        foreach ($from->getImmediateInterfaces() as $interface) {
            foreach ($interfaces as $implement) {
                if ($implement->implementsInterface($interface->getName())) {
                    continue 2;
                }
            }
            $interfaces[$interface->getName()] = $interface;
        }

        $class->setImplements(array_keys($interfaces));

        $parent = $from->getParentClass();
        if ($parent) {
            $class->setExtends([$parent->getName()]);
        }

        $props = [];
        foreach ($from->getImmediateProperties() as $prop) {
            if ($prop->isDefault()) {
                $props[] = Property::from($prop);
            }
        }
        $class->setProperties($props);

        $methods = [];
        foreach ($from->getImmediateMethods() as $method) {
            $methods[] = Method::from($method);
        }
        $class->setMethods($methods);
        $class->setConstants($from->getImmediateConstants());

        return $class;
    }

    public static function fromReflectionObject(ReflectionObject $from): self
    {
        return self::fromReflectionClass($from);
    }

    public static function fromString(string $className): self
    {
        $from = ReflectionClass::createFromName($className);

        return self::fromReflectionClass($from);
    }

    public function hasMethod(string $name): bool
    {
        return isset($this->methods[$name]);
    }

    /**
     * @param Property|string $name
     */
    public function hasProperty($name): bool
    {
        if ($name instanceof Property) {
            $name = $name->getName();
        }

        return isset($this->properties[$name]);
    }

    public function hasResolveTypes(): bool
    {
        return $this->resolveTypes;
    }

    public function hasStrictTypes(): bool
    {
        return $this->strictTypes;
    }

    public function removeConstant(string $name): self
    {
        unset($this->consts[$name]);

        return $this;
    }

    public function removeMethod(string $name): self
    {
        unset($this->methods[$name]);

        return $this;
    }

    public function removeProperty(string $name): self
    {
        unset($this->properties[$name]);

        return $this;
    }

    /**
     * @return Constant[]
     */
    public function getConstants(): array
    {
        return $this->consts;
    }

    /**
     * @return array<string, QualifiedName>
     */
    public function getExtends(): array
    {
        return $this->extends;
    }

    /**
     * @param string[]|NamespaceAware[] $names
     */
    public function setExtends(array $names): self
    {
        foreach ($names as $name) {
            $this->addExtend($name);
        }

        return $this;
    }

    /**
     * @return array<string, QualifiedName>
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * @param string[]|NamespaceAware[] $names
     */
    public function setImplements(array $names): self
    {
        foreach ($names as $name) {
            $this->addImplement($name);
        }

        return $this;
    }

    /**
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultConstVisibility(): string
    {
        return $this->defaultConstVisibility;
    }

    /**
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultPropertyVisibility(): string
    {
        return $this->defaultPropertyVisibility;
    }

    /**
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultMethodVisibility(): string
    {
        return $this->defaultMethodVisibility;
    }

    /**
     * @psalm-param value-of<Struct::VISIBILITIES> $visibility
     */
    public function setDefaultConstVisibility(string $visibility): self
    {
        $this->defaultConstVisibility = $visibility;

        return $this;
    }

    /**
     * @psalm-param value-of<Struct::VISIBILITIES> $visibility
     */
    public function setDefaultPropertyVisibility(string $visibility): self
    {
        $this->defaultPropertyVisibility = $visibility;

        return $this;
    }

    /**
     * @psalm-param value-of<Struct::VISIBILITIES> $visibility
     */
    public function setDefaultMethodVisibility(string $visibility): self
    {
        $this->defaultMethodVisibility = $visibility;

        return $this;
    }

    public function getMethod(string $name): Method
    {
        if (!isset($this->methods[$name])) {
            throw new \InvalidArgumentException("Method '$name' not found.");
        }

        return $this->methods[$name];
    }

    /**
     * @return array<string, Method>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param Method[]|string[] $methods
     */
    public function setMethods(array $methods): self
    {
        $this->methods = [];
        foreach ($methods as $method) {
            $this->addMethod($method);
        }

        return $this;
    }

    /**
     * @return array<string, Property>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param Property[]|string[] $properties
     */
    public function setProperties(array $properties): self
    {
        $this->properties = [];
        foreach ($properties as $value) {
            $this->addProperty($value);
        }

        return $this;
    }

    /**
     * @param Property|string $name
     */
    public function getProperty($name): Property
    {
        if ($name instanceof Property) {
            $name = $name->getName();
        }
        if (!isset($this->properties[$name])) {
            throw new \InvalidArgumentException("Property '$name' not found.");
        }

        return $this->properties[$name];
    }

    public function getTraitResolutions(): array
    {
        $resolutions = [];

        foreach ($this->getTraits() as $i => $trait) {
            $resolutions[$i] = $trait->getResolutions();
        }

        return $resolutions;
    }

    /**
     * @return array<string, TraitUse>
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * @param string[]|TraitUse[] $names
     */
    public function setTraits(array $names): self
    {
        foreach ($names as $name) {
            $this->addTrait($name);
        }

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Argument must one of ' . implode(', ', self::TYPES));
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @return array<string, NamespaceUse>
     */
    public function getNamespaceUses(): array
    {
        return $this->namespaceUses;
    }

    /**
     * @return array<string, NamespaceUse>
     */
    public function getNamespaceUsesStrings(): array
    {
        return array_values(array_map(static fn(NamespaceUse $namespace) => (string)$namespace->getQualifiedName(), $this->namespaceUses));
    }

    /**
     * @param Constant[]|mixed[] $consts
     */
    public function setConstants(array $consts): self
    {
        $this->consts = [];
        foreach ($consts as $key => $value) {
            if (\is_string($key)) {
                $this->addConstant($key, $value);
            } else {
                $this->addConstant($value);
            }
        }

        return $this;
    }

    public function setResolveTypes(bool $resolveTypes = true): self
    {
        $this->resolveTypes = $resolveTypes;

        return $this;
    }

    public function setStrictTypes(bool $strictTypes): void
    {
        $this->strictTypes = $strictTypes;
    }

    /**
     * @param Constant|string $const
     * @param mixed $value
     */
    public function addConstant($const, $value = null): Constant
    {
        if (!$const instanceof Constant) {
            $const = new Constant($const);
            $const->setValue($value);
        }
        $const->setParent($this);
        $this->consts[$const->getName()] = $const;

        return $this->consts[$const->getName()];
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addExtend($name): self
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $this->extends[$name] = new QualifiedName($name);
        $this->extends[$name]->setParent($this);
        $this->extends[$name]->resolve();

        return $this;
    }

    public function addGetter(string $propertyName, string $prefix = null): Method
    {
        $property = $this->getProperty($propertyName);
        $prefix   ??= $property->getTypeHint() === Type::BOOL ? 'is' : 'get';
        $method   = new Method($prefix . ucfirst($propertyName));
        $method->setVisibility(self::VISIBILITY_PUBLIC);
        $method->addBody("return \$this->$propertyName;");
        $method->setTypes($property->getTypes());

        return $this->addMethod($method);
    }

    public function addSetter(string $propertyName, string $prefix = 'set'): Method
    {
        $property = $this->getProperty($propertyName);
        $method   = new Method($prefix . ucfirst($propertyName));
        $method->setVisibility(self::VISIBILITY_PUBLIC);
        $param = $method->addParameter($property->getName());
        $param->setTypes($property->getTypes());
        $value = $property->getValue();
        if ($value) {
            $param->setValue($property->getValue());
        }
        $method->addBody("\$this->$propertyName = \$$propertyName;");
        $method->addBody('return $this;');
        $method->addType(Type::SELF);

        return $this->addMethod($method);
    }

    /**
     * @param string[] $initProperties
     */
    public function addConstructor(array $initProperties = []): Method
    {
        $method = new Method('__construct');
        $this->addMethod($method);
        $method->initProperties($initProperties);

        return $method;
    }

    /**
     * @param string[] $initProperties
     */
    public function addStaticConstructor(string $name = 'create', array $initProperties = []): Method
    {
        $method = new Method($name);
        $method->setStatic();
        $method->addType(Type::SELF);
        $this->addMethod($method);
        $method->initProperties($initProperties);
        $method->addBody('return new self();');

        return $method;
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addImplement($name): self
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $this->implements[$name] = new QualifiedName($name);
        $this->implements[$name]->setParent($this);
        $this->implements[$name]->resolve();

        return $this;
    }

    public function addMember(Member $member): self
    {
        if ($member instanceof Method) {
            $this->addMethod($member);
        } elseif ($member instanceof Property) {
            $this->addProperty($member);
        } elseif ($member instanceof Constant) {
            $this->addConstant($member);
        } elseif ($member instanceof TraitUse) {
            $this->addTrait($member);
        }

        return $this;
    }

    /**
     * @param Method|string $method
     */
    public function addMethod($method): Method
    {
        if (!$method instanceof Method) {
            $method = new Method($method);
        }
        if ($this->type === self::TYPE_INTERFACE) {
            $method->setBody(null);
            $method->setVisibility(self::VISIBILITY_PUBLIC);
        }
        $method->setParent($this);
        $this->methods[$method->getName()] = $method;
        $method->resolve();

        return $this->methods[$method->getName()];
    }

    /**
     * @param string|Property $property
     */
    public function addProperty($property): Property
    {
        if (!$property instanceof Property) {
            $property = new Property($property);
        }
        $property->setParent($this);
        $this->properties[$property->getName()] = $property;
        $property->resolve();

        return $this->properties[$property->getName()];
    }

    /**
     * @param string|TraitUse $name
     */
    public function addTrait($name, array $resolutions = []): TraitUse
    {
        if ($name instanceof TraitUse) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $trait = new TraitUse($name);
        $trait->setResolutions($resolutions);
        $trait->setParent($this);
        $this->traits[$name] = $trait;
        $trait->resolve();

        return $this->traits[$name];
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addNamespaceUse($name, ?string $alias = null): NamespaceUse
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $name = ltrim($name, '\\');
        if (null === $alias && $this->name === PhpHelper::extractNamespace($name)) {
            $alias = PhpHelper::extractShortName($name);
        }
        if (isset($this->namespaceUses[$alias]) && (string)$this->namespaceUses[$alias] !== $name) {
            throw new \DomainException(
                "Alias '$alias' used already for '{$this->namespaceUses[$alias]}', cannot use for '{$name}'."
            );
        }
        $namespaceUse = new NamespaceUse($name, $alias);
        $namespaceUse->setParent($this);
        if ($alias) {
            $this->namespaceUses[$alias] = $namespaceUse;
        } else {
            $this->namespaceUses[$name] = $namespaceUse;
        }
        asort($this->namespaceUses);

        return $namespaceUse;
    }

    /**
     * @throws \DomainException
     */
    private function validate(): void
    {
        if ($this->abstract && $this->final) {
            throw new \DomainException('Class cannot be abstract and final.');
        }

        if (!$this->name && ($this->abstract || $this->final)) {
            throw new \DomainException('Anonymous class cannot be abstract or final.');
        }
    }

    private function validateName(string $name): void
    {
        if (!PhpHelper::isNamespaceIdentifier($name)) {
            throw new \InvalidArgumentException("Value '$name' is not valid class name.");
        }
    }
}
