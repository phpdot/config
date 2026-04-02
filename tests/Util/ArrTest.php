<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests\Util;

use PHPdot\Config\Util\Arr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    #[Test]
    public function flattenConvertsNestedArrayToDotNotation(): void
    {
        $input = [
            'app' => [
                'name' => 'TestApp',
                'debug' => true,
            ],
        ];

        $result = Arr::flatten($input);

        self::assertSame('TestApp', $result['app.name']);
        self::assertTrue($result['app.debug']);
    }

    #[Test]
    public function flattenHandlesDeeplyNestedThreeLevels(): void
    {
        $input = [
            'database' => [
                'connections' => [
                    'host' => 'localhost',
                ],
            ],
        ];

        $result = Arr::flatten($input);

        self::assertSame('localhost', $result['database.connections.host']);
    }

    #[Test]
    public function flattenSkipsArrayValuesOnlyScalarLeaves(): void
    {
        $input = [
            'key' => 'value',
            'nested' => [
                'inner' => 'leaf',
            ],
        ];

        $result = Arr::flatten($input);

        self::assertSame('value', $result['key']);
        self::assertSame('leaf', $result['nested.inner']);
        self::assertArrayNotHasKey('nested', $result);
    }

    #[Test]
    public function flattenWithPrefix(): void
    {
        $input = [
            'host' => 'localhost',
            'port' => 3306,
        ];

        $result = Arr::flatten($input, 'database');

        self::assertSame('localhost', $result['database.host']);
        self::assertSame(3306, $result['database.port']);
    }

    #[Test]
    public function mergeRecursiveOverridesScalarValues(): void
    {
        $base = ['host' => 'localhost', 'port' => 3306];
        $override = ['host' => 'prod-db.internal'];

        $result = Arr::mergeRecursive($base, $override);

        self::assertSame('prod-db.internal', $result['host']);
        self::assertSame(3306, $result['port']);
    }

    #[Test]
    public function mergeRecursiveMergesNestedArrays(): void
    {
        $base = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ];
        $override = [
            'database' => [
                'host' => 'prod-db.internal',
            ],
        ];

        $result = Arr::mergeRecursive($base, $override);

        self::assertSame('prod-db.internal', $result['database']['host']);
        self::assertSame(3306, $result['database']['port']);
    }

    #[Test]
    public function mergeRecursiveOverrideWinsForNonArray(): void
    {
        $base = ['debug' => true];
        $override = ['debug' => false];

        $result = Arr::mergeRecursive($base, $override);

        self::assertFalse($result['debug']);
    }

    #[Test]
    public function resolvePlaceholdersReplacesKeyPatterns(): void
    {
        $references = [
            'app.name' => 'TestApp',
        ];

        $result = Arr::resolvePlaceholders('{app.name}', $references);

        self::assertSame('TestApp', $result);
    }

    #[Test]
    public function resolvePlaceholdersLeavesUnresolvableAsIs(): void
    {
        $references = [];

        $result = Arr::resolvePlaceholders('{missing.key}', $references);

        self::assertSame('{missing.key}', $result);
    }

    #[Test]
    public function resolvePlaceholdersSkipsNonStringValues(): void
    {
        $references = ['app.port' => 8080];

        self::assertSame(42, Arr::resolvePlaceholders(42, $references));
        self::assertTrue(Arr::resolvePlaceholders(true, $references));
        self::assertNull(Arr::resolvePlaceholders(null, $references));
    }

    #[Test]
    public function resolvePlaceholdersHandlesChainedPlaceholders(): void
    {
        $references = [
            'app.name' => 'TestApp',
            'greeting' => 'Hello {app.name}',
        ];

        $result = Arr::resolvePlaceholders('{greeting}', $references);

        self::assertSame('Hello TestApp', $result);
    }

    #[Test]
    public function resolvePlaceholdersRespectsDepthLimit(): void
    {
        // Create a self-referencing chain that would loop forever
        $references = [
            'a' => '{b}',
            'b' => '{a}',
        ];

        // Should not infinite loop — stops at maxDepth
        $result = Arr::resolvePlaceholders('{a}', $references, 0, 3);

        self::assertIsString($result);
    }
}
