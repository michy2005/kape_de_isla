<?php
error_reporting(0);
include 'db.php';
header('Content-Type: application/json');

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    try {
        // Fetch location directly from the orders table
        $stmt = $pdo->prepare("SELECT rider_lat as lat, rider_lng as lng FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($location && $location['lat'] != null) {
            echo json_encode([
                'lat' => (float)$location['lat'],
                'lng' => (float)$location['lng']
            ]);
        } else {
            echo json_encode(['error' => 'Rider has not started moving']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error']);
    }
}
?>