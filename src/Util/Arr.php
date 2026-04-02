<?php

declare(strict_types=1);

/**
 * Arr
 *
 * Internal array utility methods for configuration processing.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 *
 * @internal
 */

namespace PHPdot\Config\Util;

final class Arr
{
    /**
     * Flatten a nested array to dot-notation keys.
     * Only scalar values become leaf entries.
     *
     * @param array<int|string, mixed> $array The nested array to flatten
     * @param string $prefix The key prefix for recursion
     *
     * @return array<string, int|float|string|bool|null> The flattened key-value pairs
     */
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $stringKey = is_int($key) ? (string) $key : $key;
            $dotKey = $prefix === '' ? $stringKey : $prefix . '.' . $stringKey;

            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value, $dotKey));
            } elseif (is_scalar($value) || $value === null) {
                $result[$dotKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Deep merge two arrays. Values from $override replace $base.
     * Nested arrays are merged recursively. Non-array values overwrite.
     *
     * @param array<int|string, mixed> $base The base array
     * @param array<int|string, mixed> $override The array whose values take precedence
     *
     * @return array<int|string, mixed> The merged array
     */
    public static function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Resolve {section.key} placeholders in a value.
     * Only processes string values. Recursive with depth limit.
     * Unresolvable placeholders are left as-is.
     *
     * @param mixed $value The value to resolve
     * @param array<string, int|float|string|bool|null> $references The flat reference map for lookups
     * @param int $depth Current recursion depth
     * @param int $maxDepth Maximum recursion depth
     *
     * @return mixed The resolved value
     */
    public static function resolvePlaceholders(mixed $value, array $references, int $depth = 0, int $maxDepth = 5): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $replaced = false;

        /** @var string $resolved */
        $resolved = preg_replace_callback(
            '/\{([^}]+)\}/',
            static function (array $matches) use ($references, &$replaced): string {
                $key = $matches[1];

                if (array_key_exists($key, $references)) {
                    $replaced = true;
                    $refValue = $references[$key];

                    return (string) ($refValue ?? '');
                }

                return $matches[0];
            },
            $value,
        );

        if ($replaced && $depth < $maxDepth) {
            return self::resolvePlaceholders($resolved, $references, $depth + 1, $maxDepth);
        }

        return $resolved;
    }
}
