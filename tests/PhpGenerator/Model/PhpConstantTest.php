<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;

class PhpConstantTest extends TestCase
{
    /**
     * @test
     */
    public function validate(): void
    {
        $constant = new PhpConstant('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $constant->getName());

        Assert::assertError(
            static function () {
                new PhpConstant(null); // @phpstan-ignore-line
            },
            \TypeError::class
        );

        Assert::assertException(
            static function () {
                new PhpConstant('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new PhpConstant('*');
            },
            \InvalidArgumentException::class
        );
    }
}
