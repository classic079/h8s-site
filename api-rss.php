<?php
/**
 * BTC Stalker RSS Feed Proxy
 * Fetches and parses RSS feeds directly, eliminating rss2json.com dependency
 * Includes server-side caching for better performance
 */

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Cache-Control: public, max-age=180'); // Cache for 3 minutes

// Cache configuration
$cacheDir = sys_get_temp_dir() . '/btc_stalker_rss_cache/';
$cacheTTL = 180; // 3 minutes (matches original refresh rate)

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

function getCacheFile($url) {
    global $cacheDir;
    return $cacheDir . md5($url) . '.json';
}

function getCache($url) {
    global $cacheTTL;
    $file = getCacheFile($url);
    if (file_exists($file) && (time() - filemtime($file)) < $cacheTTL) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function setCache($url, $data) {
    $file = getCacheFile($url);
    file_put_contents($file, json_encode($data));
}

function fetchRSSFeed($url) {
    // Check cache first
    $cached = getCache($url);
    if ($cached !== null) {
        return $cached;
    }

    // Fetch RSS feed
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    // Handle SSL certificates properly
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $xml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$xml) {
        return ['status' => 'error', 'message' => "HTTP $httpCode: $error"];
    }

    // Suppress XML parsing errors
    libxml_use_internal_errors(true);

    // Try to load XML
    $rss = simplexml_load_string($xml);

    if ($rss === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return ['status' => 'error', 'message' => 'XML parse error: ' . $errors[0]->message ?? 'Unknown error'];
    }

    // Parse RSS items
    $items = [];

    // Handle RSS 2.0 format
    if (isset($rss->channel->item)) {
        foreach ($rss->channel->item as $item) {
            $items[] = parseRSSItem($item);
        }
    }
    // Handle Atom format
    elseif (isset($rss->entry)) {
        foreach ($rss->entry as $entry) {
            $items[] = parseAtomEntry($entry);
        }
    }

    $result = [
        'status' => 'ok',
        'feed' => [
            'url' => $url,
            'title' => (string)($rss->channel->title ?? $rss->title ?? 'Unknown'),
        ],
        'items' => $items
    ];

    // Cache the result
    setCache($url, $result);

    return $result;
}

function parseRSSItem($item) {
    // Get namespaces for media content
    $namespaces = $item->getNameSpaces(true);
    $media = $item->children($namespaces['media'] ?? 'http://search.yahoo.com/mrss/');
    $dc = $item->children($namespaces['dc'] ?? 'http://purl.org/dc/elements/1.1/');

    // Extract thumbnail
    $thumbnail = '';
    if (isset($media->thumbnail)) {
        $thumbnail = (string)$media->thumbnail->attributes()->url;
    } elseif (isset($media->content)) {
        $thumbnail = (string)$media->content->attributes()->url;
    }

    // Extract description (try multiple fields)
    $description = '';
    if (isset($item->description)) {
        $description = (string)$item->description;
    } elseif (isset($media->description)) {
        $description = (string)$media->description;
    } elseif (isset($item->summary)) {
        $description = (string)$item->summary;
    }

    // Clean HTML from description
    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Get publication date
    $pubDate = '';
    if (isset($item->pubDate)) {
        $pubDate = (string)$item->pubDate;
    } elseif (isset($dc->date)) {
        $pubDate = (string)$dc->date;
    }

    return [
        'title' => (string)$item->title,
        'pubDate' => $pubDate,
        'link' => (string)$item->link,
        'guid' => (string)($item->guid ?? $item->link),
        'author' => (string)($item->author ?? $dc->creator ?? ''),
        'thumbnail' => $thumbnail,
        'description' => substr($description, 0, 300), // Limit to 300 chars
        'content' => $description
    ];
}

function parseAtomEntry($entry) {
    // Handle Atom format (used by some feeds)
    $namespaces = $entry->getNameSpaces(true);

    $link = '';
    if (isset($entry->link)) {
        if (isset($entry->link->attributes()->href)) {
            $link = (string)$entry->link->attributes()->href;
        } else {
            $link = (string)$entry->link;
        }
    }

    $description = '';
    if (isset($entry->summary)) {
        $description = strip_tags((string)$entry->summary);
    } elseif (isset($entry->content)) {
        $description = strip_tags((string)$entry->content);
    }

    return [
        'title' => (string)$entry->title,
        'pubDate' => (string)($entry->published ?? $entry->updated ?? ''),
        'link' => $link,
        'guid' => (string)($entry->id ?? $link),
        'author' => (string)($entry->author->name ?? ''),
        'thumbnail' => '',
        'description' => substr($description, 0, 300),
        'content' => $description
    ];
}

// Get RSS URL from query parameter
$rssUrl = $_GET['rss_url'] ?? '';
$count = intval($_GET['count'] ?? 10);

if (empty($rssUrl)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing rss_url parameter'
    ]);
    exit;
}

// Fetch and parse the RSS feed
$result = fetchRSSFeed($rssUrl);

// Limit items to requested count
if ($result['status'] === 'ok' && isset($result['items'])) {
    $result['items'] = array_slice($result['items'], 0, $count);
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
