<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator\Helper;

class StubHelper
{
    public static function load(string $name, $require = true): string
    {
        $filePath = __DIR__ . "/../Stub/$name.php";
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("file $filePath does not exist or is not readable");
        }
        $content = file_get_contents($filePath);
        if ($require && str_contains($content, '<?php')) {
            /** @noinspection PhpIncludeInspection */
            require_once $filePath;
            $content = preg_replace("#<\?php\s*\n*#", '', $content);
        }

        return $content;
    }
}
