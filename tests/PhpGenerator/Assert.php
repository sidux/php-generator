<?php

declare(strict_types=1);

namespace Sidux\PhpGenerator;

use PHPUnit\Framework\Assert as BaseAssert;
use PHPUnit\Framework\AssertionFailedError;

class Assert extends BaseAssert
{

    /**
     * @param \Stringable|string $subject
     */
    public static function assertExpect(string $expectFile, $subject, bool $create = false): void
    {
        $expectFile = __DIR__ . "/Expected/$expectFile";
        if ($create && !file_exists($expectFile)) {
            file_put_contents($expectFile, (string)$subject);
        }
        static::assertStringEqualsFile(
            $expectFile,
            (string)$subject
        );
    }

    public static function assertThrowable(
        callable $test,
        string $expectedThrowableClass = \Throwable::class,
        ?string $expectedMessage = null,
        $expectedCode = null
    ): ?\Throwable {
        $expectedThrowableClass = self::fixThrowableClass($expectedThrowableClass);
        try {
            $test();
        } catch (\Throwable $throwable) {
            self::checkThrowableInstanceOf(
                $throwable,
                $expectedThrowableClass,
                self::resolveThrowableCaption(\Throwable::class)
            );
            self::checkThrowableCode($throwable, $expectedCode);
            self::checkThrowableMessage($throwable, $expectedMessage);

            return $throwable;
        }
        self::failAssertingThrowable($expectedThrowableClass);

        return null;
    }

    private static function fixThrowableClass(?string $throwableClass, string $defaultClass = \Throwable::class): string
    {
        if (null === $throwableClass) {
            $throwableClass = $defaultClass;
        } else {
            try {
                $reflection     = new \ReflectionClass($throwableClass);
                $throwableClass = $reflection->getName();
            } catch (\ReflectionException) {
                static::fail(
                    sprintf(
                        '%s of type "%s" does not exist.',
                        ucfirst(self::resolveThrowableCaption($defaultClass)),
                        $throwableClass
                    )
                );
            }
            if ($throwableClass !== $defaultClass && !$reflection->isInterface() && !$reflection->isSubclassOf(
                    $defaultClass
                )) {
                static::fail(
                    sprintf('A class "%s" is not %s.', $throwableClass, self::resolveThrowableCaption($defaultClass))
                );
            }
        }

        return $throwableClass;
    }

    private static function checkThrowableInstanceOf(
        \Throwable $throwable,
        string $expectedThrowableClass,
        string $expectedTypeCaption
    ): void {
        $message = $throwable->getMessage();
        $code    = $throwable->getCode();
        $details = '';
        if ('' !== $message && 0 !== $code) {
            $details = sprintf(
                ' (code was %s, message was "%s")',
                $code,
                $message
            ); // code might be string also, e.g. in PDOException
        } elseif ('' !== $message) {
            $details = sprintf(' (message was "%s")', $message);
        } elseif (0 !== $code) {
            $details = sprintf(' (code was %s)', $code);
        }
        $errorMessage = sprintf('Failed asserting the class of %s%s.', $expectedTypeCaption, $details);
        static::assertInstanceOf($expectedThrowableClass, $throwable, $errorMessage);
    }

    private static function resolveThrowableCaption(string $throwableClass): string
    {
        switch ($throwableClass) {
            case \Exception::class:
                return 'an Exception';
            case \Error::class:
                return 'an Error';
            case \Throwable::class:
                return 'a Throwable';
            default:
                return $throwableClass;
        }
    }

    /**
     * @param int|string|null $expectedCode
     */
    private static function checkThrowableCode(\Throwable $throwable, $expectedCode): void
    {
        if (null !== $expectedCode) {
            static::assertEquals(
                $expectedCode,
                $throwable->getCode(),
                sprintf('Failed asserting the code of thrown %s.', $throwable::class)
            );
        }
    }

    private static function checkThrowableMessage(\Throwable $throwable, string $expectedMessage = null): void
    {
        if (null !== $expectedMessage) {
            static::assertStringContainsString(
                $throwable->getMessage(),
                $expectedMessage,
                sprintf('Failed asserting the message of thrown %s.', $throwable::class)
            );
        }
    }

    /**
     * @throws AssertionFailedError
     */
    private static function failAssertingThrowable(string $expectedThrowableClass): void
    {
        static::fail(
            sprintf('Failed asserting that %s was thrown.', self::resolveThrowableCaption($expectedThrowableClass))
        );
    }

    public static function assertException(
        callable $test,
        string $expectedExceptionClass = \Exception::class,
        ?string $expectedMessage = null,
        $expectedCode = null
    ): ?\Exception {
        $expectedExceptionClass = self::fixThrowableClass($expectedExceptionClass);
        try {
            $test();
        } catch (\Exception $exception) {
            self::checkThrowableInstanceOf(
                $exception,
                $expectedExceptionClass,
                self::resolveThrowableCaption(\Exception::class)
            );
            self::checkThrowableCode($exception, $expectedCode);
            self::checkThrowableMessage($exception, $expectedMessage);

            return $exception;
        }
        self::failAssertingThrowable($expectedExceptionClass);

        return null;
    }


    public static function assertError(
        callable $test,
        string $expectedErrorClass = \Error::class,
        ?string $expectedMessage = null,
        $expectedCode = null
    ): ?\Error {
        $expectedErrorClass = self::fixThrowableClass($expectedErrorClass);
        try {
            $test();
        } catch (\Error $error) {
            self::checkThrowableInstanceOf($error, $expectedErrorClass, self::resolveThrowableCaption(\Error::class));
            self::checkThrowableCode($error, $expectedCode);
            self::checkThrowableMessage($error, $expectedMessage);

            return $error;
        }
        self::failAssertingThrowable($expectedErrorClass);

        return null;
    }
}
