<?php

declare(strict_types=1);

/**
 * ConfigLoaderException
 *
 * Exception thrown when configuration files cannot be loaded.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Exception;

final class ConfigLoaderException extends ConfigException
{
    /**
     * Create exception for a missing configuration directory.
     *
     * @param string $path The directory path that was not found
     */
    public static function directoryNotFound(string $path): self
    {
        return new self("Config directory not found: {$path}");
    }

    /**
     * Create exception for a configuration file that cannot be read.
     *
     * @param string $path The file path that is not readable
     */
    public static function fileNotReadable(string $path): self
    {
        return new self("Config file not readable: {$path}");
    }
}
