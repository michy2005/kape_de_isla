<?php
include '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $rider_id = $_POST['rider_id'] ?? null;

    if (!$order_id || !$rider_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Data']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Assign rider to order and update order status
        $stmt = $pdo->prepare("UPDATE orders SET rider_id = ?, status = 'Out for Delivery' WHERE id = ?");
        $stmt->execute([$rider_id, $order_id]);

        // 2. Update rider status to 'On Delivery' so they don't show as available for other orders
        $stmt2 = $pdo->prepare("UPDATE riders SET status = 'On Delivery' WHERE id = ?");
        $stmt2->execute([$rider_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}