<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionObject;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Helper\StubHelper;
use Sidux\PhpGenerator\Stub\B;
use Sidux\PhpGenerator\Stub\Class1;
use Sidux\PhpGenerator\Stub\Class3;
use Sidux\PhpGenerator\Stub\Class4;
use Sidux\PhpGenerator\Stub\Class5;
use Sidux\PhpGenerator\Stub\Class6;
use Sidux\PhpGenerator\Stub\Class7;
use Sidux\PhpGenerator\Stub\Interface1;
use Sidux\PhpGenerator\Stub\Interface2;

final class PhpStructTest extends TestCase
{

    /**
     * @test
     * @throws \ReflectionException
     */
    public function from(): void
    {
        $res = PhpStruct::from(ReflectionClass::createFromName(\stdClass::class));
        Assert::assertInstanceOf(PhpStruct::class, $res);
        Assert::assertSame('stdClass', $res->getName());


        Assert::assertException(
            static function () {
                PhpStruct::from(
                    ReflectionObject::createFromInstance(
                        new class {
                        }
                    )
                );
            },
            \InvalidArgumentException::class
        );

        $res   = [];
        $res[] = PhpStruct::from(Interface1::class);
        $res[] = PhpStruct::from(Interface2::class);
        $res[] = PhpStruct::from(Class1::class);
//        $res[]      = PhpStruct::from(new Class2());
        $obj = new Class3();
        /**
         * @noinspection PhpUndefinedFieldInspection
         * @phpstan-ignore-next-line
         */
        $obj->prop2 = 1;
        $res[]      = PhpStruct::from(ReflectionObject::createFromInstance($obj));
        $res[]      = PhpStruct::from(Class4::class);
        $res[]      = PhpStruct::from(Class5::class);
        $res[]      = PhpStruct::from(Class6::class);

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.from.expect', implode("\n", $res));
    }

    /**
     * @test
     */
    public function all(): void
    {
        $class = new PhpStruct('Example');

        Assert::assertFalse($class->isFinal());
        Assert::assertFalse($class->isAbstract());
        Assert::assertSame([], $class->getExtends());
        Assert::assertSame([], $class->getTraits());
        Assert::assertSame([], $class->getTraitResolutions());

        $class
            ->setAbstract(true)
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
        Assert::assertInstanceOf(PhpConstant::class, $class->getConstants()['ROLE']);

        $class->addConstant('FORCE_ARRAY', new PhpValue('Sidux\PhpGenerator\Helper\Json::FORCE_ARRAY'))
              ->setVisibility('private')
              ->addComment('Commented')
        ;

        $class->addProperty('handle')
              ->setVisibility('private')
              ->addComment('@var resource  orignal file handle')
        ;

        $class->addProperty('order')
              ->setValue(new PhpValue('RecursiveIteratorIterator::SELF_FIRST'))
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
                   ->setStatic(true)
        ;

        $class->addStaticConstructor();

        Assert::assertSame($p, $class->getProperty('sections'));
        Assert::assertTrue($class->hasProperty('sections'));
        Assert::assertFalse($class->hasProperty('unknown'));
        Assert::assertTrue($p->isStatic());
        Assert::assertSame(PhpStruct::VISIBILITY_PUBLIC, $p->getVisibility());

        $m = $class->addMethod('getHandle')
                   ->addComment('Returns file handle.')
                   ->addComment('@return resource')
                   ->setFinal(true)
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
                   ->setStatic(true)
                   ->setVisibility('protected')
                   ->setReference(true)
                   ->addBody('$mode = 123;')
                   ->addBody('return self::$sections;')
        ;
        $m->addParameter('mode')->setValue(new PhpValue('self::ORDER'));

        Assert::assertFalse($m->isFinal());
        Assert::assertTrue($m->isStatic());
        Assert::assertTrue($m->isReference());
        Assert::assertFalse($m->isNullable());
        Assert::assertEmpty($m->getTypes());
        Assert::assertSame('protected', $m->getVisibility());

        $method = $class->addMethod('show')
                        ->setAbstract(true)
        ;

        $method->addParameter('foo');
        $method->removeParameter('foo');

        $method->addParameter('item');

        $method->addParameter('res')
               ->setValue(null)
               ->setReference(true)
               ->addType('array')
        ;

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.expect', (string)$class);


        $methods = $class->getMethods();
        Assert::assertCount(4, $methods);
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
                $class = new PhpStruct('A');
                $class->addMethod('method')->setVisibility('unknown');
            },
            \InvalidArgumentException::class,
            'Argument must be public|protected|private.'
        );


        $class = new PhpStruct('Example');
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

        Assert::assertStringEqualsFile(__DIR__ . '/../Expected/ClassType.from.74.expect', (string)PhpStruct::from(new Class7()));
    }

    /**
     * @test
     */
    public function validate(): void
    {
        Assert::assertException(
            static function () {
                $class = new PhpStruct('A');
                $class->setFinal(true)->setAbstract(true);
                $class->__toString();
            },
            \DomainException::class,
            'Class cannot be abstract and final.'
        );

        Assert::assertException(
            static function () {
                $class = new PhpStruct('A');
                $class->setAbstract(true)->setFinal(true);
                $class->__toString();
            },
            \DomainException::class,
            'Class cannot be abstract and final.'
        );

        Assert::assertException(
            static function () {
                new PhpStruct('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new PhpStruct('*');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new PhpStruct('abc abc');
            },
            \InvalidArgumentException::class
        );

        $class = new PhpStruct('Abc');
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
        $interface = PhpStruct::create('IExample')
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
            (string)PhpStruct::from(B::class)
        );
    }

    /**
     * @test
     */
    public function createClone(): void
    {
        $class = new PhpStruct('Example');

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
                (new PhpStruct('Example'))
                    ->addMember(new \stdClass());
            },
            \TypeError::class
        );


        $class = PhpStruct::create('Example')
                          ->addMember($method = new PhpMethod('getHandle'))
                          ->addMember($property = new PhpProperty('handle'))
                          ->addMember($const = new PhpConstant('ROLE'))
        ;

        Assert::assertSame(['getHandle' => $method], $class->getMethods());
        Assert::assertSame(['handle' => $property], $class->getProperties());
        Assert::assertSame(['ROLE' => $const], $class->getConstants());
        Assert::assertSame('', $method->getBody());


        PhpStruct::create('Example')
                 ->setType('interface')
                 ->addMember($method = new PhpMethod('getHandle'))
        ;

        Assert::assertNull($method->getBody());
    }
}
