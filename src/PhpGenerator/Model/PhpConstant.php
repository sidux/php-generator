<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\PhpMember;
use Sidux\PhpGenerator\Model\Contract\ValueAware;
use Sidux\PhpGenerator\Model\Part;

class PhpConstant extends PhpMember implements ValueAware
{
    use Part\NamespaceAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\CommentAwareTrait;
    use Part\ValueAwareTrait;

    public function __toString(): string
    {
        $output = '';
        $output .= $this->commentsToString();
        $output .= $this->visibility . ' ';
        $output .= 'const ';
        $output .= $this->name;
        $output .= ' = ';
        $output .= $this->getValue();
        $output .= ';';
        $output .= "\n";

        return $output;
    }
}
