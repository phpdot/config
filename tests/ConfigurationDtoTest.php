<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPdot\Config\Tests\Stubs\DatabaseConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationDtoTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/Fixtures/config',
        );
    }

    #[Test]
    public function dtoHydratesReadonlyClassFromSection(): void
    {
        $dto = $this->config->dto('database', DatabaseConfig::class);

        self::assertInstanceOf(DatabaseConfig::class, $dto);
        self::assertSame('localhost', $dto->host);
        self::assertSame(3306, $dto->port);
        self::assertSame('testdb', $dto->name);
    }

    #[Test]
    public function dtoAutoCastsTypes(): void
    {
        $dto = $this->config->dto('database', DatabaseConfig::class);

        self::assertIsString($dto->host);
        self::assertIsInt($dto->port);
        self::assertIsString($dto->name);
        self::assertIsBool($dto->debug);
    }

    #[Test]
    public function dtoUsesDefaultValuesForMissingKeys(): void
    {
        $dto = $this->config->dto('database', DatabaseConfig::class);

        // password has default '' in the fixture, debug has default false in DTO
        self::assertSame('', $dto->password);
        self::assertFalse($dto->debug);
    }

    #[Test]
    public function dtoCachesInstances(): void
    {
        $dto1 = $this->config->dto('database', DatabaseConfig::class);
        $dto2 = $this->config->dto('database', DatabaseConfig::class);

        self::assertSame($dto1, $dto2);
    }

    #[Test]
    public function dtoThrowsForMissingRequiredParam(): void
    {
        $this->expectException(\Throwable::class);

        // cache section doesn't have the required fields for DatabaseConfig
        $this->config->dto('cache', DatabaseConfig::class);
    }
}
