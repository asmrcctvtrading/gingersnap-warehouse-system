<?php
/**
 * Camera RTSP URL Update API
 * Updates camera stream URLs to correct Hikvision format
 * Method: POST with JSON payload
 */

header('Content-Type: application/json');

$secret = 'CEOSJH4g8Wsl30n7jT1aq52BbuIRP6xp';
$providedSecret = $_GET['secret'] ?? $_POST['secret'] ?? null;

if ($providedSecret !== $secret) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$dbHost = 'localhost';
$dbUser = 'yitrmcft_ojpascual';
$dbPass = 'p@$$w0rd@123';
$dbName = 'yitrmcft_picklecam';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Define correct RTSP URLs
    $cameras = [
        1 => 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/101',
        2 => 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/102',
        3 => 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/103',
        4 => 'rtsp://admin:User13919@192.168.100.41:554/Streaming/Channels/104',
    ];

    $updated = [];
    foreach ($cameras as $courtId => $url) {
        $stmt = $pdo->prepare("UPDATE cameras SET stream_url = ? WHERE court_id = ?");
        $result = $stmt->execute([$url, $courtId]);

        if ($result) {
            $updated[] = [
                'court_id' => $courtId,
                'url' => $url,
                'status' => 'updated'
            ];
        }
    }

    // Verify updates
    $stmt = $pdo->query("SELECT court_id, name, stream_url FROM cameras WHERE court_id BETWEEN 1 AND 4 ORDER BY court_id");
    $verified = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Camera URLs updated successfully',
        'updated_count' => count($updated),
        'updated' => $updated,
        'verified' => $verified,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
