<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

use Closure;
use ReflectionObject;
use Serializable;
use Sidux\PhpGenerator\Model\PhpValue;

final class VarDumper
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

    private static function dumpVar(&$var, array $parents = [], int $level = 0, int $column = 0): string
    {
        if ($var instanceof PhpValue) {
            return ltrim(StringHelper::indent(trim((string)$var), $level), "\t ");
        }

        if ($var === null) {
            return 'null';
        }

        if (\is_string($var)) {
            return static::dumpString($var);
        }

        if (\is_array($var)) {
            return static::dumpArray($var, $parents, $level, $column);
        }

        if (\is_object($var)) {
            return static::dumpObject($var, $parents, $level);
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
            if ($table === null) {
                foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
                    $table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
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

        if ($level > static::$maxDepth || in_array($var, $parents ?? [], true)) {
            throw new \InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }

        $space      = str_repeat('    ', $level);
        $outInline  = '';
        $outWrapped = "\n$space";
        $parents[]  = $var;
        $counter    = 0;

        foreach ($var as $k => &$v) {
            $keyPart    = $k === $counter ? '' : static::dumpVar($k) . ' => ';
            $counter    = \is_int($k) ? max($k + 1, $counter) : $counter;
            $outInline  .= ($outInline === '' ? '' : ', ') . $keyPart;
            $outInline  .= static::dumpVar($v, $parents, 0, $column + strlen($outInline));
            $outWrapped .= '    '
                . $keyPart
                . static::dumpVar($v, $parents, $level + 1, strlen($keyPart))
                . ",\n$space";
        }

        array_pop($parents);
        $wrap = strpos($outInline, "\n") !== false || $level * self::INDENT_LENGTH + $column + strlen($outInline) + 3 > static::$wrapLength;

        return '[' . ($wrap ? $outWrapped : $outInline) . ']';
    }

    private static function dumpObject($var, array $parents, int $level): string
    {
        if ($var instanceof Serializable) {
            return 'unserialize(' . static::dumpString(serialize($var)) . ')';
        }

        if ($var instanceof Closure) {
            throw new \InvalidArgumentException('Cannot dump closure.');
        }

        $class = get_class($var);
        if ((new ReflectionObject($var))->isAnonymous()) {
            throw new \InvalidArgumentException('Cannot dump anonymous class.');
        }

        if (in_array($class, ['DateTime', 'DateTimeImmutable'], true)) {
            return "new $class('{$var->format('Y-m-d H:i:s.u')}', new DateTimeZone('{$var->getTimeZone()->getName()}'))";
        }

        $arr   = (array)$var;
        $space = str_repeat('    ', $level);

        if ($level > static::$maxDepth || in_array($var, $parents ?? [], true)) {
            throw new \InvalidArgumentException('Nesting level too deep or recursive dependency.');
        }

        $out       = "\n";
        $parents[] = $var;
        if (method_exists($var, '__sleep')) {
            foreach ($var->__sleep() as $v) {
                $props[$v] = $props["\x00*\x00$v"] = $props["\x00$class\x00$v"] = true;
            }
        }

        foreach ($arr as $k => &$v) {
            if (!isset($props) || isset($props[$k])) {
                $out .= "$space    "
                    . ($keyPart = static::dumpVar($k) . ' => ')
                    . static::dumpVar($v, $parents, $level + 1, strlen($keyPart))
                    . ",\n";
            }
        }

        array_pop($parents);
        $out .= $space;

        return $class === 'stdClass'
            ? "(object) [$out]"
            : __CLASS__ . "::createObject('$class', [$out])";
    }
}
