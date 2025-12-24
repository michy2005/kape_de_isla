<?php
// Prevent any PHP errors from showing up as text and breaking the JSON
error_reporting(0); 
header('Content-Type: application/json');

try {
    // Check if the path is correct (relative to this file)
    $dbPath = '../../db.php';
    if (!file_exists($dbPath)) {
        throw new Exception("Database config file not found.");
    }

    include $dbPath;

    // Fetch only the ID and Stock to keep it fast
    $stmt = $pdo->query("SELECT id, stock FROM products");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (Exception $e) {
    // Send a JSON error instead of a PHP text error
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}