<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\ConfigCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigCacheTest extends TestCase
{
    private string $cacheDir;

    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpdot_config_cache_test_' . uniqid();
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
    public function writeCreatesValidPhpFile(): void
    {
        $data = ['app' => ['name' => 'Test']];

        ConfigCache::write($data, $this->cacheFile);

        self::assertFileExists($this->cacheFile);

        $content = file_get_contents($this->cacheFile);
        self::assertIsString($content);
        self::assertStringContainsString('<?php', $content);
    }

    #[Test]
    public function readReturnsArrayFromCache(): void
    {
        $data = ['app' => ['name' => 'Test', 'debug' => true]];

        ConfigCache::write($data, $this->cacheFile);
        $result = ConfigCache::read($this->cacheFile);

        self::assertSame($data, $result);
    }

    #[Test]
    public function readReturnsNullForNonExistentFile(): void
    {
        $result = ConfigCache::read($this->cacheFile);

        self::assertNull($result);
    }

    #[Test]
    public function clearDeletesFile(): void
    {
        ConfigCache::write(['test' => true], $this->cacheFile);

        self::assertFileExists($this->cacheFile);

        ConfigCache::clear($this->cacheFile);

        self::assertFileDoesNotExist($this->cacheFile);
    }

    #[Test]
    public function clearDoesNothingIfFileDoesNotExist(): void
    {
        // Should not throw
        ConfigCache::clear($this->cacheFile);

        self::assertFileDoesNotExist($this->cacheFile);
    }

    #[Test]
    public function roundtripWriteThenReadReturnsIdenticalData(): void
    {
        $data = [
            'app' => [
                'name' => 'TestApp',
                'debug' => true,
                'version' => '1.0.0',
            ],
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ];

        ConfigCache::write($data, $this->cacheFile);
        $result = ConfigCache::read($this->cacheFile);

        self::assertSame($data, $result);
    }
}
