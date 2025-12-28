<?php
include '../db.php';

if (isset($_GET['order_id']) && isset($_GET['lat']) && isset($_GET['lng'])) {
    $stmt = $pdo->prepare("UPDATE orders SET rider_lat = ?, rider_lng = ? WHERE id = ?");
    $stmt->execute([$_GET['lat'], $_GET['lng'], $_GET['order_id']]);
    echo "Success";
}
?>