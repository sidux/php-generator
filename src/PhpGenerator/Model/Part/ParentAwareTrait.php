<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Model\PhpStruct;

/**
 * @internal
 */
trait ParentAwareTrait
{
    /**
     * @var mixed
     */
    private $parent;

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function getStructParent(): ?PhpStruct
    {
        $parent = $this->getParent();
        while ($parent
            && method_exists($parent, 'getParent')) {
            $parent = $parent->getParent();
        }

        if (!$parent) {
            return null;
        }

        if (!$parent instanceof PhpStruct) {
            return null;
        }

        return $parent;
    }
}
