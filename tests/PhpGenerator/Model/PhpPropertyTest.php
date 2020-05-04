<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;

class PhpPropertyTest extends TestCase
{
    /**
     * @test
     */
    public function validate(): void
    {
        $property = new PhpProperty('Iñtërnâtiônàlizætiøn');
        Assert::assertSame('Iñtërnâtiônàlizætiøn', $property->getName());

        Assert::assertError(
            static function () {
                new PhpProperty(null);
            },
            \TypeError::class
        );

        Assert::assertException(
            static function () {
                new PhpProperty('');
            },
            \InvalidArgumentException::class
        );

        Assert::assertException(
            static function () {
                new PhpProperty('*');
            },
            \InvalidArgumentException::class
        );
    }
}
