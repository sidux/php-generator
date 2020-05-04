<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;

class PhpParameterTest extends TestCase
{
    public function invalidNames(): iterable
    {
        yield [null, \TypeError::class];
        yield ['', \InvalidArgumentException::class];
        yield ['*', \InvalidArgumentException::class];
        yield ['$test', \InvalidArgumentException::class];
    }

    /**
     * @test
     * @dataProvider invalidNames
     */
    public function validateShouldThrowException(?string $name, string $exception): void
    {
        $this->expectException($exception);
        new PhpParameter($name);
    }

    /**
     * @test
     */
    public function validateShouldPass(): void
    {
        $parameter = new PhpParameter('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $parameter->getName());
    }

    /**
     * @test
     */
    public function dump(): void
    {
        $param = PhpParameter::create('toto');
        Assert::assertSame('$toto', (string)$param);

        $param->setValue(null);
        Assert::assertSame('$toto = null', (string)$param);

        $param->addType('string');
        Assert::assertSame('?string $toto = null', (string)$param);

        $param->addType(TestCase::class);
        Assert::assertSame('$toto = null', (string)$param);

        $param->setInitialized(false);
        $param->setTypes(['iterable', TestCase::class . '[]']);
        Assert::assertSame('iterable $toto', (string)$param);

        $param->setReference();
        Assert::assertSame('iterable &$toto', (string)$param);

        $param->setValue(null);
        Assert::assertSame('?iterable &$toto = null', (string)$param);

        $param->removeValue();
        Assert::assertSame('?iterable &$toto', (string)$param);

        $param->removeType('null');
        Assert::assertSame('iterable &$toto', (string)$param);
    }
}
