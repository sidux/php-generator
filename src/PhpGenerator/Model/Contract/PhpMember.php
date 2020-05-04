<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Contract;

use Sidux\PhpGenerator\Model\PhpMethod;
use Sidux\PhpGenerator\Model\PhpStruct;

interface PhpMember
{
    /**
     * @return null|PhpStruct|PhpMethod
     */
    public function getParent();

    /**
     * @param null|PhpStruct|PhpMethod $parent
     */
    public function setParent($parent): void;
}
