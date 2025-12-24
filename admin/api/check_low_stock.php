<?php
include '../../db.php';

// Fetch products with stock less than 5
$stmt = $pdo->query("SELECT name, stock FROM products WHERE stock < 5 ORDER BY stock ASC");
$low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($low_stock_items);