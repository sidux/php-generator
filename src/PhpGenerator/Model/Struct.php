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

/**
 * @method static self from(ReflectionClass|ReflectionObject|string|object $from)
 */
final class Struct implements NamespaceAware, Element
{
    use Part\CommentAwareTrait;
    use Part\NamespaceAwareTrait;
    use Part\FinalAwareTrait;
    use Part\AbstractAwareTrait;
    use Part\ReadOnlyAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;

    public const TYPES = [
        Struct::_CLASS,
        Struct::_INTERFACE,
        Struct::_TRAIT,
        Struct::_ENUM
    ];

    public const
        _CLASS = 'class',
        _INTERFACE = 'interface',
        _TRAIT = 'trait',
        _ENUM = 'enum';

    public const VISIBILITIES = [
        Struct::PUBLIC,
        Struct::PROTECTED,
        Struct::PRIVATE,
    ];

    public const
        PUBLIC = 'public',
        PROTECTED = 'protected',
        PRIVATE = 'private';

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
    private string $type = self::_CLASS;

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

    /**
     * @psalm-var value-of<Type::BACKED_ENUM>
     */
    private ?string $enumType = null;

    /**
     * @var array<EnumCase>
     */
    private array $cases = [];

    public function __clone()
    {
        $clone = static fn($item) => clone $item;

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
        $output .= $this->isReadOnly() ? 'readonly ' : null;
        $output .= $this->type . ' ';
        $output .= $this->getName();
        $output .= $this->isBackedEnum() ? ": $this->enumType" : null;
        $output .= $this->getExtends() ? ' extends ' . implode(', ', $this->getExtends()) : null;
        $output .= $this->getImplements() ? ' implements ' . implode(', ', $this->getImplements()) : null;
        $output .= "\n{\n";

        $members = array_filter(
            [
                implode('', $this->getTraits()),
                implode("\n", $this->getConstants()),
                implode("\n", $this->getProperties()),
                implode("\n", $this->getMethods()),
                implode("\n", $this->getCases()),
            ]
        );

        if (\count($this->getCases()) > 0) {
            $lastCase = array_pop($members);
            $members[] = $lastCase . "\n";
        }

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
            $class->setType(self::_TRAIT);
        } elseif ($from->isInterface()) {
            $class->setType(self::_INTERFACE);
        } else {
            $class->setType($class::_CLASS);
        }
        $class->setFinal($from->isFinal() && $class->getType() === $class::_CLASS);
        $class->setAbstract($from->isAbstract() && $class->getType() === $class::_CLASS);

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
     * @return array<string, Constant>
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
     * @param string[]|NamespaceAware[] $parents
     */
    public function setExtends(array $parents): self
    {
        foreach ($parents as $parent) {
            $this->addExtend($parent);
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
     * @return array<int, string>
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
     */
    public function addConstant($const, mixed $value = null): Constant
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
     * @param string|NamespaceAware $parent
     */
    public function addExtend($parent): self
    {
        if ($parent instanceof NamespaceAware) {
            $parent = $parent->getQualifiedName();
        }
        $this->validateName($parent);
        $this->extends[$parent] = new QualifiedName($parent);
        $this->extends[$parent]->setParent($this);
        $this->extends[$parent]->resolve();

        return $this;
    }

    public function addGetter(string $propertyName, string $prefix = null): Method
    {
        $property = $this->getProperty($propertyName);
        $prefix   ??= $property->getTypeHint() === Type::BOOL ? 'is' : 'get';
        $method   = new Method($prefix . ucfirst($propertyName));
        $method->setVisibility(self::PUBLIC);
        $method->addBody("return \$this->$propertyName;");
        $method->setTypes($property->getTypes());

        return $this->addMethod($method);
    }

    public function addSetter(string $propertyName, string $prefix = 'set'): Method
    {
        $property = $this->getProperty($propertyName);
        $method   = new Method($prefix . ucfirst($propertyName));
        $method->setVisibility(self::PUBLIC);
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
     * @param string|NamespaceAware $interface
     */
    public function addImplement($interface): self
    {
        if ($interface instanceof NamespaceAware) {
            $interface = $interface->getQualifiedName();
        }
        $this->validateName($interface);
        $this->implements[$interface] = new QualifiedName($interface);
        $this->implements[$interface]->setParent($this);
        $this->implements[$interface]->resolve();

        return $this;
    }

    public function setMembers(Member ...$members): self
    {
        foreach ($members as $member) {
            $this->addMember($member);
        }

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
        if ($this->type === self::_INTERFACE) {
            $method->setBody(null);
            $method->setVisibility(self::PUBLIC);
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

    public function isBackedEnum(): bool
    {
        return isset($this->enumType);
    }

    public function getCases(): array
    {
        return $this->cases;
    }

    /**
     * @psalm-param value-of<Type::BACKED_ENUM> $type
     */
    public function setEnumType(string $type): void
    {
        if (!in_array($type, Type::BACKED_ENUM, true)) {
            throw new \InvalidArgumentException("Invalid backed enum type '$type'.");
        }

        $this->enumType = $type;
    }

    public function setCases(array $cases): void
    {
        $firstCase = reset($cases);
        $enumType = gettype($firstCase);

        $enumCases = [];

        foreach ($cases as $name => $value) {
            if (gettype($value) !== $enumType) {
                throw new \InvalidArgumentException("All cases of the enum must be of the same type.");
            }

            $enumCase = EnumCase::create($name);
            $enumCase->setValue($value);

            $enumCases[] = $enumCase;
        }

        $this->setEnumType($enumType);
        $this->cases = $enumCases;
    }
}
