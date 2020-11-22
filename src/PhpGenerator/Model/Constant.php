<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\Member;
use Sidux\PhpGenerator\Model\Contract\ValueAware;

class Constant extends Member implements ValueAware
{
    use Part\NamespaceAwareTrait;
    use Part\VisibilityAwareTrait;
    use Part\CommentAwareTrait;
    use Part\ValueAwareTrait;

    public const DEFAULT_VISIBILITY = Struct::PUBLIC;

    public static function create(string $name): self
    {
        return new self($name);
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
     * @psalm-return value-of<Struct::VISIBILITIES>
     */
    public function getDefaultVisibility(): string
    {
        return $this->getParent() ? $this->getParent()->getDefaultConstVisibility() : self::DEFAULT_VISIBILITY;
    }
}
