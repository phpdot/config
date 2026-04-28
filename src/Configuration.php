<?php

declare(strict_types=1);

/**
 * Configuration
 *
 * Main entry point for configuration management. Loads, merges, resolves,
 * and caches configuration, providing dot-notation access and DTO hydration.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config;

use InvalidArgumentException;
use PHPdot\Config\Loader\ConfigLoader;
use PHPdot\Config\Merger\ConfigMerger;
use PHPdot\Config\Resolver\ConfigResolver;
use PHPdot\Config\Util\Arr;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;

final class Configuration
{
    /** @var array<string, int|float|string|bool|null> Flattened dot-notation map */
    private array $flat = [];

    /** @var array<string, mixed> Section-keyed resolved config */
    private array $resolved = [];

    /** @var array<string, object> DTO cache */
    private array $dtoCache = [];

    /**
     * Create a new configuration manager.
     *
     * Immediately loads and processes configuration from the given path.
     *
     * @param string $path Directory path containing PHP config files
     * @param string $environment The current environment name
     * @param list<string> $environments All known environment names
     * @param string|null $cachePath Path to the cache file, or null to disable caching
     */
    public function __construct(
        private readonly string $path,
        private readonly string $environment = '',
        private readonly array $environments = [],
        private readonly ?string $cachePath = null,
    ) {
        $this->load();
    }

    /**
     * Get a configuration value by dot-notation key.
     *
     * Returns scalar values from the O(1) flat map. Falls back to walking
     * the resolved tree for nested array access.
     *
     * @param string $key The dot-notation key
     * @param mixed $default The default value if key is not found
     *
     * @return mixed The configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Fast path: scalar value from flat map
        if (array_key_exists($key, $this->flat)) {
            return $this->flat[$key];
        }

        // Slow path: walk resolved tree for nested arrays
        $parts = explode('.', $key);
        $current = $this->resolved;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Get a string configuration value.
     *
     * @param string $key The dot-notation key
     * @param string $default The default value
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : $default;
    }

    /**
     * Get an integer configuration value.
     *
     * @param string $key The dot-notation key
     * @param int $default The default value
     */
    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get a float configuration value.
     *
     * @param string $key The dot-notation key
     * @param float $default The default value
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        if (is_float($value)) {
            return $value;
        }

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Get a boolean configuration value.
     *
     * Interprets 'true'/'false'/'yes'/'no'/'on'/'off'/'1'/'0' as booleans.
     *
     * @param string $key The dot-notation key
     * @param bool $default The default value
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off' => false,
                default => $default,
            };
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return $default;
    }

    /**
     * Get an array configuration value.
     *
     * @param string $key The dot-notation key
     * @param array<mixed> $default The default value
     *
     * @return array<mixed> The configuration array or default
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : $default;
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key The dot-notation key
     *
     * @return bool True if the key exists
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->flat)) {
            return true;
        }

        // Check resolved tree for array keys
        $parts = explode('.', $key);
        $current = $this->resolved;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return false;
            }
            $current = $current[$part];
        }

        return true;
    }

    /**
     * Get all values for a configuration section.
     *
     * @param string $section The section name
     *
     * @return array<string, mixed> The section values, or empty array if not found
     */
    public function section(string $section): array
    {
        if (!isset($this->resolved[$section])) {
            return [];
        }

        $value = $this->resolved[$section];

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }

    /**
     * Search for configuration keys matching a prefix.
     *
     * A dot is appended to the prefix automatically to prevent partial matches
     * (e.g. 'database' won't match 'database_backup'). Pass 'database.options'
     * to search within nested keys.
     *
     * @param string $prefix The dot-notation prefix (e.g. 'database', 'database.options')
     * @param bool $stripPrefix Whether to remove the prefix from result keys
     *
     * @return array<string, int|float|string|bool|null> Matching key-value pairs
     */
    public function search(string $prefix, bool $stripPrefix = false): array
    {
        $result = [];
        $searchPrefix = str_ends_with($prefix, '.') ? $prefix : $prefix . '.';
        $prefixLength = strlen($searchPrefix);

        foreach ($this->flat as $key => $value) {
            if (str_starts_with($key, $searchPrefix)) {
                $resultKey = $stripPrefix ? substr($key, $prefixLength) : $key;
                $result[$resultKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Get all flattened configuration values.
     *
     * @return array<string, int|float|string|bool|null> All dot-notation key-value pairs
     */
    public function all(): array
    {
        return $this->flat;
    }

    /**
     * Get all section names.
     *
     * @return list<string> The section names
     */
    public function sections(): array
    {
        return array_keys($this->resolved);
    }

    /**
     * Hydrate a DTO from a configuration section.
     *
     * Constructor parameters are matched to configuration keys by name.
     * Type casting is applied based on parameter type hints.
     * Results are cached for subsequent calls with the same section and class.
     *
     * @template T of object
     *
     * @param string $section The configuration section name
     * @param class-string<T> $class The DTO class name
     *
     *
     * @throws InvalidArgumentException If a required parameter is missing from config
     * @return T The hydrated DTO instance
     */
    public function dto(string $section, string $class): object
    {
        $cacheKey = $section . ':' . $class;

        if (isset($this->dtoCache[$cacheKey])) {
            $cached = $this->dtoCache[$cacheKey];

            if ($cached instanceof $class) {
                return $cached;
            }
        }

        $data = $this->section($section);
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            $instance = $reflection->newInstance();
            $this->dtoCache[$cacheKey] = $instance;

            return $instance;
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[] = $this->castValue($data[$name], $param->getType());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException(
                    "Missing required config key '{$name}' in section '{$section}' for {$class}",
                );
            }
        }

        $instance = $reflection->newInstanceArgs($args);
        $this->dtoCache[$cacheKey] = $instance;

        return $instance;
    }

    /**
     * Reload all configuration from disk, clearing all caches.
     */
    public function reload(): void
    {
        $this->flat = [];
        $this->resolved = [];
        $this->dtoCache = [];

        if ($this->cachePath !== null) {
            ConfigCache::clear($this->cachePath);
        }

        $this->load();
    }

    /**
     * Get the current environment name.
     *
     * @return string The environment name
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get the configuration directory path.
     *
     * @return string The directory path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Load, merge, resolve, and optionally cache configuration.
     */
    private function load(): void
    {
        if ($this->cachePath !== null) {
            $cached = ConfigCache::read($this->cachePath);

            if ($cached !== null) {
                $this->resolved = $cached;
                $this->flat = Arr::flatten($cached);

                return;
            }
        }

        $loader = new ConfigLoader();
        $config = $loader->load($this->path);

        if ($this->environments !== []) {
            $merger = new ConfigMerger();
            $config = $merger->merge($config, $this->environment, $this->environments);
        }

        $resolver = new ConfigResolver();
        $config = $resolver->resolve($config);

        $this->resolved = $config;
        $this->flat = Arr::flatten($config);

        if ($this->cachePath !== null) {
            ConfigCache::write($config, $this->cachePath);
        }
    }

    /**
     * Cast a configuration value to the expected parameter type.
     *
     * Scalars are coerced to int/float/string/bool. Arrays whose target type
     * is a class are recursively hydrated as nested DTOs.
     *
     * @param mixed $value The raw configuration value
     * @param ReflectionType|null $type The target parameter type
     *
     * @return mixed The cast value
     */
    private function castValue(mixed $value, ?ReflectionType $type): mixed
    {
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        if (is_array($value) && class_exists($typeName)) {
            /** @var array<string, mixed> $value */
            return $this->hydrateClass($typeName, $value);
        }

        if (!is_scalar($value) && $value !== null) {
            return $value;
        }

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => $this->castBool($value),
            default => $value,
        };
    }

    /**
     * Hydrate a class from an associative array by matching constructor
     * parameters to keys. Recurses through castValue() so nested DTOs and
     * scalar coercion both work together.
     *
     * @param class-string $class
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException If a required constructor parameter has no matching key
     */
    private function hydrateClass(string $class, array $data): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[] = $this->castValue($data[$name], $param->getType());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException(
                    "Missing required key '{$name}' for nested DTO {$class}",
                );
            }
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Cast a value to boolean with string interpretation.
     *
     * @param mixed $value The value to cast
     *
     * @return bool The boolean result
     */
    private function castBool(mixed $value): bool
    {
        if (is_string($value)) {
            return match (strtolower($value)) {
                'true', '1', 'yes', 'on' => true,
                'false', '0', 'no', 'off' => false,
                default => (bool) $value,
            };
        }

        return (bool) $value;
    }
}
