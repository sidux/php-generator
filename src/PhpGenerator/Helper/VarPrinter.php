<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

use Closure;
use ReflectionObject;
use Serializable;
use Sidux\PhpGenerator\Model\Value;

final class VarPrinter
{
    private const INDENT_LENGTH = 4;

    public static int $maxDepth = 50;

    public static int $wrapLength = 120;

    public static function createObject(string $class, array $props): object
    {
        /** @noinspection UnserializeExploitsInspection */
        return unserialize('O' . substr(serialize($class), 1, -1) . substr(serialize($props), 1));
    }

    public static function dump($var, int $column = 0): string
    {
        return self::dumpVar($var, [], 0, $column);
    }

    public static function setWrapLength(int $wrapLength): void
    {
        self::$wrapLength = $wrapLength;
    }

    private static function dumpVar(&$var, array $parents = [], int $level = 0, int $column = 0): string
    {
        if ($var instanceof Value) {
            return ltrim(StringHelper::indent(trim((string)$var), $level), "\t ");
        }

        if (null === $var) {
            return 'null';
        }

        if (\is_string($var)) {
            return self::dumpString($var);
        }

        if (\is_array($var)) {
            return self::dumpArray($var, $parents, $level, $column);
        }

        if (\is_object($var)) {
            return self::dumpObject($var, $parents, $level);
        }

        if (\is_resource($var)) {
            throw new \InvalidArgumentException('Cannot dump resource.');
        }

        return var_export($var, true);
    }

    private static function dumpString(string $var): string
    {
        if (preg_match('#[^\x09\x20-\x7E\xA0-\x{10FFFF}]#u', $var) || preg_last_error()) {
            static $table;
            if (null === $table) {
                foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
                    $table[$ch] = '\x' . str_pad(dechex(\ord($ch)), 2, '0', STR_PAD_LEFT);
                }
                $table['\\'] = '\\\\';
                $table["\r"] = '\r';
                $table["\n"] = '\n';
                $table["\t"] = '\t';
                $table['$']  = '\$';
                $table['"']  = '\"';
            }

            return '"' . strtr($var, $table) . '"';
        }

        return "'" . preg_replace('#\'|\\\\(?=[\'\\\\]|$)#D', '\\\\$0', $var) . "'";
    }

    private static function dumpArray(array &$var, array $parents, int $level, int $column): string
    {
        if (empty($var)) {
            return '[]';
        }

        if ($level > self::$maxDepth || \in_array($var, $parents, true)) {
            throw new \InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }

        $space      = str_repeat('    ', $level);
        $outInline  = '';
        $outWrapped = "\n$space";
        $parents[]  = $var;
        $counter    = 0;

        foreach ($var as $k => &$v) {
            $keyPart    = $k === $counter ? '' : self::dumpVar($k) . ' => ';
            $counter    = \is_int($k) ? max($k + 1, $counter) : $counter;
            $outInline  .= ('' === $outInline ? '' : ', ') . $keyPart;
            $outInline  .= self::dumpVar($v, $parents, 0, $column + \strlen($outInline));
            $outWrapped .= '    '
                . $keyPart
                . self::dumpVar($v, $parents, $level + 1, \strlen($keyPart))
                . ",\n$space";
        }
        unset($v);

        array_pop($parents);
        $wrap = str_contains($outInline, "\n") || $level * self::INDENT_LENGTH + $column + \strlen($outInline) + 3 > self::$wrapLength;

        return '[' . ($wrap ? $outWrapped : $outInline) . ']';
    }

    private static function dumpObject($var, array $parents, int $level): string
    {
        if ($var instanceof Serializable) {
            return 'unserialize(' . self::dumpString(serialize($var)) . ')';
        }

        if ($var instanceof Closure) {
            throw new \InvalidArgumentException('Cannot dump closure.');
        }

        $class = $var::class;
        if ((new ReflectionObject($var))->isAnonymous()) {
            throw new \InvalidArgumentException('Cannot dump anonymous class.');
        }

        if (\in_array($class, ['DateTime', 'DateTimeImmutable'], true)) {
            return "new $class('{$var->format('Y-m-d H:i:s.u')}', new DateTimeZone('{$var->getTimeZone()->getName()}'))";
        }

        $arr   = (array)$var;
        $space = str_repeat('    ', $level);

        if ($level > self::$maxDepth || \in_array($var, $parents, true)) {
            throw new \InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }

        $out       = "\n";
        $parents[] = $var;
        if (method_exists($var, '__sleep')) {
            $props = [];
            foreach ($var->__sleep() as $v) {
                $props["\x00$class\x00$v"] = true;
                $props["\x00*\x00$v"]      = true;
                $props[$v]                 = true;
            }
        }

        foreach ($arr as $k => &$v) {
            if (!isset($props) || isset($props[$k])) {
                $out .= "$space    "
                    . ($keyPart = self::dumpVar($k) . ' => ')
                    . self::dumpVar($v, $parents, $level + 1, \strlen($keyPart))
                    . ",\n";
            }
        }
        unset($v);

        array_pop($parents);
        $out .= $space;

        return \stdClass::class === $class
            ? "(object) [$out]"
            : self::class . "::createObject('$class', [$out])";
    }
}
