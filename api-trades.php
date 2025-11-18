<?php
/**
 * BTC Stalker - Top Trades API
 * MySQL-based storage for top trades (replaces Firebase)
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Database configuration
// IMPORTANT: Update these credentials after creating the database in Hostinger hPanel
define('DB_HOST', 'localhost');
define('DB_NAME', 'u582515363_btc_stalker'); // Format: username_dbname
define('DB_USER', 'u582515363'); // Your Hostinger username
define('DB_PASS', ''); // Set this password in hPanel -> Databases

/**
 * Get database connection
 */
function getDbConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    return $conn;
}

/**
 * Initialize database tables (run once)
 */
function initDatabase() {
    $conn = getDbConnection();

    // Create top_trades table
    $sql = "CREATE TABLE IF NOT EXISTS top_trades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timeframe VARCHAR(10) NOT NULL,
        trade_type ENUM('buy', 'sell') NOT NULL,
        price DECIMAL(20, 2) NOT NULL,
        size DECIMAL(20, 8) NOT NULL,
        usd_value DECIMAL(20, 2) NOT NULL,
        trade_time BIGINT NOT NULL,
        last_update BIGINT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_timeframe (timeframe),
        INDEX idx_trade_type (trade_type),
        INDEX idx_trade_time (trade_time),
        UNIQUE KEY unique_trade (timeframe, trade_type, trade_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Save top trades for a specific timeframe
 */
function saveTopTrades($timeframe, $buys, $sells) {
    $conn = getDbConnection();
    $lastUpdate = round(microtime(true) * 1000); // JavaScript timestamp format

    try {
        $conn->beginTransaction();

        // Delete old trades for this timeframe
        $stmt = $conn->prepare("DELETE FROM top_trades WHERE timeframe = ?");
        $stmt->execute([$timeframe]);

        // Insert new buy trades
        $stmt = $conn->prepare("
            INSERT INTO top_trades (timeframe, trade_type, price, size, usd_value, trade_time, last_update)
            VALUES (?, 'buy', ?, ?, ?, ?, ?)
        ");

        foreach ($buys as $trade) {
            $stmt->execute([
                $timeframe,
                $trade['price'],
                $trade['size'],
                $trade['usd'],
                $trade['time'],
                $lastUpdate
            ]);
        }

        // Insert new sell trades
        $stmt = $conn->prepare("
            INSERT INTO top_trades (timeframe, trade_type, price, size, usd_value, trade_time, last_update)
            VALUES (?, 'sell', ?, ?, ?, ?, ?)
        ");

        foreach ($sells as $trade) {
            $stmt->execute([
                $timeframe,
                $trade['price'],
                $trade['size'],
                $trade['usd'],
                $trade['time'],
                $lastUpdate
            ]);
        }

        $conn->commit();

        return [
            'success' => true,
            'timeframe' => $timeframe,
            'buys_saved' => count($buys),
            'sells_saved' => count($sells)
        ];

    } catch (PDOException $e) {
        $conn->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Load all top trades
 */
function loadAllTopTrades() {
    $conn = getDbConnection();

    try {
        $stmt = $conn->query("
            SELECT timeframe, trade_type, price, size, usd_value, trade_time, last_update
            FROM top_trades
            ORDER BY timeframe, trade_type, usd_value DESC
        ");

        $trades = $stmt->fetchAll();

        // Group by timeframe
        $result = [];
        foreach ($trades as $trade) {
            $tf = $trade['timeframe'];
            if (!isset($result[$tf])) {
                $result[$tf] = [
                    'buys' => [],
                    'sells' => [],
                    'lastUpdate' => (int)$trade['last_update']
                ];
            }

            $tradeData = [
                'price' => (float)$trade['price'],
                'size' => (float)$trade['size'],
                'usd' => (float)$trade['usd_value'],
                'time' => (int)$trade['trade_time']
            ];

            if ($trade['trade_type'] === 'buy') {
                $result[$tf]['buys'][] = $tradeData;
            } else {
                $result[$tf]['sells'][] = $tradeData;
            }
        }

        return ['success' => true, 'data' => $result];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get database stats
 */
function getDatabaseStats() {
    $conn = getDbConnection();

    try {
        $stmt = $conn->query("
            SELECT
                timeframe,
                trade_type,
                COUNT(*) as count,
                MAX(last_update) as last_update
            FROM top_trades
            GROUP BY timeframe, trade_type
            ORDER BY timeframe, trade_type
        ");

        return [
            'success' => true,
            'stats' => $stmt->fetchAll()
        ];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ===== ROUTING =====

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'init':
        // Initialize database (run once)
        $result = initDatabase();
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Database initialized' : 'Database initialization failed'
        ]);
        break;

    case 'save':
        // Save top trades
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['timeframe']) || !isset($input['buys']) || !isset($input['sells'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $result = saveTopTrades($input['timeframe'], $input['buys'], $input['sells']);
        echo json_encode($result);
        break;

    case 'load':
        // Load all top trades
        $result = loadAllTopTrades();
        echo json_encode($result);
        break;

    case 'stats':
        // Get database stats
        $result = getDatabaseStats();
        echo json_encode($result);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid action',
            'available_actions' => ['init', 'save', 'load', 'stats']
        ]);
        break;
}
?>
