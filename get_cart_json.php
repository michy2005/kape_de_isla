<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$response = ['items' => [], 'total' => 0, 'count' => 0];

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $response['items'][] = [
            'id'    => $item['id'], // Added ID
            'name'  => $item['name'],
            'price' => (float)$item['price'],
            'qty'   => (int)$item['quantity'],
            'temp'  => $item['temp']
        ];
        $response['total'] += ($item['price'] * $item['quantity']);
    }
    $response['count'] = count($items);
}

echo json_encode($response);