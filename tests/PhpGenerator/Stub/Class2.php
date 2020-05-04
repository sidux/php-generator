<?php

namespace Sidux\PhpGenerator\Stub;

use Abc\Unknown;

class Class2 extends \Sidux\PhpGenerator\Stub\Class1 implements \Sidux\PhpGenerator\Stub\Interface2
{
    /**
     * Public
     *
     * @var int
     */
    public $public;

    /** @var int */
    protected $protected = 10;

    private $private = [];

    static public $static;

    /**
     * Func3
     *
     * @return \Sidux\PhpGenerator\Stub\Class1
     */
    private function &func3(
        array $a = [],
        Class2 $b = null,
        \Abc\Unknown $c,
        \Xyz\Unknown $d,
        callable $e,
        $f = Unknown::ABC,
        $g
    ) {
    }

    final public function func2()
    {
    }
}
