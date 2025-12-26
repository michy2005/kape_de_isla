<?php
include '../../db.php';
header('Content-Type: application/json');

try {
    // We only want riders who are 'Available' to be assigned to new orders
    $stmt = $pdo->query("SELECT id, first_name, last_name, vehicle_details FROM riders WHERE status = 'Available'");
    $riders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($riders);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}