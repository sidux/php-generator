<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Helper\StubHelper;
use Sidux\PhpGenerator\Stub\D;
use Sidux\PhpGenerator\Stub\InterfaceStub1;
use Sidux\PhpGenerator\Stub\SubNamespace\Foo;
use Sidux\PhpGenerator\Stub\Variadics;

final class PhpMethodTest extends TestCase
{

    /**
     * @test
     * @throws \ReflectionException
     */
    public function from(): void
    {
        $res = PhpMethod::from(ReflectionMethod::createFromName(self::class, 'from'));
        Assert::assertInstanceOf(PhpMethod::class, $res);
        Assert::assertSame('from', $res->getName());

        $res = PhpMethod::from(ReflectionFunction::createFromName('trim'));
        Assert::assertInstanceOf(PhpMethod::class, $res);
        Assert::assertSame('trim', $res->getName());

        Assert::assertException(
            static function () {
                PhpMethod::from(
                    ReflectionFunction::createFromClosure(
                        static function () {
                        }
                    )
                );
            },
            \InvalidArgumentException::class
        );
    }

    /**
     * @test
     */
    public function validate(): void
    {
        Assert::assertException(
            static function () {
                PhpMethod::create('foo')
                         ->setFinal()
                         ->setAbstract()
                         ->validate()
                ;
            },
            \DomainException::class
        );

        Assert::assertException(
            static function () {
                PhpMethod::create('foo')
                         ->setAbstract(true)
                         ->setVisibility(PhpStruct::VISIBILITY_PRIVATE)
                         ->validate()
                ;
            },
            \DomainException::class
        );

        $method = new PhpMethod('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $method->getName());

        Assert::assertException(
            static function () {
                new PhpMethod('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertError(
            static function () {
                new PhpMethod(null); // @phpstan-ignore-line
            },
            \TypeError::class
        );

        Assert::assertException(
            static function () {
                new PhpMethod('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new PhpMethod('*');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                PhpMethod::from('foo::bar::toto');
            },
            \InvalidArgumentException::class
        );
    }

    /**
     * @test
     */
    public function scalarParameters(): void
    {
        $method = PhpMethod::from(InterfaceStub1::class . '::scalars');
        Assert::assertSame('string', (string)$method->getParameters()['a']->getTypeHint());
        Assert::assertSame('bool', (string)$method->getParameters()['b']->getTypeHint());
        Assert::assertSame('int', (string)$method->getParameters()['c']->getTypeHint());
        Assert::assertSame('float', (string)$method->getParameters()['d']->getTypeHint());

        $method = PhpMethod::create('functionStub3')
                           ->setBody('return null;')
        ;

        $method->addParameter('a')->addType('string');
        $method->addParameter('b')->addType('bool');

        Assert::assertSame(
            StubHelper::load('functionStub3'),
            (string)$method
        );
    }

    /**
     * @test
     */
    public function variadics(): void
    {
        $method = PhpMethod::from(Variadics::class . '::foo');
        Assert::assertTrue($method->isVariadic());

        $method = PhpMethod::from(Variadics::class . '::bar');
        Assert::assertTrue($method->isVariadic());
        Assert::assertTrue($method->getParameters()['bar']->isReference());
        Assert::assertSame('array', (string)$method->getParameters()['bar']->getTypeHint());

        $method = PhpMethod::create('variadic')
                           ->setVariadic(true)
                           ->setBody('return 42;')
        ;

        Assert::assertSame(
            'function variadic()
{
    return 42;
}
',
            (string)$method
        );


        $method = PhpMethod::create('variadic')
                           ->setVariadic(true)
                           ->setBody('return 42;')
        ;
        $method->addParameter('foo');

        Assert::assertSame(
            'function variadic(...$foo)
{
    return 42;
}
',
            (string)$method
        );


        $method = PhpMethod::create('variadic')
                           ->setVariadic(true)
                           ->setBody('return 42;')
        ;
        $method->addParameter('foo');
        $method->addParameter('bar');
        $method->addParameter('baz')->setValue([]);

        Assert::assertSame(
            'function variadic($foo, $bar, array ...$baz)
{
    return 42;
}
',
            (string)$method
        );


        $method = PhpMethod::create('variadic')
                           ->setVariadic(true)
                           ->setBody('return 42;')
        ;
        $method->addParameter('foo')->addType('array');

        Assert::assertSame(
            'function variadic(array ...$foo)
{
    return 42;
}
',
            (string)$method
        );


        $method = PhpMethod::create('variadic')
                           ->setVariadic(true)
                           ->setBody('return 42;')
        ;
        $method->addParameter('foo')->addType('array')->setReference(true);

        Assert::assertSame(
            'function variadic(array &...$foo)
{
    return 42;
}
',
            (string)$method
        );
    }

    /**
     * @test
     */
    public function returnTypes(): void
    {
        $method = PhpMethod::from(D::class . '::testClass');
        Assert::assertSame(Foo::class, (string)$method->getTypeHint());

        $method = PhpMethod::from(D::class . '::testScalar');
        Assert::assertSame('string', (string)$method->getTypeHint());

        $method = PhpMethod::create('create')
                           ->addType('Foo')
                           ->setBody('return new Foo();')
        ;

        Assert::assertSame(
            'function create(): Foo
{
    return new Foo();
}
',
            (string)$method
        );
    }

    /**
     * @test
     */
    public function fromName(): void
    {
        $content  = StubHelper::load('functionStub1');
        $function = PhpMethod::from('functionStub1');
        Assert::assertSame($content, (string)$function);

    }
}
