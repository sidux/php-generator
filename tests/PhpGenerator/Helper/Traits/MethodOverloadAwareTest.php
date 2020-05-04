<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper\Traits;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Stub\Class2;

class MethodOverloadAwareTest extends TestCase
{
    use MethodOverloadAwareTrait;

    public function shouldThrowException(): void
    {
        self::buildMethodNameWithArgsTypes('from', ['test', true]);
    }

    public function testArgs(): void
    {
        $methodName = self::buildMethodNameWithArgsTypes('from', ['test']);
        Assert::assertSame('fromString', $methodName);

        $methodName = self::buildMethodNameWithArgsTypes('blob', ['test']);
        Assert::assertSame('blobFromString', $methodName);

        $methodName = self::buildMethodNameWithArgsTypes('from', ['test', 2]);
        Assert::assertSame('fromStringAndInteger', $methodName);

        $methodName = self::buildMethodNameWithArgsTypes('from', [new Class2(), new Class2()]);
        Assert::assertSame('fromClass1AndInterface2', $methodName);

        $methodName = self::buildMethodNameWithArgsTypes('from', [new Class2(), new Class2(), new Class2()]);
        Assert::assertSame('fromClass1AndInterface2AndClass2', $methodName);
    }

    public function fromStringAndInteger(): void
    {
    }

    public function fromString(): void
    {
    }

    public function blobFromString(): void
    {
    }

    public function fromClass1AndInterface2(): void
    {
    }

    public function fromClass1AndInterface2AndClass2(): void
    {
    }
}
