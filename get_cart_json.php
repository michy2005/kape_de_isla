<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$response = ['items' => [], 'total' => 0, 'count' => 0];

if (isset($_SESSION['user_id'])) {
    // JOIN with products to get stock and image data
    $stmt = $pdo->prepare("
        SELECT c.*, p.stock, p.image_url, p.image_url_iced, p.image_url_hot, p.has_iced, p.has_hot 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        // Determine correct image based on temp
        $img = $item['image_url'];
        if ($item['temp'] == 'Iced' && !empty($item['image_url_iced'])) $img = $item['image_url_iced'];
        if ($item['temp'] == 'Hot' && !empty($item['image_url_hot'])) $img = $item['image_url_hot'];

        $response['items'][] = [
            'id'       => $item['id'],
            'name'     => $item['name'],
            'price'    => (float)$item['price'],
            'qty'      => (int)$item['quantity'],
            'temp'     => $item['temp'],
            'stock'    => (int)$item['stock'],
            'img'      => $img,
            'has_iced' => (bool)$item['has_iced'],
            'has_hot'  => (bool)$item['has_hot']
        ];
        $response['total'] += ($item['price'] * $item['quantity']);
    }
    $response['count'] = count($items);
}

echo json_encode($response);