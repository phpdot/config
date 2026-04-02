<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Exception\ConfigCacheException;
use PHPdot\Config\Exception\ConfigException;
use PHPdot\Config\Exception\ConfigLoaderException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionTest extends TestCase
{
    #[Test]
    public function configExceptionExtendsRuntimeException(): void
    {
        $exception = new ConfigException('test');

        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function loaderExceptionDirectoryNotFound(): void
    {
        $exception = ConfigLoaderException::directoryNotFound('/missing/path');

        self::assertInstanceOf(ConfigLoaderException::class, $exception);
        self::assertInstanceOf(ConfigException::class, $exception);
        self::assertStringContainsString('/missing/path', $exception->getMessage());
    }

    #[Test]
    public function loaderExceptionFileNotReadable(): void
    {
        $exception = ConfigLoaderException::fileNotReadable('/bad/file.php');

        self::assertStringContainsString('/bad/file.php', $exception->getMessage());
    }

    #[Test]
    public function cacheExceptionWriteFailure(): void
    {
        $exception = ConfigCacheException::writeFailure('/cache/path');

        self::assertInstanceOf(ConfigCacheException::class, $exception);
        self::assertInstanceOf(ConfigException::class, $exception);
        self::assertStringContainsString('/cache/path', $exception->getMessage());
    }

    #[Test]
    public function cacheExceptionInvalidFormat(): void
    {
        $exception = ConfigCacheException::invalidCacheFormat('/cache/bad');

        self::assertStringContainsString('/cache/bad', $exception->getMessage());
    }
}
