<?php

declare(strict_types=1);

/**
 * ConfigMerger
 *
 * Merges environment-specific configuration over base configuration.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Merger;

use PHPdot\Config\Util\Arr;

final class ConfigMerger
{
    /**
     * Merge environment-specific values over base configuration.
     *
     * For each section, keys that match known environment names are separated
     * from base keys. The current environment's values are deep-merged over
     * the base. All environment keys are removed from the result.
     *
     * @param array<string, mixed> $config The raw section-keyed configuration
     * @param string $environment The current environment name
     * @param list<string> $environments All known environment names
     *
     * @return array<string, mixed> The merged configuration without environment keys
     */
    public function merge(array $config, string $environment, array $environments): array
    {
        $result = [];

        foreach ($config as $section => $values) {
            if (!is_array($values)) {
                $result[$section] = $values;
                continue;
            }

            $base = [];
            $envValues = [];

            foreach ($values as $key => $value) {
                if (in_array($key, $environments, true)) {
                    if ($key === $environment && is_array($value)) {
                        $envValues = $value;
                    }
                } else {
                    $base[$key] = $value;
                }
            }

            $result[$section] = $envValues !== [] ? Arr::mergeRecursive($base, $envValues) : $base;
        }

        return $result;
    }
}
