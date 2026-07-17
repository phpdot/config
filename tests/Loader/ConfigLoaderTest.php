<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Loader;

use PHPdot\Config\Exception\ConfigLoaderException;
use PHPdot\Config\Loader\ConfigLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;

    private string $fixturePath;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
        $this->fixturePath = __DIR__ . '/../Fixtures/config';
    }

    #[Test]
    public function loadsAllPhpFilesFromFixtureDirectory(): void
    {
        $config = $this->loader->load($this->fixturePath);

        self::assertArrayHasKey('app', $config);
        self::assertArrayHasKey('database', $config);
        self::assertArrayHasKey('cache', $config);
        self::assertArrayHasKey('mail', $config);
    }

    #[Test]
    public function returnsLowercaseSectionNamesFromFilenames(): void
    {
        $config = $this->loader->load($this->fixturePath);

        $keys = array_keys($config);

        foreach ($keys as $key) {
            self::assertSame(strtolower($key), $key);
        }
    }

    #[Test]
    public function excludesFilesInExcludeList(): void
    {
        $config = $this->loader->load($this->fixturePath, ['mail']);

        self::assertArrayNotHasKey('mail', $config);
        self::assertArrayHasKey('app', $config);
    }

    #[Test]
    public function throwsForNonExistentDirectory(): void
    {
        $this->expectException(ConfigLoaderException::class);

        $this->loader->load('/nonexistent/path/to/config');
    }

    #[Test]
    public function returnsEmptyForEmptyDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/phpdot_config_test_empty_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $config = $this->loader->load($tempDir);

            self::assertSame([], $config);
        } finally {
            rmdir($tempDir);
        }
    }

    #[Test]
    public function sortsFilesDeterministically(): void
    {
        $config1 = $this->loader->load($this->fixturePath);
        $config2 = $this->loader->load($this->fixturePath);

        self::assertSame(array_keys($config1), array_keys($config2));
    }

    #[Test]
    public function loadsNestedFilesAsDotKeyedSections(): void
    {
        $config = $this->loader->load(__DIR__ . '/../Fixtures/config-nested');

        // Parent file keeps its plain basename; nested files become dot-keyed.
        self::assertArrayHasKey('database', $config);
        self::assertArrayHasKey('database.mysql', $config);
        self::assertArrayHasKey('database.sqlite', $config);

        self::assertSame(3306, $config['database.mysql']['port'] ?? null);
        self::assertSame('/var/db/app.sqlite', $config['database.sqlite']['path'] ?? null);
    }

    #[Test]
    public function loadsArbitrarilyDeepNestedFiles(): void
    {
        $config = $this->loader->load(__DIR__ . '/../Fixtures/config-nested');

        self::assertArrayHasKey('a.b.c.d', $config);
        self::assertSame('deep', $config['a.b.c.d']['value'] ?? null);
    }

    #[Test]
    public function excludesNestedSectionByDotPath(): void
    {
        $config = $this->loader->load(
            __DIR__ . '/../Fixtures/config-nested',
            ['database.mysql'],
        );

        self::assertArrayNotHasKey('database.mysql', $config);
        self::assertArrayHasKey('database.sqlite', $config);
    }
}
