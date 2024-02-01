<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\Member;

class EnumCase extends Member implements \Stringable
{
    use Part\NamespaceAwareTrait;
    use Part\CommentAwareTrait;
    use Part\ValueAwareTrait;

    public static function create(string $name): self
    {
        return new self($name);
    }

    public function __toString()
    {
        $output = '';
        $output .= "case {$this->getName()} = {$this->getValue()};";

        return $output;
    }
}
