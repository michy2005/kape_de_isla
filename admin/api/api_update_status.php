<?php
session_start();
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['admin_logged_in'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$_POST['status'], $_POST['order_id']])) {
        http_response_code(200);
        echo "Success";
    } else {
        http_response_code(500);
    }
}