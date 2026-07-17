<?php

declare(strict_types=1);

namespace PHPdot\Config\Tests;

use PHPdot\Config\Configuration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;

final class ConfigurationTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration(
            path: __DIR__ . '/Fixtures/config',
        );
    }

    #[Test]
    public function getReturnsScalarValues(): void
    {
        self::assertSame('TestApp', $this->config->get('app.name'));
        self::assertSame(3306, $this->config->get('database.port'));
        self::assertTrue($this->config->get('app.debug'));
    }

    #[Test]
    public function getReturnsDefaultForMissingKeys(): void
    {
        self::assertSame('fallback', $this->config->get('app.missing', 'fallback'));
        self::assertSame(0, $this->config->get('nonexistent.key', 0));
    }

    #[Test]
    public function getReturnsNullForMissingWithoutDefault(): void
    {
        self::assertNull($this->config->get('app.nonexistent'));
        self::assertNull($this->config->get('totally.missing.key'));
    }

    #[Test]
    public function hasReturnsTrueForExistingKeys(): void
    {
        self::assertTrue($this->config->has('app.name'));
        self::assertTrue($this->config->has('database.host'));
        self::assertTrue($this->config->has('cache.driver'));
    }

    #[Test]
    public function hasReturnsFalseForMissingKeys(): void
    {
        self::assertFalse($this->config->has('app.nonexistent'));
        self::assertFalse($this->config->has('missing.section'));
    }

    #[Test]
    public function sectionReturnsFullArray(): void
    {
        $app = $this->config->section('app');

        self::assertIsArray($app);
        self::assertSame('TestApp', $app['name']);
        self::assertSame('https://testapp.com', $app['url']);
    }

    #[Test]
    public function sectionReturnsEmptyArrayForMissingSection(): void
    {
        self::assertSame([], $this->config->section('nonexistent'));
    }

    #[Test]
    public function searchFindsKeysByPrefix(): void
    {
        $results = $this->config->search('app');

        self::assertArrayHasKey('app.name', $results);
        self::assertArrayHasKey('app.url', $results);
        self::assertArrayHasKey('app.debug', $results);
    }

    #[Test]
    public function searchWithStripPrefixRemovesPrefix(): void
    {
        $results = $this->config->search('app', stripPrefix: true);

        self::assertArrayHasKey('name', $results);
        self::assertArrayHasKey('url', $results);
        self::assertArrayNotHasKey('app.name', $results);
    }

    #[Test]
    public function allReturnsAllFlattenedKeys(): void
    {
        $all = $this->config->all();

        self::assertIsArray($all);
        self::assertArrayHasKey('app.name', $all);
        self::assertArrayHasKey('database.host', $all);
        self::assertArrayHasKey('cache.driver', $all);
    }

    #[Test]
    public function sectionsReturnsSectionNamesSorted(): void
    {
        $sections = $this->config->sections();

        self::assertIsArray($sections);
        self::assertContains('app', $sections);
        self::assertContains('database', $sections);
        self::assertContains('cache', $sections);

        // Verify sorted
        $sorted = $sections;
        sort($sorted);
        self::assertSame($sorted, $sections);
    }

    #[Test]
    public function getEnvironmentReturnsEnvironment(): void
    {
        $config = new Configuration(
            path: __DIR__ . '/Fixtures/config',
            environment: 'production',
        );

        self::assertSame('production', $config->getEnvironment());
    }

    #[Test]
    public function getPathReturnsPath(): void
    {
        self::assertSame(
            __DIR__ . '/Fixtures/config',
            $this->config->getPath(),
        );
    }

    #[Test]
    public function reloadClearsAndReloadsState(): void
    {
        $before = $this->config->get('app.name');
        $this->config->reload();
        $after = $this->config->get('app.name');

        self::assertSame($before, $after);
    }

    #[Test]
    public function searchReturnsEmptyForNoMatches(): void
    {
        $result = $this->config->search('nonexistent');

        self::assertSame([], $result);
    }

    #[Test]
    public function dtoThrowsForNonExistentClass(): void
    {
        $this->expectException(ReflectionException::class);

        $this->config->dto('app', 'NonExistent\\ClassName');
    }

    #[Test]
    public function multiplePlaceholdersInSingleString(): void
    {
        $value = $this->config->get('mail.from_name');

        self::assertSame('TestApp', $value);
    }

    #[Test]
    public function searchWithNestedPrefix(): void
    {
        $result = $this->config->search('database');

        self::assertArrayHasKey('database.host', $result);
        self::assertArrayHasKey('database.port', $result);
    }

    #[Test]
    public function searchDoesNotMatchPartialSectionNames(): void
    {
        $result = $this->config->search('app');

        foreach (array_keys($result) as $key) {
            self::assertStringStartsWith('app.', $key);
        }
    }

    #[Test]
    public function searchWithTrailingDot(): void
    {
        $withDot = $this->config->search('database.');
        $withoutDot = $this->config->search('database');

        self::assertSame($withDot, $withoutDot);
    }

    #[Test]
    public function searchWithStripPrefixOnNestedKeys(): void
    {
        $result = $this->config->search('database', stripPrefix: true);

        self::assertArrayHasKey('host', $result);
        self::assertArrayHasKey('port', $result);
        self::assertArrayNotHasKey('database.host', $result);
    }
}
