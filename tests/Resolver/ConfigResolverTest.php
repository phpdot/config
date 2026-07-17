<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Resolver;

use PHPdot\Config\Resolver\ConfigResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigResolverTest extends TestCase
{
    private ConfigResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ConfigResolver();
    }

    #[Test]
    public function executesClosuresAndReplacesWithReturnValues(): void
    {
        $config = [
            'app' => [
                'name' => 'TestApp',
                'computed' => static fn(): string => 'resolved_value',
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame('resolved_value', $result['app']['computed']);
    }

    #[Test]
    public function resolvesSectionKeyPlaceholders(): void
    {
        $config = [
            'app' => [
                'name' => 'TestApp',
            ],
            'mail' => [
                'from_name' => '{app.name}',
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame('TestApp', $result['mail']['from_name']);
    }

    #[Test]
    public function resolvesNestedSectionKeyPlaceholders(): void
    {
        $config = [
            'database' => [
                'connections' => [
                    'host' => 'localhost',
                ],
            ],
            'app' => [
                'db_host' => '{database.connections.host}',
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame('localhost', $result['app']['db_host']);
    }

    #[Test]
    public function resolvesChainedPlaceholders(): void
    {
        $config = [
            'app' => [
                'name' => 'TestApp',
                'url' => 'https://testapp.com',
            ],
            'mail' => [
                'from_url' => '{app.url}/unsubscribe',
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame('https://testapp.com/unsubscribe', $result['mail']['from_url']);
    }

    #[Test]
    public function leavesUnresolvablePlaceholdersAsIs(): void
    {
        $config = [
            'app' => [
                'title' => '{missing.key}',
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame('{missing.key}', $result['app']['title']);
    }

    #[Test]
    public function onlyResolvesStringValues(): void
    {
        $config = [
            'app' => [
                'port' => 8080,
                'debug' => true,
                'name' => null,
            ],
        ];

        $result = $this->resolver->resolve($config);

        self::assertSame(8080, $result['app']['port']);
        self::assertTrue($result['app']['debug']);
        self::assertNull($result['app']['name']);
    }
}
