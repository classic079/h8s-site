<?php
/**
 * BTC Stalker API Proxy
 * Securely proxies external API calls to hide keys
 */

// Enable CORS for same-origin requests
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60'); // Cache for 1 minute

// API Keys (stored server-side, not exposed to client)
define('TWELVE_API_KEY', '830bdfca25d44bfe9992c9872d0693f5');
define('OIL_API_KEY', '30f7359b27f8a5ddc220f30004684bfac80a7e734ba3b8c5050c47f9f8649576');
define('WEATHER_API_KEY', '1591da776534a0f5e80ba9c577531a54');

// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';

// Rate limiting (simple cache to prevent abuse)
$cacheDir = sys_get_temp_dir() . '/btc_stalker_cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

function getCacheFile($key) {
    global $cacheDir;
    return $cacheDir . md5($key) . '.json';
}

function getCache($key, $ttl = 60) {
    $file = getCacheFile($key);
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function setCache($key, $data) {
    $file = getCacheFile($key);
    file_put_contents($file, json_encode($data));
}

function fetchWithCache($url, $headers = [], $ttl = 60) {
    $cacheKey = $url . json_encode($headers);

    // Check cache first
    $cached = getCache($cacheKey, $ttl);
    if ($cached !== null) {
        return $cached;
    }

    // Fetch from API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        setCache($cacheKey, $data);
        return $data;
    }

    return null;
}

// Route requests
switch ($endpoint) {
    case 'gold':
        // Fetch gold price from TwelveData
        $data = fetchWithCache(
            "https://api.twelvedata.com/price?symbol=XAU/USD&apikey=" . TWELVE_API_KEY,
            [],
            300 // Cache for 5 minutes
        );

        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch gold price']);
        }
        break;

    case 'oil':
        // Fetch oil price from OilPriceAPI
        $data = fetchWithCache(
            "https://api.oilpriceapi.com/v1/prices/latest",
            [
                "Authorization: Token " . OIL_API_KEY,
                "Content-Type: application/json"
            ],
            300 // Cache for 5 minutes
        );

        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch oil price']);
        }
        break;

    case 'weather':
        // Fetch weather from OpenWeather
        $zip = $_GET['zip'] ?? '01002';
        $type = $_GET['type'] ?? 'current'; // 'current' or 'forecast'

        if ($type === 'forecast') {
            $url = "https://api.openweathermap.org/data/2.5/forecast?zip={$zip},US&units=imperial&appid=" . WEATHER_API_KEY;
        } else {
            $url = "https://api.openweathermap.org/data/2.5/weather?zip={$zip},US&units=imperial&appid=" . WEATHER_API_KEY;
        }

        $data = fetchWithCache($url, [], 600); // Cache for 10 minutes

        if ($data) {
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch weather']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid endpoint',
            'available' => ['gold', 'oil', 'weather']
        ]);
        break;
}
?>
