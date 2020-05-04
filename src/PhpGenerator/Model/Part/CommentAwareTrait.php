<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Model\Part;

/**
 * @internal
 */
trait CommentAwareTrait
{
    private array $comments = [];

    public function commentsToString(): string
    {
        if (!$this->comments) {
            return '';
        }

        $content = str_replace("\n", "\n * ", implode("\n", $this->comments));

        return "/**\n * " . $content . "\n */\n";
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function setComment(string $value): self
    {
        if (!$value) {
            return $this;
        }

        $value    = ltrim(preg_replace("#/\*\*\n?|\*/#", '', $value), '* ');
        $comments = preg_split("/ \* ?/", $value);

        foreach ($comments as $comment) {
            $this->addComment(trim($comment));
        }

        return $this;
    }

    public function addComment(string $value): self
    {
        $this->comments[] = $value;

        return $this;
    }
}
