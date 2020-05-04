<?php

namespace Sidux\PhpGenerator\Stub;

use Serializable;

class Test3 implements Serializable
{
    private $a;


    public function serialize()
    {
        return '';
    }


    public function unserialize($s)
    {
    }
}