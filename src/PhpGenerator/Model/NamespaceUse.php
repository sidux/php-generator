<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model;

use Sidux\PhpGenerator\Model\Contract\Element;

class NamespaceUse extends QualifiedName implements Element
{
    public function __toString(): string
    {
        $name   = $this->getName();
        $output = '';
        if ($this->qualifiedName !== ($name ? $name . '\\' . $this->alias : $this->alias)) {
            if (!$this->alias || $this->alias === $this->qualifiedName || substr($this->qualifiedName, -(\strlen($this->alias) + 1)) === '\\' . $this->alias) {
                $output .= "use $this->qualifiedName;";
            } else {
                $output .= "use $this->qualifiedName as $this->alias;";
            }
        }

        return $output;
    }
}
