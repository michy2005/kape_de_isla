<?php
include '../../db.php';
header('Content-Type: application/json');

// Get parameters from the request
$status = $_GET['status'] ?? 'All';
$sort = $_GET['sort'] ?? 'DESC';

// Ensure $sort is only ASC or DESC to prevent SQL injection
if ($sort !== 'ASC') { $sort = 'DESC'; }

/** * 1. STATS CALCULATION
 * Include 'Archived' in revenue so earnings don't "disappear" after 24h 
 */
$revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('Delivered', 'Archived')")->fetchColumn() ?: 0;
$today_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pending', 'Brewing')")->fetchColumn();

/** * 2. ORDER FETCHING LOGIC
 * We hide 'Archived' orders from the main list to keep it clean.
 */
$sql = "SELECT orders.*, ua.house_no, ua.street, ua.barangay, ua.notes 
        FROM orders 
        LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
        WHERE orders.status != 'Archived'";

if ($status !== 'All') {
    $sql .= " AND orders.status = :s";
}

// Apply the dynamic sorting
$sql .= " ORDER BY orders.created_at $sort LIMIT 15";

$stmt = $pdo->prepare($sql);
if ($status !== 'All') {
    $stmt->execute(['s' => $status]);
} else {
    $stmt->execute();
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($orders as $o) {
    // Fetch Items for this specific order
    $item_stmt = $pdo->prepare("SELECT oi.quantity, p.name, oi.mode FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $item_stmt->execute([$o['id']]);
    $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $items_html = "";
    foreach($items as $i) {
        $items_html .= "<p class='text-[11px] text-stone-300'>
                            <span class='text-[#CA8A4B] font-bold'>{$i['quantity']}x</span> {$i['name']} 
                            <span class='text-[8px] opacity-50 uppercase'>({$i['mode']})</span>
                        </p>";
    }

    $data[] = [
        'id' => $o['id'],
        'customer_name' => $o['customer_name'],
        'time' => date('h:i A', strtotime($o['created_at'])),
        'items_html' => $items_html,
        'address' => $o['house_no'] ? "{$o['house_no']} {$o['street']}, {$o['barangay']}" : null,
        'note' => $o['notes'],
        'total_amount' => $o['total_amount'],
        'status' => $o['status']
    ];
}

// Return combined JSON
echo json_encode([
    'revenue' => $revenue,
    'today_count' => $today_count,
    'pending_count' => $pending_count,
    'orders' => $data
]);