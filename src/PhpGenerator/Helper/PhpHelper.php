<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

final class PhpHelper
{
    public const PHP_IDENTIFIER = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\[\])?';

    public static function extractNamespace(string $name): string
    {
        return ($pos = strrpos($name, '\\')) ? substr($name, 0, $pos) : '\\';
    }

    public static function extractShortName(string $name): string
    {
        return false === ($pos = strrpos($name, '\\')) ? $name : substr($name, $pos + 1);
    }

    public static function isIdentifier($value): bool
    {
        return \is_string($value) && preg_match('#^' . self::PHP_IDENTIFIER . '$#D', $value);
    }

    public static function isNamespaceIdentifier($value): bool
    {
        $pattern = '#^\\\\?' . self::PHP_IDENTIFIER . '(\\\\' . self::PHP_IDENTIFIER . ')*|\\\\$#D';

        return \is_string($value) && preg_match($pattern, $value);
    }

    public static function isType($value): bool
    {
        $pattern = '#^\??\\\\?' . self::PHP_IDENTIFIER . '(\\\\' . self::PHP_IDENTIFIER . ')*$#D';

        return \is_string($value) && preg_match($pattern, $value);
    }
}
