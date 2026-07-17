<?php

declare(strict_types=1);

// Settings shared across all database drivers. The active driver is selected by
// binding the connection contract in the container (DI) — never by a config
// value here. Per-driver parameters live in database/<driver>.php.
return [
    'prefix' => '',
    'slowQueryThreshold' => 100,
];
