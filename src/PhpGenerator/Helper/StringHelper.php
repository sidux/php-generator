<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

class StringHelper
{
    public static function indent(string $subject, int $level = 1, string $chars = '    '): string
    {
        if ($level > 0) {
            $subject = preg_replace('#(?:^|[\r\n]+)(?=[^\r\n])#', '$0' . str_repeat($chars, $level), $subject);
        }

        return $subject;
    }
}
