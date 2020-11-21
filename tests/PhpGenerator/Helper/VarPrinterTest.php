<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Model\Value;
use Sidux\PhpGenerator\Stub\Test;
use Sidux\PhpGenerator\Stub\Test2;
use Sidux\PhpGenerator\Stub\Test3;
use Sidux\PhpGenerator\Stub\TestDateTime;

class VarPrinterTest extends TestCase
{
    /**
     * @test
     */
    public function indent(): void
    {
        Assert::assertSame('[1, 2, 3]', VarPrinter::dump([1, 2, 3], VarPrinter::$wrapLength - 10));

        Assert::assertSame(
            '[
    1,
    2,
    3,
]',
            VarPrinter::dump([1, 2, 3], VarPrinter::$wrapLength - 8)
        );


// ignore indent after new line
        Assert::assertSame(
            '[
    [1, 2, 3],
]',
            VarPrinter::dump([[1, 2, 3]], VarPrinter::$wrapLength - 8)
        );


// counts with length of key
        Assert::assertSame('[8 => 1, 2, 3]', VarPrinter::dump([8 => 1, 2, 3], VarPrinter::$wrapLength - 15));

        Assert::assertSame(
            '[
    8 => 1,
    2,
    3,
]',
            VarPrinter::dump([8 => 1, 2, 3], VarPrinter::$wrapLength - 13)
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function all(): void
    {
        ini_set('serialize_precision', '14');


        Assert::assertSame('0', VarPrinter::dump(0));
        Assert::assertSame('1', VarPrinter::dump(1));
        Assert::assertSame('0.0', VarPrinter::dump(0.0));
        Assert::assertSame('1.0', VarPrinter::dump(1.0));
        Assert::assertSame('0.1', VarPrinter::dump(0.1));
        Assert::assertSame('INF', VarPrinter::dump(INF));
        Assert::assertSame('-INF', VarPrinter::dump(-INF));
        Assert::assertSame('NAN', VarPrinter::dump(NAN));
        Assert::assertSame('null', VarPrinter::dump(null));
        Assert::assertSame('true', VarPrinter::dump(true));
        Assert::assertSame('false', VarPrinter::dump(false));

        Assert::assertSame("''", VarPrinter::dump(''));
        Assert::assertSame("'Hello'", VarPrinter::dump('Hello'));
        Assert::assertSame('"    \n    "', VarPrinter::dump("    \n    "));
        Assert::assertSame(
            "'I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n'",
            VarPrinter::dump("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n")
        ); // Iñtërnâtiônàlizætiøn
        Assert::assertSame('"\rHello \$"', VarPrinter::dump("\rHello $"));
        Assert::assertSame("'He\\llo'", VarPrinter::dump('He\llo'));
        Assert::assertSame('\'He\ll\\\\\o \\\'wor\\\\\\\'ld\\\\\'', VarPrinter::dump('He\ll\\\o \'wor\\\'ld\\'));
        Assert::assertSame('[]', VarPrinter::dump([]));

        Assert::assertSame('[$s]', VarPrinter::dump([new Value('$s')]));

        Assert::assertSame('[1, 2, 3]', VarPrinter::dump([1, 2, 3]));
        Assert::assertSame(
            "['a', 7 => 'b', 'c', '9a' => 'd', 'e']",
            VarPrinter::dump(['a', 7 => 'b', 'c', '9a' => 'd', 9 => 'e'])
        );

        VarPrinter::setWrapLength(100);
        Assert::assertSame(
            "[
    [
        'a',
        'loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
    ],
]",
            VarPrinter::dump(
                [['a', 'loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong']]
            )
        );

        Assert::assertSame("['a' => 1, [\"\\r\" => \"\\r\", 2], 3]", VarPrinter::dump(['a' => 1, ["\r" => "\r", 2], 3]));

        Assert::assertSame("(object) [\n    'a' => 1,\n    'b' => 2,\n]", VarPrinter::dump((object)['a' => 1, 'b' => 2]));
        Assert::assertSame(
            "(object) [\n    'a' => (object) [\n        'b' => 2,\n    ],\n]",
            VarPrinter::dump((object)['a' => (object)['b' => 2]])
        );


        Assert::assertSame(
            VarPrinter::class . "::createObject('Sidux\PhpGenerator\Stub\Test', [\n    'a' => 1,\n    \"\\x00*\\x00b\" => 2,\n    \"\\x00Sidux\\\PhpGenerator\\\Stub\\\Test\\x00c\" => 3,\n])",
            VarPrinter::dump(new Test())
        );
        /** @noinspection PhpUnreachableStatementInspection */
        Assert::assertEquals(
            new Test(),
            eval('return ' . VarPrinter::dump(new Test()) . ';')
        );


        Assert::assertSame(
            VarPrinter::class . "::createObject('Sidux\\PhpGenerator\\Stub\\Test2', [\n    \"\\x00Sidux\\\PhpGenerator\\\Stub\\\Test2\\x00c\" => 4,\n    'a' => 1,\n    \"\\x00*\\x00b\" => 2,\n])",
            VarPrinter::dump(new Test2())
        );
        /** @noinspection PhpUnreachableStatementInspection */
        Assert::assertEquals(
            new Test2(),
            eval('return ' . VarPrinter::dump(new Test2()) . ';')
        );


        Assert::assertSame('unserialize(\'C:29:"Sidux\PhpGenerator\Stub\Test3":0:{}\')', VarPrinter::dump(new Test3()));
        /** @noinspection PhpUnreachableStatementInspection */
        Assert::assertEquals(
            new Test3(),
            eval('return ' . VarPrinter::dump(new Test3()) . ';')
        );

        Assert::assertException(
            static function () {
                VarPrinter::dump(
                    static function () {
                    }
                );
            },
            \InvalidArgumentException::class,
            'Cannot dump closure.'
        );

        Assert::assertSame(
            "new DateTime('2016-06-22 20:52:43.123400', new DateTimeZone('Europe/Prague'))",
            VarPrinter::dump(new \DateTime('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague')))
        );
        Assert::assertSame(
            "new DateTimeImmutable('2016-06-22 20:52:43.123400', new DateTimeZone('Europe/Prague'))",
            VarPrinter::dump(new \DateTimeImmutable('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague')))
        );
        Assert::assertSame(
            VarPrinter::class . "::createObject('Sidux\PhpGenerator\Stub\TestDateTime', [
    'date' => '2016-06-22 20:52:43.123400',
    'timezone_type' => 3,
    'timezone' => 'Europe/Prague',
])",
            VarPrinter::dump(
                new TestDateTime('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague'))
            )
        );

        Assert::assertException(
            static function () {
                VarPrinter::dump(
                    new class {
                    }
                );
            },
            \InvalidArgumentException::class,
            'Cannot dump anonymous class.'
        );


        Assert::assertException(
            static function () {
                $rec   = [];
                $rec[] = &$rec;

                VarPrinter::dump($rec);
            },
            \InvalidArgumentException::class,
            'Nesting level too deep or recursive dependency.'
        );


        Assert::assertException(
            static function () {
                $rec    = new \stdClass();
                $rec->x = &$rec;

                VarPrinter::dump($rec);
            },
            \InvalidArgumentException::class,
            'Nesting level too deep or recursive dependency.'
        );
    }

    /**
     * @test
     */
    public function wrap(): void
    {
        VarPrinter::setWrapLength(21);
        Assert::assertSame(
            "[
    'a' => [1, 2, 3],
    'aaaaaaaaa' => [
        1,
        2,
        3,
    ],
]",
            VarPrinter::dump(
                [
                    'a' => [1, 2, 3],
                    'aaaaaaaaa' => [1, 2, 3],
                ]
            )
        );

        Assert::assertSame(
            "[
    'single' => 1 + 2,
    'multi' => [
        1,
    ],
]",
            VarPrinter::dump(
                [
                    'single' => new Value('1 + 2'),
                    'multi' => new Value("[\n    1,\n]\n"),
                ]
            )
        );

        Assert::assertSame(
            "(object) [
    'a' => [1, 2, 3],
    'aaaaaaaaa' => [
        1,
        2,
        3,
    ],
]",
            VarPrinter::dump(
                (object)[
                    'a' => [1, 2, 3],
                    'aaaaaaaaa' => [1, 2, 3],
                ]
            )
        );


        VarPrinter::setWrapLength(100);
        Assert::assertSame(
            "[
    [
        'a',
        'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
    ],
]",
            VarPrinter::dump(
                [['a', 'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong']]
            )
        );
    }
}
