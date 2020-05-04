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
use Sidux\PhpGenerator\Model\Contract\NamespaceAware;
use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Part;

/**
 * @method static self from(ReflectionClass|ReflectionObject|string|object $from)
 */
final class PhpStruct implements NamespaceAware
{
    use Part\CommentAwareTrait;
    use Part\NamespaceAwareTrait;
    use Part\FinalAwareTrait;
    use Part\AbstractAwareTrait;
    use Helper\Traits\MethodOverloadAwareTrait;
    use Helper\Traits\StaticCreateAwareTrait;

    public const TYPES = [
        PhpStruct::TYPE_CLASS,
        PhpStruct::TYPE_INTERFACE,
        PhpStruct::TYPE_TRAIT,
    ];

    public const
        TYPE_CLASS = 'class',
        TYPE_INTERFACE = 'interface',
        TYPE_TRAIT = 'trait';

    public const VISIBILITIES = [
        PhpStruct::VISIBILITY_PUBLIC,
        PhpStruct::VISIBILITY_PROTECTED,
        PhpStruct::VISIBILITY_PRIVATE,
    ];

    public const
        VISIBILITY_PUBLIC = 'public',
        VISIBILITY_PROTECTED = 'protected',
        VISIBILITY_PRIVATE = 'private';

    /**
     * @var array<string, PhpConstant>
     */
    private array $consts = [];

    /**
     * @var array<string, PhpName>
     */
    private array $extends = [];

    /**
     * @var array<string, PhpName>
     */
    private array $implements = [];

    /**
     * @var array<string, PhpMethod>
     */
    private array $methods = [];

    /**
     * @var array<string, PhpProperty>
     */
    private array $properties = [];

    private bool $resolveTypes = true;

    private bool $strictTypes = true;

    /**
     * @var array<string, PhpTraitUse>
     */
    private array $traits = [];

    /**
     * @psalm-var value-of<PhpStruct::TYPES>
     */
    private string $type = self::TYPE_CLASS;

    /**
     * @var array<string, PhpUse>
     */
    private array $uses = [];

    public function __clone()
    {
        $clone = static function ($item) {
            return clone $item;
        };

        $this->uses       = array_map($clone, $this->uses);
        $this->traits     = array_map($clone, $this->traits);
        $this->consts     = array_map($clone, $this->consts);
        $this->properties = array_map($clone, $this->properties);
        $this->methods    = array_map($clone, $this->methods);
    }

    public function __toString(): string
    {
        $this->validate();

        $output = "<?php\n\n";

        $output .= $this->hasStrictTypes() ? "declare(strict_types=1);\n\n" : null;
        $output .= $this->hasNamespace() ? "namespace $this->namespace;\n\n" : null;
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

    public static function fromFile(string $fileName): self
    {
        $astLocator = (new BetterReflection())->astLocator();
        $reflector  = new ClassReflector(new SingleFileSourceLocator($fileName, $astLocator));
        $classes    = $reflector->getAllClasses();
        if (!$classes) {
            throw new \InvalidArgumentException("No class found in file $fileName");
        }
        if (count($classes) > 1) {
            throw new \InvalidArgumentException("Multiple classes found in file $fileName");
        }

        return static::fromReflectionClass($classes[0]);
    }

    public static function fromObject(object $object): self
    {
        $from = ReflectionClass::createFromInstance($object);

        return static::fromReflectionClass($from);
    }

    public static function fromReflectionClass(ReflectionClass $from): self
    {
        if ($from->isAnonymous()) {
            throw new \InvalidArgumentException('Anonymous classes are not supported.');
        }
        $class = new self($from->getName());

        $class->setComment($from->getDocComment());

        if ($from->isTrait()) {
            $class->setType(static::TYPE_TRAIT);
        } elseif ($from->isInterface()) {
            $class->setType(static::TYPE_INTERFACE);
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

        if ($from->getParentClass()) {
            $class->setExtends([$from->getParentClass() ? $from->getParentClass()->getName() : null]);
        }

        $props = [];
        foreach ($from->getImmediateProperties() as $prop) {
            if ($prop->isDefault()) {
                $props[] = PhpProperty::from($prop);
            }
        }
        $class->setProperties($props);

        $methods = [];
        foreach ($from->getImmediateMethods() as $method) {
            $methods[] = PhpMethod::from($method);
        }
        $class->setMethods($methods);
        $class->setConstants($from->getImmediateConstants());

        return $class;
    }

    public static function fromReflectionObject(ReflectionObject $from): self
    {
        return static::fromReflectionClass($from);
    }

    public static function fromString(string $className): self
    {
        $from = ReflectionClass::createFromName($className);

        return static::fromReflectionClass($from);
    }

    public function hasMethod(string $name): bool
    {
        return isset($this->methods[$name]);
    }

    public function hasProperty(string $name): bool
    {
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
     * @return PhpConstant[]
     */
    public function getConstants(): array
    {
        return $this->consts;
    }

    /**
     * @return array<string, PhpName>
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
     * @return array<string, PhpName>
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

    public function getMethod(string $name): PhpMethod
    {
        if (!isset($this->methods[$name])) {
            throw new \InvalidArgumentException("PhpMethod '$name' not found.");
        }

        return $this->methods[$name];
    }

    /**
     * @return array<string, PhpMethod>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param PhpMethod[]|string[] $methods
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
     * @return array<string, PhpProperty>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param PhpProperty[]|string[] $properties
     */
    public function setProperties(array $properties): self
    {
        $this->properties = [];
        foreach ($properties as $value) {
            $this->addProperty($value);
        }

        return $this;
    }

    public function getProperty(string $name): PhpProperty
    {
        if (!isset($this->properties[$name])) {
            throw new \InvalidArgumentException("PhpProperty '$name' not found.");
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
     * @return array<string, PhpTraitUse>
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * @param string[]|PhpTraitUse[] $names
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
     * @return array<string, PhpUse>
     */
    public function getUses(): array
    {
        return $this->uses;
    }

    /**
     * @param PhpConstant[]|mixed[] $consts
     */
    public function setConstants(array $consts): self
    {
        $this->consts = [];
        foreach ($consts as $key => $value) {
            if (is_string($key)) {
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
     * @param PhpConstant|string $const
     * @param mixed $value
     */
    public function addConstant($const, $value = null): PhpConstant
    {
        if (!$const instanceof PhpConstant) {
            $const = new PhpConstant($const);
            $const->setValue($value);
        }
        $const->setParent($this);
        $this->consts[$const->getName()] = $const;

        return $this->consts[$const->getName()];
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addExtend($name): PhpName
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $this->extends[$name] = new PhpName($name);
        $this->extends[$name]->setParent($this);

        return $this->extends[$name];
    }

    public function addGetter(string $propertyName): PhpMethod
    {
        $property = $this->getProperty($propertyName);
        $prefix   = $property->getTypeHint() === PhpType::BOOL ? 'is' : 'get';
        $method   = new PhpMethod($prefix . ucfirst($propertyName));
        $method->setVisibility(self::VISIBILITY_PUBLIC);
        $method->addBody("return \$this->$propertyName;");
        $method->setTypes($property->getTypes());

        return $this->addMethod($method);
    }

    public function addSetter(string $propertyName): PhpMethod
    {
        $property = $this->getProperty($propertyName);
        $prefix   = 'set';
        $method   = new PhpMethod($prefix . ucfirst($propertyName));
        $method->setVisibility(self::VISIBILITY_PUBLIC);
        $method->addParameter($property->getName())
               ->setValue($property->getValue())
               ->setTypes($property->getTypes())
        ;
        $method->addBody("\$this->$propertyName = $propertyName;");
        $method->addBody("return \$this;");
        $method->addType(PhpType::SELF);

        return $this->addMethod($method);
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addImplement($name): PhpName
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $this->implements[$name] = new PhpName($name);
        $this->implements[$name]->setParent($this);

        return $this->implements[$name];
    }

    public function addMember(PhpMember $member): self
    {
        if ($member instanceof PhpMethod) {
            $this->addMethod($member);
        } elseif ($member instanceof PhpProperty) {
            $this->addProperty($member);
        } elseif ($member instanceof PhpConstant) {
            $this->addConstant($member);
        } elseif ($member instanceof PhpTraitUse) {
            $this->addTrait($member);
        }

        return $this;
    }

    /**
     * @param PhpMethod|string $method
     */
    public function addMethod($method): PhpMethod
    {
        if (!$method instanceof PhpMethod) {
            $method = new PhpMethod($method);
        }
        if ($this->type === self::TYPE_INTERFACE) {
            $method->setBody(null);
            $method->setVisibility(self::VISIBILITY_PUBLIC);
        }
        $method->setParent($this);

        $this->methods[$method->getName()] = $method;

        return $this->methods[$method->getName()];
    }

    /**
     * @param string|PhpProperty $property
     */
    public function addProperty($property): PhpProperty
    {
        if (!$property instanceof PhpProperty) {
            $property = new PhpProperty($property);
        }
        $property->setParent($this);

        $this->properties[$property->getName()] = $property;

        return $this->properties[$property->getName()];
    }

    /**
     * @param string|PhpTraitUse $name
     */
    public function addTrait($name, array $resolutions = []): PhpTraitUse
    {
        if ($name instanceof PhpTraitUse) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $trait = new PhpTraitUse($name);
        $trait->setResolutions($resolutions);
        $trait->setParent($this);
        $this->traits[$name] = $trait;

        return $this->traits[$name];
    }

    /**
     * @param string|NamespaceAware $name
     */
    public function addUse($name, string $alias = null): PhpUse
    {
        if ($name instanceof NamespaceAware) {
            $name = $name->getQualifiedName();
        }
        $this->validateName($name);
        $name = ltrim($name, '\\');
        if ($alias === null && $this->name === PhpHelper::extractNamespace($name)) {
            $alias = PhpHelper::extractShortName($name);
        }
        if ($alias === null) {
            $path  = explode('\\', $name);
            $count = null;
            do {
                if (empty($path)) {
                    $count++;
                } else {
                    $alias = array_pop($path) . $alias;
                }
                $index = $alias . $count;
            } while (isset($this->uses[$index]) && $this->uses[$index] !== $name);
            $alias .= $count;
        } elseif (isset($this->uses[$alias]) && $this->uses[$alias] !== $name) {
            throw new \DomainException(
                "Alias '$alias' used already for '{$this->uses[$alias]}', cannot use for '{$name}'."
            );
        }

        $this->uses[$alias] = new PhpUse($name, $alias);
        $this->uses[$alias]->setParent($this);
        asort($this->uses);

        return $this->uses[$alias];
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
