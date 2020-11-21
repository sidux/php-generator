<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;

class ConstantTest extends TestCase
{
    /**
     * @test
     */
    public function validate(): void
    {
        $constant = new Constant('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $constant->getName());

        Assert::assertError(
            static function () {
                new Constant(null); // @phpstan-ignore-line
            },
            \TypeError::class
        );

        Assert::assertException(
            static function () {
                new Constant('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new Constant('*');
            },
            \InvalidArgumentException::class
        );
    }
}
