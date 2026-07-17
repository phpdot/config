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

use FilesystemIterator;
use PHPdot\Config\Exception\ConfigLoaderException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ConfigLoader
{
    /**
     * Load all PHP configuration files from a directory, recursively.
     *
     * Each file must return an array. The section key is the file's path
     * relative to the base directory, lowercased, with the `.php` extension
     * removed and directory separators replaced by dots — so
     * `database/mysql.php` becomes section `database.mysql`. Top-level files
     * keep their plain basename (`database.php` -> `database`). A file or its
     * relative dot-path matching the exclude list is skipped.
     *
     * The directory separator (`/`) is the only path separator: directory and
     * file names must not contain dots, since dots delimit nested sections.
     *
     * @param string $path Directory path containing PHP config files
     * @param list<string> $exclude Section keys or basenames to skip (without extension)
     *
     * @throws ConfigLoaderException If the directory does not exist or a file is not readable
     *
     * @return array<string, mixed> Section-keyed configuration arrays
     */
    public function load(string $path, array $exclude = []): array
    {
        if (!is_dir($path)) {
            throw ConfigLoaderException::directoryNotFound($path);
        }

        $base = rtrim($path, '/') . '/';
        $config = [];

        foreach ($this->phpFiles($path) as $file) {
            if (!is_readable($file)) {
                throw ConfigLoaderException::fileNotReadable($file);
            }

            $relative = substr($file, strlen($base), -4);
            $section = strtolower(str_replace('/', '.', $relative));
            $leaf = strtolower(basename($file, '.php'));

            if (in_array($section, $exclude, true) || in_array($leaf, $exclude, true)) {
                continue;
            }

            $data = require $file;

            if (!is_array($data)) {
                continue;
            }

            $config[$section] = $data;
        }

        return $config;
    }

    /**
     * Recursively collect all `.php` files under a directory, sorted for
     * deterministic ordering.
     *
     * @param string $path The directory to scan
     *
     * @return list<string> Absolute file paths
     */
    private function phpFiles(string $path): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && strtolower($entry->getExtension()) === 'php') {
                $files[] = $entry->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
