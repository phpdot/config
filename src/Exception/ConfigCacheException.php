<?php

declare(strict_types=1);

/**
 * ConfigCacheException
 *
 * Exception thrown when configuration caching operations fail.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Exception;

final class ConfigCacheException extends ConfigException
{
    /**
     * Create exception for a cache write failure.
     *
     * @param string $path The cache file path that could not be written
     */
    public static function writeFailure(string $path): self
    {
        return new self("Failed to write config cache: {$path}");
    }

    /**
     * Create exception for an invalid cache file format.
     *
     * @param string $path The cache file path with invalid format
     */
    public static function invalidCacheFormat(string $path): self
    {
        return new self("Invalid config cache format: {$path}");
    }
}
