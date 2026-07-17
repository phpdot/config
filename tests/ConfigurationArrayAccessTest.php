<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationArrayAccessTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/Fixtures/config-arrays',
        );
    }

    // ─── get() with nested arrays ───

    #[Test]
    public function getReturnsScalarLeafValues(): void
    {
        self::assertSame('smtp', $this->config->get('services.mailer'));
        self::assertSame(100, $this->config->get('services.rate_limit'));
    }

    #[Test]
    public function getReturnsDeeplyNestedScalar(): void
    {
        self::assertSame('127.0.0.1', $this->config->get('services.connections.mysql.host'));
        self::assertSame(3306, $this->config->get('services.connections.mysql.port'));
        self::assertSame(6379, $this->config->get('services.connections.redis.port'));
    }

    #[Test]
    public function getReturnsNestedAssociativeArray(): void
    {
        $mysql = $this->config->get('services.connections.mysql');
        self::assertIsArray($mysql);
        self::assertSame(['host' => '127.0.0.1', 'port' => 3306], $mysql);
    }

    #[Test]
    public function getReturnsParentArrayWithMultipleChildren(): void
    {
        $connections = $this->config->get('services.connections');
        self::assertIsArray($connections);
        self::assertArrayHasKey('mysql', $connections);
        self::assertArrayHasKey('redis', $connections);
        self::assertSame('127.0.0.1', $connections['mysql']['host']);
    }

    #[Test]
    public function getReturnsEntireSectionAsArray(): void
    {
        $services = $this->config->get('services');
        self::assertIsArray($services);
        self::assertSame('smtp', $services['mailer']);
        self::assertArrayHasKey('connections', $services);
        self::assertArrayHasKey('middleware', $services);
    }

    #[Test]
    public function getReturnsListArray(): void
    {
        $middleware = $this->config->get('services.middleware');
        self::assertIsArray($middleware);
        self::assertSame(['auth', 'cors', 'throttle'], $middleware);
    }

    #[Test]
    public function getReturnsListItemByIndex(): void
    {
        self::assertSame('auth', $this->config->get('services.middleware.0'));
        self::assertSame('cors', $this->config->get('services.middleware.1'));
        self::assertSame('throttle', $this->config->get('services.middleware.2'));
    }

    #[Test]
    public function getReturnsEmptyArray(): void
    {
        $result = $this->config->get('services.empty_list');
        self::assertIsArray($result);
        self::assertSame([], $result);
    }

    #[Test]
    public function getReturnsDefaultForMissingKey(): void
    {
        self::assertNull($this->config->get('services.missing'));
        self::assertSame('fallback', $this->config->get('services.missing', 'fallback'));
        self::assertSame([], $this->config->get('nonexistent.section', []));
    }

    #[Test]
    public function getReturnsDefaultForMissingNestedKey(): void
    {
        self::assertNull($this->config->get('services.connections.postgres'));
        self::assertNull($this->config->get('services.connections.mysql.charset'));
    }

    #[Test]
    public function getReturnsBooleanFromNestedArray(): void
    {
        self::assertTrue($this->config->get('services.features.dark_mode'));
        self::assertFalse($this->config->get('services.features.beta'));
    }

    // ─── has() with nested arrays ───

    #[Test]
    public function hasReturnsTrueForArrayKeys(): void
    {
        self::assertTrue($this->config->has('services.connections'));
        self::assertTrue($this->config->has('services.connections.mysql'));
        self::assertTrue($this->config->has('services.middleware'));
        self::assertTrue($this->config->has('services'));
    }

    #[Test]
    public function hasReturnsTrueForScalarKeys(): void
    {
        self::assertTrue($this->config->has('services.mailer'));
        self::assertTrue($this->config->has('services.connections.mysql.host'));
    }

    #[Test]
    public function hasReturnsFalseForMissingKeys(): void
    {
        self::assertFalse($this->config->has('services.missing'));
        self::assertFalse($this->config->has('services.connections.postgres'));
        self::assertFalse($this->config->has('nonexistent'));
    }

    #[Test]
    public function hasReturnsTrueForEmptyArray(): void
    {
        self::assertTrue($this->config->has('services.empty_list'));
    }

    // ─── Typed getters ───

    #[Test]
    public function stringReturnsStringValue(): void
    {
        self::assertSame('smtp', $this->config->string('services.mailer'));
    }

    #[Test]
    public function stringReturnsDefaultForNonString(): void
    {
        self::assertSame('', $this->config->string('services.rate_limit'));
        self::assertSame('default', $this->config->string('services.missing', 'default'));
    }

    #[Test]
    public function integerReturnsIntValue(): void
    {
        self::assertSame(100, $this->config->integer('services.rate_limit'));
        self::assertSame(3306, $this->config->integer('services.connections.mysql.port'));
    }

    #[Test]
    public function integerReturnsDefaultForNonInt(): void
    {
        self::assertSame(0, $this->config->integer('services.mailer'));
        self::assertSame(42, $this->config->integer('services.missing', 42));
    }

    #[Test]
    public function floatReturnsNumericAsFloat(): void
    {
        self::assertEqualsWithDelta(100.0, $this->config->float('services.rate_limit'), 0.01);
    }

    #[Test]
    public function floatReturnsDefaultForNonNumeric(): void
    {
        self::assertEqualsWithDelta(0.0, $this->config->float('services.mailer'), 0.01);
        self::assertEqualsWithDelta(3.14, $this->config->float('services.missing', 3.14), 0.01);
    }

    #[Test]
    public function booleanReturnsBoolValue(): void
    {
        self::assertTrue($this->config->boolean('services.features.dark_mode'));
        self::assertFalse($this->config->boolean('services.features.beta'));
    }

    #[Test]
    public function booleanReturnsDefaultForMissing(): void
    {
        self::assertFalse($this->config->boolean('services.missing'));
        self::assertTrue($this->config->boolean('services.missing', true));
    }

    #[Test]
    public function arrayReturnsArrayValue(): void
    {
        self::assertSame(['auth', 'cors', 'throttle'], $this->config->array('services.middleware'));
    }

    #[Test]
    public function arrayReturnsNestedArrayValue(): void
    {
        $connections = $this->config->array('services.connections');
        self::assertArrayHasKey('mysql', $connections);
        self::assertArrayHasKey('redis', $connections);
    }

    #[Test]
    public function arrayReturnsDefaultForScalar(): void
    {
        self::assertSame([], $this->config->array('services.mailer'));
        self::assertSame(['fallback'], $this->config->array('services.missing', ['fallback']));
    }

    #[Test]
    public function arrayReturnsEmptyArrayForEmptyList(): void
    {
        self::assertSame([], $this->config->array('services.empty_list'));
    }
}
