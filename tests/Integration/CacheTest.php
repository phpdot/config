<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Integration;

use PHPdot\Config\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    private string $cacheDir;

    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpdot_config_integration_test_' . uniqid();
        mkdir($this->cacheDir, 0777, true);
        $this->cacheFile = $this->cacheDir . '/config.cache.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    #[Test]
    public function coldStartWritesCacheFile(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            cachePath: $this->cacheFile,
        );

        // Accessing a value triggers loading
        $config->get('app.name');

        self::assertFileExists($this->cacheFile);
    }

    #[Test]
    public function warmStartLoadsFromCache(): void
    {
        // Cold start: create cache
        $config1 = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            cachePath: $this->cacheFile,
        );
        $name1 = $config1->get('app.name');

        // Warm start: load from cache
        $config2 = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            cachePath: $this->cacheFile,
        );
        $name2 = $config2->get('app.name');

        self::assertSame($name1, $name2);
        self::assertSame('TestApp', $name2);
    }

    #[Test]
    public function reloadClearsCacheAndReloads(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            cachePath: $this->cacheFile,
        );

        // Populate cache
        $config->get('app.name');
        self::assertFileExists($this->cacheFile);

        // Reload should clear and rebuild
        $config->reload();

        self::assertSame('TestApp', $config->get('app.name'));
    }

    #[Test]
    public function cachedValuesMatchOriginalValues(): void
    {
        // Create uncached manager
        $uncached = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
        );

        // Create cached manager
        $cached = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            cachePath: $this->cacheFile,
        );

        self::assertSame(
            $uncached->get('app.name'),
            $cached->get('app.name'),
        );
        self::assertSame(
            $uncached->get('database.host'),
            $cached->get('database.host'),
        );
        self::assertSame(
            $uncached->get('cache.driver'),
            $cached->get('cache.driver'),
        );
    }
}
