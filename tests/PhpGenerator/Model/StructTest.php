<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionObject;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Helper\StubHelper;
use Sidux\PhpGenerator\Stub\B;
use Sidux\PhpGenerator\Stub\Class1;
use Sidux\PhpGenerator\Stub\Class2;
use Sidux\PhpGenerator\Stub\Class3;
use Sidux\PhpGenerator\Stub\Class4;
use Sidux\PhpGenerator\Stub\Class5;
use Sidux\PhpGenerator\Stub\Class6;
use Sidux\PhpGenerator\Stub\Class7;
use Sidux\PhpGenerator\Stub\Interface1;
use Sidux\PhpGenerator\Stub\Interface2;

final class StructTest extends TestCase
{

    /**
     * @test
     * @throws \ReflectionException
     */
    public function from(): void
    {
        $res = Struct::from(ReflectionClass::createFromName(\stdClass::class));
        Assert::assertInstanceOf(Struct::class, $res);
        Assert::assertSame('stdClass', $res->getName());


        Assert::assertException(
            static function () {
                Struct::from(
                    ReflectionObject::createFromInstance(
                        new class {
                        }
                    )
                );
            },
            \InvalidArgumentException::class
        );

        $res   = [];
        $res[] = Struct::from(Interface1::class);
        $res[] = Struct::from(Interface2::class);
        $res[] = Struct::from(Class1::class);
//        $res[]      = Struct::from(new Class2());
        $obj = new Class3();
        /**
         * @noinspection PhpUndefinedFieldInspection
         * @phpstan-ignore-next-line
         */
        $obj->prop2 = 1;
        $res[]      = Struct::from(ReflectionObject::createFromInstance($obj));
        $res[]      = Struct::from(Class4::class);
        $res[]      = Struct::from(Class5::class);
        $res[]      = Struct::from(Class6::class);

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.from.expect', implode("\n", $res));
    }

    /**
     * @test
     */
    public function all(): void
    {
        $class = new Struct('Example');

        Assert::assertFalse($class->isFinal());
        Assert::assertFalse($class->isAbstract());
        Assert::assertSame([], $class->getExtends());
        Assert::assertSame([], $class->getTraits());
        Assert::assertSame([], $class->getTraitResolutions());

        $class
            ->setAbstract()
            ->setTraits(['ObjectTrait'])
            ->addTrait('AnotherTrait', ['sayHello as protected'])
        ;
        $class->addExtend('ParentClass');
        $class->addImplement('IExample');
        $class->addImplement('IOne');
        $class->addComment("Description of class.\nThis is example\n")
              ->addComment('@property-read Sidux\Forms\Form $form')
              ->setConstants(['ROLE' => 'admin'])
              ->addConstant('ACTIVE', false)
        ;

        Assert::assertFalse($class->isFinal());
        Assert::assertTrue($class->isAbstract());
        Assert::assertSame('ParentClass', array_keys($class->getExtends())[0]);
        Assert::assertSame(['ObjectTrait', 'AnotherTrait'], array_keys($class->getTraits()));
        Assert::assertSame(['ObjectTrait' => [], 'AnotherTrait' => ['sayHello as protected']], $class->getTraitResolutions());
        Assert::assertCount(2, $class->getConstants());
        Assert::assertInstanceOf(Constant::class, $class->getConstants()['ROLE']);

        $class->addConstant('FORCE_ARRAY', new Value('Sidux\PhpGenerator\Helper\Json::FORCE_ARRAY'))
              ->setVisibility('private')
              ->addComment('Commented')
        ;

        $class->addProperty('handle')
              ->setVisibility('private')
              ->addComment('@var resource  orignal file handle')
        ;

        $class->addProperty('order')
              ->setValue(new Value('RecursiveIteratorIterator::SELF_FIRST'))
        ;

        $class->addProperty('typed1')
              ->addType('array')
        ;

        $class->addProperty('typed2')
              ->addType('?array')
              ->setInitialized()
        ;

        $p = $class->addProperty('sections')
                   ->setValue(['first' => true])
                   ->setStatic()
        ;

        $class->addConstructor(['typed2']);

        $class->addStaticConstructor();
        $class->addSetter('typed1');
        $class->addSetter('typed2');

        Assert::assertSame($p, $class->getProperty('sections'));
        Assert::assertTrue($class->hasProperty('sections'));
        Assert::assertFalse($class->hasProperty('unknown'));
        Assert::assertTrue($p->isStatic());
        Assert::assertSame(Struct::PRIVATE, $p->getVisibility());

        $m = $class->addMethod('getHandle')
                   ->addComment('Returns file handle.')
                   ->addComment('@return resource')
                   ->setFinal()
                   ->setBody('return $this->handle;')
        ;

        Assert::assertSame($m, $class->getMethod('getHandle'));
        Assert::assertTrue($class->hasMethod('getHandle'));
        Assert::assertFalse($class->hasMethod('unknown'));
        Assert::assertTrue($m->isFinal());
        Assert::assertFalse($m->isStatic());
        Assert::assertFalse($m->isAbstract());
        Assert::assertFalse($m->isReference());
        Assert::assertSame('public', $m->getVisibility());
        Assert::assertSame('return $this->handle;', $m->getBody());

        $m = $class->addMethod('getSections')
                   ->setStatic()
                   ->setVisibility('protected')
                   ->setReference()
                   ->addBody('$mode = 123;')
                   ->addBody('return self::$sections;')
        ;
        $m->addParameter('mode')->setValue(new Value('self::ORDER'));

        Assert::assertFalse($m->isFinal());
        Assert::assertTrue($m->isStatic());
        Assert::assertTrue($m->isReference());
        Assert::assertFalse($m->isNullable());
        Assert::assertEmpty($m->getTypes());
        Assert::assertSame('protected', $m->getVisibility());

        $method = $class->addMethod('show')
                        ->setAbstract()
        ;

        $method->addParameter('foo');
        $method->removeParameter('foo');

        $method->addParameter('item');

        $method->addParameter('res')
               ->setValue(null)
               ->setReference()
               ->addType('array')
        ;
        $class->setDefaultPropertyVisibility(Struct::PUBLIC);

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.expect', (string)$class);


        $methods = $class->getMethods();
        Assert::assertCount(7, $methods);
        $class->setMethods(array_values($methods));
        Assert::assertSame($methods, $class->getMethods());

        $properties = $class->getProperties();
        Assert::assertCount(5, $properties);
        $class->setProperties(array_values($properties));
        Assert::assertSame($properties, $class->getProperties());

        $parameters = $method->getParameters();
        Assert::assertCount(2, $parameters);
        $method->setParameters(array_values($parameters));
        Assert::assertSame($parameters, $method->getParameters());


        Assert::assertException(
            static function () {
                $class = new Struct('A');
                $class->addMethod('method')->setVisibility('unknown');
            },
            \InvalidArgumentException::class,
            'Argument must be public|protected|private.'
        );


        $class = new Struct('Example');
        $class->addConstant('a', 1);
        $class->addConstant('b', 1);
        $class->removeConstant('b')->removeConstant('c');

        Assert::assertSame(['a'], array_keys($class->getConstants()));

        $class->addProperty('a');
        $class->addProperty('b');
        $class->removeProperty('b')->removeProperty('c');

        Assert::assertSame(['a'], array_keys($class->getProperties()));

        $class->addMethod('a');
        $class->addMethod('b');
        $class->removeMethod('b')->removeMethod('c');

        Assert::assertSame(['a'], array_keys($class->getMethods()));

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.from.74.expect', (string)Struct::from(new Class7()));
    }

    /**
     * @test
     */
    public function validate(): void
    {
        Assert::assertException(
            static function () {
                $class = new Struct('A');
                $class->setFinal()->setAbstract();
                $class->__toString();
            },
            \DomainException::class,
            'Class cannot be abstract and final.'
        );

        Assert::assertException(
            static function () {
                $class = new Struct('A');
                $class->setAbstract()->setFinal();
                $class->__toString();
            },
            \DomainException::class,
            'Class cannot be abstract and final.'
        );

        Assert::assertException(
            static function () {
                new Struct('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new Struct('*');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new Struct('abc abc');
            },
            \InvalidArgumentException::class
        );

        $class = new Struct('Abc');
        Assert::assertException(
            static function () use ($class) {
                $class->setExtends(['*']);
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->setExtends(['A', '*']);
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->addExtend('*');
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->setImplements(['A', '*']);
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->addImplement('*');
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->setTraits(['A', '*']);
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );

        Assert::assertException(
            static function () use ($class) {
                $class->addTrait('*');
            },
            \InvalidArgumentException::class,
            "Value '*' is not valid class name."
        );
    }

    /**
     * @test
     */
    public function createInterface(): void
    {
        $interface = Struct::create('IExample')
                           ->setType('interface')
                           ->addComment('Description of interface')
        ;
        $interface->addExtend('IOne');
        $interface->addExtend('ITwo');

        Assert::assertSame(['IOne', 'ITwo'], array_keys($interface->getExtends()));

        $interface->addMethod('getForm');

        Assert::assertSame(StubHelper::load('IExample', false), (string)$interface);
    }

    /**
     * @test
     */
    public function fromClassWithExtend(): void
    {
        Assert::assertStringEqualsFile(
            __DIR__ . '/../Expected/ClassType.inheritance.expect',
            (string)Struct::from(B::class)
        );
    }

    /**
     * @test
     */
    public function createClone(): void
    {
        $class = new Struct('Example');

        $class->addConstant('A', 10);
        $class->addProperty('a');
        $class->addMethod('a');

        $dolly = clone $class;

        Assert::assertNotSame($dolly->getConstants(), $class->getConstants());
        Assert::assertNotSame($dolly->getProperty('a'), $class->getProperty('a'));
        Assert::assertNotSame($dolly->getMethod('a'), $class->getMethod('a'));
    }

    /**
     * @test
     */
    public function addMember(): void
    {
        Assert::assertError(
            static function () {
                /**
                 * @noinspection PhpParamsInspection
                 * @phpstan-ignore-next-line
                 */
                (new Struct('Example'))
                    ->addMember(new \stdClass());
            },
            \TypeError::class
        );

        $class = Struct::create('Example')
                       ->addMember(
                           $method = Method::create('getHandle')
                                           ->addType(Class1::class)
                       )
                       ->addMember(
                           $property = Property::create('handle')
                                               ->addType(\TypeError::class)
                       )
                       ->addMember($const = Constant::create('ROLE'))
        ;

        Assert::assertSame(['getHandle' => $method], $class->getMethods());
        Assert::assertSame(['handle' => $property], $class->getProperties());
        Assert::assertSame(['ROLE' => $const], $class->getConstants());
        Assert::assertSame('', $method->getBody());
        Assert::assertSame([Class1::class], $class->getNamespaceUsesStrings());

        Struct::create('Example')
              ->setType('interface')
              ->addMember($method = new Method('getHandle'))
        ;

        Assert::assertNull($method->getBody());
    }

    /**
     * @test
     */
    public function resolve(): void
    {
        $class = new Struct('A\\B');
        $class->addExtend(Class4::class);
        $class->addImplement(Interface1::class);
        $constructor = $class->addConstructor();
        $constructor->initProperty(Property::create('toto')->addType(Class5::class));
        $class->addMethod('execute')
              ->addType(Class2::class)
              ->addParameter('request')
              ->addType(Class3::class)
        ;

        Assert::assertExpect('Resolved.expect', $class);
    }

    /**
     * @test
     */
    public function generate82Class(): void
    {
        $class = new Struct('Sidux\PhpGenerator\Stub\RequestClass');
        $class->setReadOnly(true);
        $class->addNamespaceUse('Sidux\PhpGenerator\Stub\PropertyTwo');

        $objectClass = new Struct('Sidux\PhpGenerator\Stub\PropertyTwo');
        $constructor = $class->addConstructor();

        $constructor
            ->addPromotedParameter(PromotedParameter::create('propertyOne')->addType('string'))
            ->addPromotedParameter(PromotedParameter::create('propertyTwo')->addType($objectClass))
            ->addPromotedParameter(PromotedParameter::create('propertyThree')->addTypes(['string', 'array', 'null']))
            ->addPromotedParameter(PromotedParameter::create('propertyFour')->addTypes(['string', 'null'])->setValue(null))
        ;

        Assert::assertExpect('ClassType.from.82.expect', $class);
    }

    /**
     * @test
     */
    public function generatePhp82EnumClass(): void
    {
        $class = new Struct('Sidux\PhpGenerator\Stub\Gender');
        $class->setType('enum');
        $class->setCases(['M' => 'M', 'F' => 'F', 'N' => 'N', 'U' => 'U']);

        Assert::assertExpect('EnumType.from.82.expect', $class);
    }
}
