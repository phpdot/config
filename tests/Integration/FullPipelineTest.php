<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Integration;

use PHPdot\Config\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FullPipelineTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
        );
    }

    #[Test]
    public function loadMergeResolveVerifyAllValuesCorrect(): void
    {
        self::assertSame('TestApp', $this->config->get('app.name'));
        self::assertSame('https://testapp.com', $this->config->get('app.url'));
        self::assertTrue($this->config->get('app.debug'));
        self::assertSame('1.0.0', $this->config->get('app.version'));
        self::assertSame('localhost', $this->config->get('database.host'));
        self::assertSame(3306, $this->config->get('database.port'));
        self::assertSame('file', $this->config->get('cache.driver'));
        self::assertSame(3600, $this->config->get('cache.ttl'));
    }

    #[Test]
    public function placeholderAppNameResolvedInMailSection(): void
    {
        self::assertSame('TestApp', $this->config->get('mail.from_name'));
    }

    #[Test]
    public function placeholderAppUrlResolvedInMailSection(): void
    {
        self::assertSame('https://testapp.com/unsubscribe', $this->config->get('mail.from_url'));
    }

    #[Test]
    public function closuresExecutedDuringResolve(): void
    {
        $bootId = $this->config->get('services.boot_id');

        self::assertIsString($bootId);
        self::assertStringStartsWith('boot-', $bootId);
    }

    #[Test]
    public function placeholderChainedThroughServices(): void
    {
        self::assertSame('https://testapp.com/api/v1', $this->config->get('services.api_base'));
        self::assertSame('https://testapp.com/api/v1/webhooks', $this->config->get('services.webhook'));
    }

    #[Test]
    public function productionMergeWithPlaceholders(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/../Fixtures/config',
            environment: 'production',
            environments: ['staging', 'production'],
        );

        self::assertSame('prod-db.internal', $config->get('database.host'));
        self::assertSame(5432, $config->get('database.port'));
        self::assertSame('testdb', $config->get('database.name'));
        self::assertSame('TestApp', $config->get('mail.from_name'));
    }
}
