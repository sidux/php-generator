<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;

class PropertyTest extends TestCase
{
    /**
     * @test
     */
    public function validate(): void
    {
        $property = new Property('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $property->getName());

        Assert::assertError(
            static function () {
                new Property(null); // @phpstan-ignore-line
            },
            \TypeError::class
        );

        Assert::assertException(
            static function () {
                new Property('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new Property('*');
            },
            \InvalidArgumentException::class
        );
    }
}
