<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper\Traits;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Sidux\PhpGenerator\Helper\PhpHelper;

trait MethodOverloadAwareTrait
{
    public static function __callStatic($name, $args)
    {
        $methodName = self::buildMethodNameWithArgsTypes($name, $args);

        return static::{$methodName}(...$args);
    }

    public function __call($name, $args)
    {
        $methodName = self::buildMethodNameWithArgsTypes($name, $args);

        return $this->$methodName(...$args);
    }

    protected static function buildMethodNameWithArgsTypes(string $baseName, array $args): string
    {
        $types = [];
        foreach ($args as $i => $arg) {
            $type = \gettype($arg);
            if ('object' === $type) {
                $class       = ReflectionClass::createFromInstance($arg);
                $types[$i][] = $class->getShortName();
                $types[$i]   = array_merge(
                    $types[$i],
                    $class->getInterfaceNames(),
                    $class->getParentClassNames()
                );
            }
            $types[$i][] = $type;
            $types[$i]   = array_map([PhpHelper::class, 'extractShortName'], $types[$i]);
        }

        $argsNumber    = \count($args);

        $possibleMethods = [];
        $getMethodName = static function (array $nameParts = [], int $index = 0) use (
            $types,
            $argsNumber,
            $baseName,
            &$getMethodName,
            &$possibleMethods
        ): ?string {
            if (!$types) {
                return null;
            }
            foreach ($types[$index] as $type) {
                $nameParts[$index] = $type;
                if (\count($nameParts) === $argsNumber) {
                    $methodName        = self::buildMethodName($baseName, $nameParts);
                    $possibleMethods[] = $methodName;
                    unset($nameParts[$index]);
                } else {
                    $methodName = $getMethodName($nameParts, $index + 1);
                }
                if ($methodName && method_exists(static::class, $methodName)) {
                    return $methodName;
                }
            }

            return null;
        };

        $methodName = $getMethodName();
        if (!$methodName) {
            $methods = implode(', ', $possibleMethods);
            $methods = "$baseName, $methods";
            throw new \RuntimeException("Non of methods list ($methods) found in class " . static::class);
        }

        return $methodName;
    }

    protected static function buildMethodName(string $baseName, array $types): ?string
    {
        $prefix = 'from' === strtolower(substr($baseName, -4, 4)) ? '' : 'From';

        return $baseName . $prefix . implode('And', array_map('ucfirst', $types));
    }
}
