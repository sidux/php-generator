<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper\Traits;

/**
 * @method __construct($args)
 */
trait StaticCreateAwareTrait
{
    public static function create(...$args): self
    {
        return new static(...$args);
    }
}
