<?php

declare(strict_types=1);

/**
 * ConfigCache
 *
 * Reads and writes cached configuration as PHP files.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config;

use PHPdot\Config\Exception\ConfigCacheException;

final class ConfigCache
{
    /**
     * Write a configuration array to a cache file atomically.
     *
     * The file is written to a temporary path first, then renamed to ensure
     * atomic writes. The generated file is a valid PHP file returning an array.
     *
     * @param array<string, mixed> $config The configuration to cache
     * @param string $path The cache file path
     *
     *
     * @throws ConfigCacheException If the file cannot be written
     */
    public static function write(array $config, string $path): void
    {
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";

        $tempPath = $path . '.' . uniqid('', true) . '.tmp';

        if (file_put_contents($tempPath, $content) === false) {
            throw ConfigCacheException::writeFailure($path);
        }

        if (!rename($tempPath, $path)) {
            unlink($tempPath);
            throw ConfigCacheException::writeFailure($path);
        }
    }

    /**
     * Read a cached configuration file.
     *
     * @param string $path The cache file path
     *
     * @return array<string, mixed>|null The cached configuration, or null if not available
     */
    public static function read(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $data = require $path;

        if (!is_array($data)) {
            return null;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * Clear a cached configuration file.
     *
     * Silently ignores missing files.
     *
     * @param string $path The cache file path
     */
    public static function clear(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
