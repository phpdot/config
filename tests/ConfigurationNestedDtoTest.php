<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPdot\Config\Tests\Stubs\CookieConfigStub;
use PHPdot\Config\Tests\Stubs\DeepConfigStub;
use PHPdot\Config\Tests\Stubs\HttpConfigStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigurationNestedDtoTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/Fixtures/config',
        );
    }

    #[Test]
    public function dtoHydratesNestedClassFromSubArray(): void
    {
        $http = $this->config->dto('http', HttpConfigStub::class);

        self::assertInstanceOf(HttpConfigStub::class, $http);
        self::assertSame(['10.0.0.0/8'], $http->trustedProxies);
        self::assertSame(31, $http->trustedHeaders);

        self::assertInstanceOf(CookieConfigStub::class, $http->cookie);
        self::assertFalse($http->cookie->secure);
        self::assertTrue($http->cookie->httpOnly);
        self::assertSame('Strict', $http->cookie->sameSite);
        self::assertSame('/admin', $http->cookie->path);
    }

    #[Test]
    public function dtoUsesNestedDefaultWhenSubArrayMissing(): void
    {
        $config = new Configuration(path: __DIR__ . '/Fixtures/config');

        // Section 'app' has no 'http' key — must fall back to nested default
        // We create a small stub for this scenario
        $stub = $config->dto('app', HttpConfigOptionalStub::class);

        self::assertInstanceOf(HttpConfigOptionalStub::class, $stub);
        self::assertInstanceOf(CookieConfigStub::class, $stub->cookie);
        self::assertTrue($stub->cookie->secure);  // baseline default
    }

    #[Test]
    public function dtoHydratesMultiLevelNesting(): void
    {
        $deep = $this->config->dto('deep', DeepConfigStub::class);

        self::assertInstanceOf(DeepConfigStub::class, $deep);
        self::assertSame('deep-app', $deep->name);

        self::assertInstanceOf(HttpConfigStub::class, $deep->http);
        self::assertSame(['172.16.0.0/12'], $deep->http->trustedProxies);
        self::assertSame(8, $deep->http->trustedHeaders);

        self::assertInstanceOf(CookieConfigStub::class, $deep->http->cookie);
        self::assertTrue($deep->http->cookie->secure);
        self::assertSame('None', $deep->http->cookie->sameSite);
        // Unspecified keys fall back to nested DTO defaults
        self::assertTrue($deep->http->cookie->httpOnly);
        self::assertSame('/', $deep->http->cookie->path);
    }

    #[Test]
    public function dtoNestedHydrationCoercesScalarsAtEachLevel(): void
    {
        $http = $this->config->dto('http', HttpConfigStub::class);

        self::assertIsBool($http->cookie->secure);
        self::assertIsString($http->cookie->sameSite);
        self::assertIsInt($http->trustedHeaders);
    }

    #[Test]
    public function dtoNestedHydrationThrowsWhenInnerRequiredKeyMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nested DTO');

        // 'nested' fixture has 'inner' but the inner DTO requires 'required' — not present.
        $this->config->dto('nested', NestedRequiredStub::class);
    }
}

/**
 * @internal
 */
final readonly class HttpConfigOptionalStub
{
    public function __construct(
        public CookieConfigStub $cookie = new CookieConfigStub(),
    ) {}
}

/**
 * @internal
 */
final readonly class NestedRequiredStub
{
    public function __construct(
        public RequiredFieldStub $inner,
    ) {}
}

/**
 * @internal
 */
final readonly class RequiredFieldStub
{
    public function __construct(
        public string $required,
    ) {}
}
