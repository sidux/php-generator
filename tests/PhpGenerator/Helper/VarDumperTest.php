<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

use PHPUnit\Framework\TestCase;
use Sidux\PhpGenerator\Assert;
use Sidux\PhpGenerator\Model\PhpValue;
use Sidux\PhpGenerator\Stub\Test;
use Sidux\PhpGenerator\Stub\Test2;
use Sidux\PhpGenerator\Stub\Test3;
use Sidux\PhpGenerator\Stub\TestDateTime;

class VarDumperTest extends TestCase
{
    /**
     * @test
     */
    public function indent(): void
    {
        Assert::assertSame('[1, 2, 3]', VarDumper::dump([1, 2, 3], VarDumper::$wrapLength - 10));

        Assert::assertSame(
            '[
    1,
    2,
    3,
]',
            VarDumper::dump([1, 2, 3], VarDumper::$wrapLength - 8)
        );


// ignore indent after new line
        Assert::assertSame(
            '[
    [1, 2, 3],
]',
            VarDumper::dump([[1, 2, 3]], VarDumper::$wrapLength - 8)
        );


// counts with length of key
        Assert::assertSame('[8 => 1, 2, 3]', VarDumper::dump([8 => 1, 2, 3], VarDumper::$wrapLength - 15));

        Assert::assertSame(
            '[
    8 => 1,
    2,
    3,
]',
            VarDumper::dump([8 => 1, 2, 3], VarDumper::$wrapLength - 13)
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function all(): void
    {
        ini_set('serialize_precision', '14');


        Assert::assertSame('0', VarDumper::dump(0));
        Assert::assertSame('1', VarDumper::dump(1));
        Assert::assertSame('0.0', VarDumper::dump(0.0));
        Assert::assertSame('1.0', VarDumper::dump(1.0));
        Assert::assertSame('0.1', VarDumper::dump(0.1));
        Assert::assertSame('INF', VarDumper::dump(INF));
        Assert::assertSame('-INF', VarDumper::dump(-INF));
        Assert::assertSame('NAN', VarDumper::dump(NAN));
        Assert::assertSame('null', VarDumper::dump(null));
        Assert::assertSame('true', VarDumper::dump(true));
        Assert::assertSame('false', VarDumper::dump(false));

        Assert::assertSame("''", VarDumper::dump(''));
        Assert::assertSame("'Hello'", VarDumper::dump('Hello'));
        Assert::assertSame('"    \n    "', VarDumper::dump("    \n    "));
        Assert::assertSame(
            "'I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n'",
            VarDumper::dump("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n")
        ); // Iñtërnâtiônàlizætiøn
        Assert::assertSame('"\rHello \$"', VarDumper::dump("\rHello $"));
        Assert::assertSame("'He\\llo'", VarDumper::dump('He\llo'));
        Assert::assertSame('\'He\ll\\\\\o \\\'wor\\\\\\\'ld\\\\\'', VarDumper::dump('He\ll\\\o \'wor\\\'ld\\'));
        Assert::assertSame('[]', VarDumper::dump([]));

        Assert::assertSame('[$s]', VarDumper::dump([new PhpValue('$s')]));

        Assert::assertSame('[1, 2, 3]', VarDumper::dump([1, 2, 3]));
        Assert::assertSame(
            "['a', 7 => 'b', 'c', '9a' => 'd', 'e']",
            VarDumper::dump(['a', 7 => 'b', 'c', '9a' => 'd', 9 => 'e'])
        );

        VarDumper::$wrapLength = 100;
        Assert::assertSame(
            "[
    [
        'a',
        'loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
    ],
]",
            VarDumper::dump(
                [['a', 'loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong']]
            )
        );

        Assert::assertSame("['a' => 1, [\"\\r\" => \"\\r\", 2], 3]", VarDumper::dump(['a' => 1, ["\r" => "\r", 2], 3]));

        Assert::assertSame("(object) [\n    'a' => 1,\n    'b' => 2,\n]", VarDumper::dump((object)['a' => 1, 'b' => 2]));
        Assert::assertSame(
            "(object) [\n    'a' => (object) [\n        'b' => 2,\n    ],\n]",
            VarDumper::dump((object)['a' => (object)['b' => 2]])
        );


        Assert::assertSame(
            VarDumper::class . "::createObject('Sidux\PhpGenerator\Stub\Test', [\n    'a' => 1,\n    \"\\x00*\\x00b\" => 2,\n    \"\\x00Sidux\\\PhpGenerator\\\Stub\\\Test\\x00c\" => 3,\n])",
            VarDumper::dump(new Test())
        );
        Assert::assertEquals(
            new Test(),
            eval('return ' . VarDumper::dump(new Test()) . ';')
        );


        Assert::assertSame(
            VarDumper::class . "::createObject('Sidux\\PhpGenerator\\Stub\\Test2', [\n    \"\\x00Sidux\\\PhpGenerator\\\Stub\\\Test2\\x00c\" => 4,\n    'a' => 1,\n    \"\\x00*\\x00b\" => 2,\n])",
            VarDumper::dump(new Test2())
        );
        Assert::assertEquals(
            new Test2(),
            eval('return ' . VarDumper::dump(new Test2()) . ';')
        );


        Assert::assertSame('unserialize(\'C:29:"Sidux\PhpGenerator\Stub\Test3":0:{}\')', VarDumper::dump(new Test3()));
        Assert::assertEquals(
            new Test3(),
            eval('return ' . VarDumper::dump(new Test3()) . ';')
        );

        Assert::assertException(
            static function () {
                VarDumper::dump(
                    static function () {
                    }
                );
            },
            \InvalidArgumentException::class,
            'Cannot dump closure.'
        );

        Assert::assertSame(
            "new DateTime('2016-06-22 20:52:43.123400', new DateTimeZone('Europe/Prague'))",
            VarDumper::dump(new \DateTime('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague')))
        );
        Assert::assertSame(
            "new DateTimeImmutable('2016-06-22 20:52:43.123400', new DateTimeZone('Europe/Prague'))",
            VarDumper::dump(new \DateTimeImmutable('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague')))
        );
        Assert::assertSame(
            VarDumper::class ."::createObject('Sidux\PhpGenerator\Stub\TestDateTime', [
    'date' => '2016-06-22 20:52:43.123400',
    'timezone_type' => 3,
    'timezone' => 'Europe/Prague',
])",
            VarDumper::dump(
                new TestDateTime('2016-06-22 20:52:43.1234', new \DateTimeZone('Europe/Prague'))
            )
        );

        Assert::assertException(
            static function () {
                VarDumper::dump(
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

                VarDumper::dump($rec);
            },
            \InvalidArgumentException::class,
            'Nesting level too deep or recursive dependency.'
        );


        Assert::assertException(
            static function () {
                $rec    = new \stdClass();
                $rec->x = &$rec;

                VarDumper::dump($rec);
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
        VarDumper::$wrapLength = 21;
        Assert::assertSame(
            "[
    'a' => [1, 2, 3],
    'aaaaaaaaa' => [
        1,
        2,
        3,
    ],
]",
            VarDumper::dump(
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
            VarDumper::dump(
                [
                    'single' => new PhpValue('1 + 2'),
                    'multi' => new PhpValue("[\n    1,\n]\n"),
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
            VarDumper::dump(
                (object)[
                    'a' => [1, 2, 3],
                    'aaaaaaaaa' => [1, 2, 3],
                ]
            )
        );


        VarDumper::$wrapLength = 100;
        Assert::assertSame(
            "[
    [
        'a',
        'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
    ],
]",
            VarDumper::dump(
                [['a', 'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong']]
            )
        );
    }
}
