<?php

namespace Sidux\PhpGenerator\Stub;

interface Variadics
{
    function foo(...$foo);

    function bar($foo, array &...$bar);
}
