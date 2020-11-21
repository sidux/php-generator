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

    public const DEFAULT_VISIBILITY = PhpStruct::VISIBILITY_PUBLIC;

    public static function create(...$args): self
    {
        return new self(...$args);
    }

    public function __toString(): string
    {
        $output = '';
        $output .= $this->commentsToString();
        $output .= $this->getVisibility() . ' ';
        $output .= 'const ';
        $output .= $this->getName();
        $output .= ' = ';
        $output .= (string)($this->getValue() ?? 'null');
        $output .= ';';
        $output .= "\n";

        return $output;
    }

    /**
     * @psalm-return value-of<PhpStruct::VISIBILITIES>
     */
    public function getDefaultVisibility(): string
    {
        return $this->getParent() ? $this->getParent()->getDefaultConstVisibility() : self::DEFAULT_VISIBILITY;
    }
}
