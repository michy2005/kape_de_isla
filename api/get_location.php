<?php
// api/get_location.php
header('Content-Type: application/json');

// Security: Only allow logged-in users to use the proxy
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = urlencode($_GET['lat']);
    $lon = urlencode($_GET['lon']);
    
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
    
    // Nominatim REQUIRES a User-Agent header or they will block the request
    $opts = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: KapeDeIsla_App_Localhost\r\n"
        ]
    ];
    
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        echo json_encode(['error' => 'Failed to reach location service']);
    } else {
        echo $result;
    }
} else {
    echo json_encode(['error' => 'Missing coordinates']);
}
?>