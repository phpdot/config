<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPdot\Config\Tests\Stubs\MysqlConfigStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationNestedSectionTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(__DIR__ . '/Fixtures/config-nested');
    }

    #[Test]
    public function getsNestedValuesByDotPath(): void
    {
        self::assertSame('localhost', $this->config->get('database.mysql.host'));
        self::assertSame(3306, $this->config->get('database.mysql.port'));
        self::assertSame('/var/db/app.sqlite', $this->config->get('database.sqlite.path'));
        self::assertTrue($this->config->get('database.sqlite.foreignKeys'));
    }

    #[Test]
    public function getsArbitrarilyDeepValue(): void
    {
        self::assertSame('deep', $this->config->get('a.b.c.d.value'));
    }

    #[Test]
    public function deepMergesParentSharedSettingsWithChildDriverSections(): void
    {
        $database = $this->config->section('database');

        // Parent (database.php) shared keys coexist with child driver subsections.
        self::assertArrayHasKey('prefix', $database);
        self::assertArrayHasKey('slowQueryThreshold', $database);
        self::assertArrayHasKey('mysql', $database);
        self::assertArrayHasKey('sqlite', $database);
        self::assertSame('', $database['prefix']);
        self::assertSame(100, $database['slowQueryThreshold']);
    }

    #[Test]
    public function returnsNestedSectionByDotPath(): void
    {
        self::assertSame(
            ['host' => 'localhost', 'port' => 3306, 'charset' => 'utf8mb4'],
            $this->config->section('database.mysql'),
        );
    }

    #[Test]
    public function hydratesDtoFromNestedSection(): void
    {
        $mysql = $this->config->dto('database.mysql', MysqlConfigStub::class);

        self::assertSame('localhost', $mysql->host);
        self::assertSame(3306, $mysql->port);
        self::assertSame('utf8mb4', $mysql->charset);
    }

    #[Test]
    public function missingNestedSectionReturnsEmpty(): void
    {
        self::assertSame([], $this->config->section('database.oracle'));
    }

    #[Test]
    public function appliesEnvironmentOverrideToNestedSection(): void
    {
        $config = new Configuration(
            __DIR__ . '/Fixtures/config-nested-env',
            'production',
            ['production', 'staging'],
        );

        // The production block inside database/mysql.php must override the base
        // value on the nested section, and the env key itself must be stripped.
        self::assertSame('prod-db.internal', $config->get('database.mysql.host'));
        self::assertSame(3306, $config->get('database.mysql.port'));
        self::assertSame([], $config->section('database.mysql.production'));
    }

    #[Test]
    public function ignoresNonMatchingEnvironmentOverrideForNestedSection(): void
    {
        $config = new Configuration(
            __DIR__ . '/Fixtures/config-nested-env',
            'staging',
            ['production', 'staging'],
        );

        self::assertSame('localhost', $config->get('database.mysql.host'));
    }
}
