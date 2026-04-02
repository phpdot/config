<?php

declare(strict_types=1);

return [
    'api_base' => '{app.url}/api/v1',
    'webhook' => '{app.url}/api/v1/webhooks',
    'boot_id' => fn() => 'boot-' . time(),
];
