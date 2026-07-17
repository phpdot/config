<?php

declare(strict_types=1);

/**
 * ConfigResolver
 *
 * Resolves closures and placeholders in configuration values.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Resolver;

use Closure;
use PHPdot\Config\Util\Arr;

final class ConfigResolver
{
    /**
     * Resolve all closures and placeholders in the configuration.
     *
     * Pass 1: Walk the tree and execute any closures, replacing them with
     * their return values.
     * Pass 2: Flatten the config to a dot-notation map, then walk the tree
     * resolving {section.key} placeholders.
     *
     * @param array<string, mixed> $config The section-keyed configuration
     *
     * @return array<string, mixed> The fully resolved configuration
     */
    public function resolve(array $config): array
    {
        $resolved = [];

        foreach ($config as $key => $value) {
            $resolved[$key] = $this->resolveValue($value);
        }

        $flat = Arr::flatten($resolved);

        $result = [];

        foreach ($resolved as $key => $value) {
            $result[$key] = $this->resolvePlaceholderValue($value, $flat);
        }

        return $result;
    }

    /**
     * Resolve closures in a single value, recursing into arrays.
     *
     * @param mixed $value The value to resolve
     *
     * @return mixed The resolved value
     */
    private function resolveValue(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $value();
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $k => $v) {
                $result[$k] = $this->resolveValue($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * Resolve placeholders in a single value, recursing into arrays.
     *
     * @param mixed $value The value to resolve
     * @param array<string, int|float|string|bool|null> $flat The flattened reference map
     *
     * @return mixed The resolved value
     */
    private function resolvePlaceholderValue(mixed $value, array $flat): mixed
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $k => $v) {
                $result[$k] = $this->resolvePlaceholderValue($v, $flat);
            }

            return $result;
        }

        return Arr::resolvePlaceholders($value, $flat);
    }
}
