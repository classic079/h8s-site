<?php
/**
 * Firebase to MySQL Migration Script
 * One-time use to migrate top trades data
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
  <title>Firebase to MySQL Migration</title>
  <style>
    body {
      font-family: system-ui, -apple-system, sans-serif;
      max-width: 800px;
      margin: 50px auto;
      padding: 20px;
      background: #0a0a0a;
      color: #e9fef7;
    }
    h1 { color: #2dd4bf; }
    .status {
      background: #111;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
      border: 1px solid #333;
    }
    .success { color: #2dd4bf; }
    .error { color: #f59e0b; }
    button {
      background: #2dd4bf;
      color: #0a0a0a;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
    }
    button:hover { background: #5eead4; }
    pre {
      background: #000;
      padding: 15px;
      border-radius: 6px;
      overflow-x: auto;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <h1>🔄 Firebase → MySQL Migration</h1>

  <div class="status">
    <h3>Instructions:</h3>
    <ol>
      <li>This page will connect to Firebase and read your top trades data</li>
      <li>Click the button below to start the migration</li>
      <li>Data will be saved to your MySQL database</li>
    </ol>
  </div>

  <form method="post">
    <button type="submit" name="migrate">Start Migration</button>
  </form>

  <?php
  if (isset($_POST['migrate'])) {
      echo '<div class="status">';
      echo '<h3>Migration Progress:</h3>';

      // Fetch data from Firebase
      echo '<p>📖 Reading from Firebase...</p>';

      $firebaseUrl = 'https://btc-stalker-default-rtdb.firebaseio.com/topTrades.json';
      $firebaseData = @file_get_contents($firebaseUrl);

      if ($firebaseData === false) {
          echo '<p class="error">❌ Failed to connect to Firebase</p>';
          echo '</div>';
          exit;
      }

      $data = json_decode($firebaseData, true);

      if (!$data || empty($data)) {
          echo '<p class="error">❌ No data found in Firebase</p>';
          echo '</div>';
          exit;
      }

      echo '<p class="success">✅ Found data for ' . count($data) . ' timeframes</p>';
      echo '<pre>';

      $totalBuys = 0;
      $totalSells = 0;

      foreach ($data as $timeframe => $tfData) {
          $buys = isset($tfData['buys']) ? count($tfData['buys']) : 0;
          $sells = isset($tfData['sells']) ? count($tfData['sells']) : 0;
          $totalBuys += $buys;
          $totalSells += $sells;
          echo "  - {$timeframe}: {$buys} buys, {$sells} sells\n";
      }

      echo "\nTotal trades: " . ($totalBuys + $totalSells) . "\n";
      echo '</pre>';

      // Migrate to MySQL
      echo '<p>💾 Saving to MySQL...</p>';
      echo '<pre>';

      $successCount = 0;
      $failCount = 0;

      foreach ($data as $timeframe => $tfData) {
          $payload = [
              'timeframe' => $timeframe,
              'buys' => isset($tfData['buys']) ? $tfData['buys'] : [],
              'sells' => isset($tfData['sells']) ? $tfData['sells'] : []
          ];

          // Call our API
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, 'https://h8s.us/api-trades.php?action=save');
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

          $response = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          if ($httpCode === 200) {
              $result = json_decode($response, true);
              if ($result && isset($result['success']) && $result['success']) {
                  $successCount++;
                  echo "  ✅ {$timeframe}: {$result['buys_saved']} buys, {$result['sells_saved']} sells saved\n";
              } else {
                  $failCount++;
                  $error = isset($result['error']) ? $result['error'] : 'Unknown error';
                  echo "  ❌ {$timeframe}: {$error}\n";
              }
          } else {
              $failCount++;
              echo "  ❌ {$timeframe}: HTTP {$httpCode}\n";
          }
      }

      echo '</pre>';

      if ($failCount === 0) {
          echo '<p class="success">🎉 Migration Complete!</p>';
          echo '<p>Successfully migrated ' . $successCount . ' timeframes with ' . ($totalBuys + $totalSells) . ' total trades.</p>';
          echo '<p>Visit <a href="https://h8s.us" style="color: #2dd4bf;">https://h8s.us</a> and refresh to see your persisted trades!</p>';
      } else {
          echo '<p class="error">⚠️ Migration completed with errors</p>';
          echo '<p>' . $successCount . ' succeeded, ' . $failCount . ' failed</p>';
      }

      echo '</div>';
  }
  ?>
</body>
</html>
