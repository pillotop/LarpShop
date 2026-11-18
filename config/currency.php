<?php

return [
    // Catalog prices and checkout amounts remain in USD.
    'base' => 'USD',
    'supported' => ['USD', 'VND'],
    'rates_url' => 'https://open.er-api.com/v6/latest/USD',
    'cache_key' => 'currency.usd-vnd.v1',
];
