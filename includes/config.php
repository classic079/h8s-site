<?php
/**
 * BTC Stalker Configuration
 * v13.53
 */

// Page Settings
define('PAGE_TITLE', 'BTC Stalker - Multi-Timeframe');
define('VERSION', 'v13.53');

// Default Settings
define('DEFAULT_ZIP', '43440'); // Default weather ZIP code
define('DEFAULT_THEME', 'dark'); // dark or light

// Cache Settings (in seconds)
define('GOLD_CACHE_TTL', 1800); // 30 minutes
define('OIL_CACHE_TTL', 3600);  // 1 hour
define('WEATHER_CACHE_TTL', 600); // 10 minutes

// Feature Flags
define('ENABLE_TOP_TRADES', true);
define('ENABLE_NEWS_FEED', true);
define('ENABLE_WEATHER', true);
define('ENABLE_COMMODITIES', true);

// Timeframes
$timeframes = [
    '30d' => ['label' => '30 DAYS', 'interval' => 3600000],
    '24h' => ['label' => '24 HOURS', 'interval' => 300000],
    '12h' => ['label' => '12 HOURS', 'interval' => 120000],
    '8h'  => ['label' => '8 HOURS', 'interval' => 60000],
    '1h'  => ['label' => '1 HOUR', 'interval' => 10000],
    '10m' => ['label' => '10 MIN', 'interval' => 2000],
    '5m'  => ['label' => '5 MIN', 'interval' => 1000],
    '1m'  => ['label' => '1 MIN', 'interval' => 500]
];
?>
