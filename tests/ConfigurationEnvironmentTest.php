<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationEnvironmentTest extends TestCase
{
    /** @var list<string> */
    private array $environments = ['development', 'staging', 'production'];

    #[Test]
    public function productionEnvironmentOverridesHost(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/Fixtures/config-env',
            environment: 'production',
            environments: $this->environments,
        );

        self::assertSame('prod-db.internal', $config->get('database.host'));
        self::assertSame(5432, $config->get('database.port'));
    }

    #[Test]
    public function stagingEnvironmentOverridesUrl(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/Fixtures/config-env',
            environment: 'staging',
            environments: $this->environments,
        );

        self::assertSame('https://staging.example.com', $config->get('app.url'));
        self::assertFalse($config->get('app.debug'));
    }

    #[Test]
    public function developmentEnvironmentUsesBaseValues(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/Fixtures/config-env',
            environment: 'development',
            environments: $this->environments,
        );

        self::assertSame('EnvApp', $config->get('app.name'));
        self::assertTrue($config->get('app.debug'));
        self::assertSame('http://localhost', $config->get('app.url'));
    }

    #[Test]
    public function environmentKeysRemovedFromSections(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/Fixtures/config-env',
            environment: 'production',
            environments: $this->environments,
        );

        $app = $config->section('app');

        self::assertArrayNotHasKey('production', $app);
        self::assertArrayNotHasKey('staging', $app);
        self::assertArrayNotHasKey('development', $app);
    }
}
