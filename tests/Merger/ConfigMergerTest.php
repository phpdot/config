<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Merger;

use PHPdot\Config\Merger\ConfigMerger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigMergerTest extends TestCase
{
    private ConfigMerger $merger;

    /** @var list<string> */
    private array $environments;

    protected function setUp(): void
    {
        $this->merger = new ConfigMerger();
        $this->environments = ['development', 'staging', 'production'];
    }

    #[Test]
    public function mergesCurrentEnvironmentOverBase(): void
    {
        $config = [
            'app' => [
                'name' => 'MyApp',
                'debug' => true,
                'production' => [
                    'debug' => false,
                ],
                'staging' => [
                    'debug' => false,
                ],
                'development' => [
                    'debug' => true,
                ],
            ],
        ];

        $result = $this->merger->merge($config, 'production', $this->environments);

        self::assertFalse($result['app']['debug']);
    }

    #[Test]
    public function inheritsBaseValuesNotOverridden(): void
    {
        $config = [
            'app' => [
                'name' => 'MyApp',
                'debug' => true,
                'production' => [
                    'debug' => false,
                ],
                'staging' => [],
                'development' => [],
            ],
        ];

        $result = $this->merger->merge($config, 'production', $this->environments);

        self::assertSame('MyApp', $result['app']['name']);
    }

    #[Test]
    public function removesAllEnvironmentKeysFromResult(): void
    {
        $config = [
            'app' => [
                'name' => 'MyApp',
                'production' => ['debug' => false],
                'staging' => ['debug' => true],
                'development' => ['debug' => true],
            ],
        ];

        $result = $this->merger->merge($config, 'production', $this->environments);

        self::assertArrayNotHasKey('production', $result['app']);
        self::assertArrayNotHasKey('staging', $result['app']);
        self::assertArrayNotHasKey('development', $result['app']);
    }

    #[Test]
    public function deepMergesNestedArrays(): void
    {
        $config = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'production' => [
                    'host' => 'prod-db.internal',
                    'port' => 5432,
                ],
                'staging' => [],
                'development' => [],
            ],
        ];

        $result = $this->merger->merge($config, 'production', $this->environments);

        self::assertSame('prod-db.internal', $result['database']['host']);
        self::assertSame(5432, $result['database']['port']);
    }

    #[Test]
    public function returnsBaseWhenEnvironmentHasNoOverride(): void
    {
        $config = [
            'app' => [
                'name' => 'MyApp',
                'debug' => true,
                'production' => ['debug' => false],
                'staging' => [],
                'development' => [],
            ],
        ];

        $result = $this->merger->merge($config, 'development', $this->environments);

        self::assertTrue($result['app']['debug']);
        self::assertSame('MyApp', $result['app']['name']);
    }

    #[Test]
    public function returnsRawConfigWhenEnvironmentsListIsEmpty(): void
    {
        $config = [
            'app' => [
                'name' => 'MyApp',
                'debug' => true,
            ],
        ];

        $result = $this->merger->merge($config, 'production', []);

        self::assertSame('MyApp', $result['app']['name']);
        self::assertTrue($result['app']['debug']);
    }

    #[Test]
    public function handlesSectionWithNoEnvironmentKeys(): void
    {
        $config = [
            'cache' => [
                'driver' => 'file',
                'ttl' => 3600,
            ],
        ];

        $result = $this->merger->merge($config, 'production', $this->environments);

        self::assertSame('file', $result['cache']['driver']);
        self::assertSame(3600, $result['cache']['ttl']);
    }
}
