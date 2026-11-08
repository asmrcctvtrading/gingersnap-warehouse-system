<?php
/**
 * Quick update script for camera RTSP URLs
 * Note: This script only runs on localhost for safety
 */

// Only allow localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== 'localhost') {
    die('Access denied');
}

$dbHost = 'localhost';
$dbUser = 'yitrmcft_ojpascual';
$dbPass = 'p@$$w0rd@123';
$dbName = 'yitrmcft_picklecam';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Update camera URLs with correct Hikvision format
    $updates = [
        1 => '/Streaming/Channels/101',
        2 => '/Streaming/Channels/102',
        3 => '/Streaming/Channels/103',
        4 => '/Streaming/Channels/104',
    ];

    echo "=== Updating Camera URLs ===\n";
    foreach ($updates as $courtId => $path) {
        $url = "rtsp://admin:User13919@192.168.100.41:554" . $path;

        $stmt = $pdo->prepare("UPDATE cameras SET stream_url = ? WHERE court_id = ?");
        $stmt->execute([$url, $courtId]);

        echo "✓ Court $courtId → $path\n";
    }

    // Verify
    echo "\n=== Verification ===\n";
    $stmt = $pdo->query("SELECT court_id, name, stream_url FROM cameras WHERE court_id BETWEEN 1 AND 4");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "Court {$row['court_id']}: {$row['stream_url']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
