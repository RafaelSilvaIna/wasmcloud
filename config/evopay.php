<?php

declare(strict_types=1);

return [
    'api_key' => getenv('EVOPAY_API_KEY') ?: '05165218-a666-4b15-9c82-b04387ae8d57',
    'base_url' => getenv('EVOPAY_BASE_URL') ?: 'https://pix.evopay.cash/v1',
    'webhook_secret' => getenv('EVOPAY_WEBHOOK_SECRET') ?: '',
];
