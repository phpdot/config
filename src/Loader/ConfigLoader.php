<?php

declare(strict_types=1);

/**
 * ConfigLoader
 *
 * Loads PHP configuration files from a directory.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Loader;

use PHPdot\Config\Exception\ConfigLoaderException;

final class ConfigLoader
{
    /**
     * Load all PHP configuration files from a directory.
     *
     * Each file must return an array. Files are keyed by their lowercase
     * basename without extension. Files in the exclude list are skipped.
     *
     * @param string $path Directory path containing PHP config files
     * @param list<string> $exclude Filenames to skip (without extension)
     *
     *
     * @throws ConfigLoaderException If the directory does not exist or a file is not readable
     * @return array<string, mixed> Section-keyed configuration arrays
     */
    public function load(string $path, array $exclude = []): array
    {
        if (!is_dir($path)) {
            throw ConfigLoaderException::directoryNotFound($path);
        }

        $files = glob($path . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        $config = [];

        foreach ($files as $file) {
            if (!is_readable($file)) {
                throw ConfigLoaderException::fileNotReadable($file);
            }

            $basename = strtolower(basename($file, '.php'));

            if (in_array($basename, $exclude, true)) {
                continue;
            }

            $data = require $file;

            if (!is_array($data)) {
                continue;
            }

            $config[$basename] = $data;
        }

        return $config;
    }
}
