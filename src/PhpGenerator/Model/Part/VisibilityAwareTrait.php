<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

use Sidux\PhpGenerator\Model\Struct;

/**
 * @internal
 */
trait VisibilityAwareTrait
{
    /**
     * @psalm-var value-of<Struct::VISIBILITIES>
     */
    private ?string $visibility = null;

    abstract public function getDefaultVisibility(): string;

    public function getVisibility(): string
    {
        return $this->visibility ?? $this->getDefaultVisibility();
    }

    /**
     * @psalm-param value-of<Struct::VISIBILITIES> $visibility
     */
    public function setVisibility(string $visibility): self
    {
        if ($visibility && !\in_array($visibility, Struct::VISIBILITIES, true)) {
            throw new \InvalidArgumentException('Argument must be ' . implode('|', Struct::VISIBILITIES));
        }
        $this->visibility = $visibility;

        return $this;
    }
}
