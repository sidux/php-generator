<?php

namespace Sidux\PhpGenerator\Stub;

class Test2 extends \Sidux\PhpGenerator\Stub\Test
{
    public $d = 5;

    private $c = 4;


    public function __sleep()
    {
        return ['c', 'b', 'a'];
    }


    public function __wakeup()
    {
    }
}