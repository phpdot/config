# phpdot/config

Configuration management for PHP: load, merge, resolve, cache.

## Install

```bash
composer require phpdot/config
```

Zero dependencies. Pure PHP 8.3+.

## Quick Start

```php
use PHPdot\Config\Configuration;

$config = new Configuration(path: __DIR__ . '/config');

$config->get('database.host');                // 'localhost'
$config->get('database.port');                // 3306
$config->get('database.missing', 'default');  // 'default'
```

---

## Architecture

```
config/*.php files
      │
      ▼
ConfigLoader         Scans directory, requires each file
      │
      ▼
ConfigMerger         Overlays current environment values
      │
      ▼
ConfigResolver       Executes closures, resolves {section.key} placeholders
      │
      ▼
Configuration        Flattens to dot-notation, provides get/has/section/dto
      │
      ▼
ConfigCache          Optional: dumps to single PHP file for production
```

---

## Config Files

Each file in the config directory returns an array. The filename becomes the section name.

```php
// config/database.php
return [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'myapp',
    'username' => 'root',
    'password' => '',
];
```

Access with dot notation:

```php
$config->get('database.host');              // 'localhost'
$config->get('database.port');              // 3306
$config->get('database.options.timeout');   // nested values work too
```

---

## Environment Overrides

Config files can include environment-specific overrides as top-level keys:

```php
// config/database.php
return [
    'host' => 'localhost',
    'port' => 3306,
    'debug' => true,

    'staging' => [
        'host' => 'staging-db.internal',
    ],

    'production' => [
        'host' => 'prod-db.internal',
        'port' => 5432,
        'debug' => false,
    ],
];
```

```php
$config = new Configuration(
    path: __DIR__ . '/config',
    environment: 'production',
    environments: ['development', 'staging', 'production'],
);

$config->get('database.host');   // 'prod-db.internal' (overridden)
$config->get('database.port');   // 5432 (overridden)
$config->get('database.debug');  // false (overridden)
$config->get('database.name');   // 'myapp' (inherited from base)
```

Values not overridden are inherited from base. Environment keys are removed from the result.

---

## Placeholders

Reference values from other sections with `{section.key}` syntax:

```php
// config/app.php
return [
    'name' => 'MyApp',
    'url' => 'https://myapp.com',
];

// config/mail.php
return [
    'from_name' => '{app.name}',
    'footer' => 'Sent from {app.name}',
];

// config/services.php
return [
    'api_base' => '{app.url}/api/v1',
    'webhook' => '{services.api_base}/webhooks',  // chained
];
```

```php
$config->get('mail.from_name');     // 'MyApp'
$config->get('services.webhook');   // 'https://myapp.com/api/v1/webhooks'
```

Unresolvable placeholders are left as-is. No errors thrown.

---

## Dynamic Values

Closures are executed once during resolution:

```php
return [
    'secret' => fn() => bin2hex(random_bytes(32)),
    'boot_time' => fn() => date('Y-m-d H:i:s'),
];
```

---

## DTO Hydration

Auto-hydrate any class from a config section:

```php
readonly class DatabaseConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public string $name,
        public string $username,
        public string $password = '',
        public bool $debug = false,
    ) {}
}

$db = $config->dto('database', DatabaseConfig::class);
$db->host;    // 'prod-db.internal'
$db->port;    // 5432 (int, auto-cast)
$db->debug;   // false (bool, auto-cast)
```

No base class needed. No interface needed. Config keys matched to constructor parameter names. Types auto-cast for scalars. Parameters with defaults are optional. Cached per section+class.

---

## Caching

Skip parsing in production:

```php
// Production — cached
$config = new Configuration(
    path: __DIR__ . '/config',
    environment: 'production',
    environments: ['development', 'staging', 'production'],
    cachePath: __DIR__ . '/storage/cache/config.php',
);

// First request: loads, merges, resolves, writes cache
// Subsequent requests: single require from cache (opcache-friendly)
```

```php
// CLI: clear cache on deploy
ConfigCache::clear(__DIR__ . '/storage/cache/config.php');
```

---

## Full API

```php
$config->get('key', $default);              // scalar value or default
$config->has('key');                         // bool
$config->section('database');               // full array for section
$config->sections();                        // ['app', 'cache', 'database', ...]
$config->search('database');                // all database.* keys
$config->search('database', stripPrefix: true); // same, without prefix
$config->all();                             // all flattened key-value pairs
$config->dto('database', DbConfig::class);  // DTO hydration
$config->reload();                          // re-run pipeline (clears cache)
$config->getEnvironment();                  // current environment
$config->getPath();                         // config directory path
```

---

## Package Structure

```
src/
├── Configuration.php          Main entry point
├── ConfigCache.php            Cache read/write/clear
├── Loader/
│   └── ConfigLoader.php       Directory scanner
├── Merger/
│   └── ConfigMerger.php       Environment override merging
├── Resolver/
│   └── ConfigResolver.php     Closures + placeholder resolution
├── Util/
│   └── Arr.php                Internal array helpers
└── Exception/
    ├── ConfigException.php    Base exception
    ├── ConfigLoaderException.php
    └── ConfigCacheException.php
```

---

## Development

```bash
composer test        # PHPUnit (78 tests)
composer analyse     # PHPStan level 10
composer cs-fix      # PHP-CS-Fixer
composer check       # All three
```

## License

MIT
