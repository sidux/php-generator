<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Model\PhpStruct;

/**
 * @internal
 */
trait VisibilityAwareTrait
{
    /**
     * @psalm-var value-of<PhpStruct::VISIBILITIES>
     */
    private string $visibility = PhpStruct::VISIBILITY_PUBLIC;

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * @psalm-param value-of<PhpStruct::VISIBILITIES> $visibility
     */
    public function setVisibility(string $visibility): self
    {
        if ($visibility && !\in_array($visibility, PhpStruct::VISIBILITIES, true)) {
            throw new \InvalidArgumentException('Argument must be ' . implode('|', PhpStruct::VISIBILITIES));
        }
        $this->visibility = $visibility;

        return $this;
    }
}
