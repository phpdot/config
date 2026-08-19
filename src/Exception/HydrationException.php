<?php

declare(strict_types=1);

/**
 * A configuration array cannot be hydrated into its typed DTO.
 *
 * Raised by Configuration::dto() when a config value cannot be cast to the
 * constructor parameter's type or the target class cannot be instantiated —
 * inside the package hierarchy, so `catch (ConfigException)` covers
 * hydration failures alongside loader and cache errors.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Config\Exception;

final class HydrationException extends ConfigException {}
