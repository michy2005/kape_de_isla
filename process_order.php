<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];

// 1. Fetch items from the DATABASE cart
$cart_stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

// 2. Fetch User's Real Name from users table (Fixes the Undefined Key error)
$user_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch();
$full_customer_name = $user_data['first_name'] . ' ' . $user_data['last_name'];

// 3. Only proceed if the database cart has items
if ($_SERVER["REQUEST_METHOD"] == "POST" && count($cart_items) > 0) {
    
    // Fallback logic: Use the fetched DB name if POST is empty
    $customer_name = !empty($_POST['customer_name']) ? $_POST['customer_name'] : $full_customer_name;
    $address_id = $_POST['address_id'] ?? null; 
    $total = $_POST['total'] ?? 0;
    
    // Safety check: if no address was selected, stop the order
    if (!$address_id) {
        die("Order failed: No delivery address selected. Please go back to the cart and add/select an address.");
    }

    $items_array = [];
    foreach ($cart_items as $item) {
        $items_array[] = $item['name'] . " (x" . $item['quantity'] . ")";
    }
    $items_string = implode(", ", $items_array);

    try {
        $pdo->beginTransaction(); // Start transaction to ensure stock and order happen together

        // 4. INSERT INTO ORDERS
        $sql = "INSERT INTO orders (user_id, address_id, customer_name, items, total_amount, status, created_at) 
                VALUES (:uid, :aid, :name, :items, :total, 'Pending', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uid' => $user_id, 
            'aid' => $address_id, 
            'name' => $customer_name, 
            'items' => $items_string, 
            'total' => $total
        ]);

        // 5. DEDUCT STOCK FROM PRODUCTS
        foreach ($cart_items as $item) {
            $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $updateStock->execute([$item['quantity'], $item['product_id']]);
        }

        // 6. Clear the database cart
        $deleteCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $deleteCart->execute([$user_id]);
        
        $pdo->commit(); // Save all changes
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brewing Success | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <meta http-equiv="refresh" content="10;url=profile.php?ordered=true">
    <style>
        body { background: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass { background: rgba(44, 26, 18, 0.4); backdrop-filter: blur(20px); border: 1px solid rgba(202, 138, 75, 0.1); }
        
        .steam-line { width: 4px; height: 30px; background: rgba(255,255,255,0.3); border-radius: 4px; animation: steam 2s infinite ease-in-out; filter: blur(2px); }
        .steam-line:nth-child(2) { animation-delay: 0.5s; }
        .steam-line:nth-child(3) { animation-delay: 1s; }

        @keyframes steam {
            0% { transform: translateY(0) scaleY(1); opacity: 0; }
            50% { opacity: 0.8; }
            100% { transform: translateY(-40px) scaleY(1.5); opacity: 0; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6">
    <div class="glass p-12 md:p-16 rounded-[4rem] text-center max-w-lg border border-white/5 shadow-[0_30px_100px_rgba(0,0,0,0.8)]">
        <div class="relative w-24 h-24 bg-[#CA8A4B] rounded-3xl flex items-center justify-center mx-auto mb-10 shadow-2xl shadow-[#CA8A4B]/40">
            <div class="absolute -top-12 flex gap-3">
                <div class="steam-line"></div>
                <div class="steam-line"></div>
                <div class="steam-line"></div>
            </div>
            <i data-lucide="coffee" class="w-12 h-12 text-white"></i>
        </div>

        <h1 class="font-serif text-5xl text-white italic mb-4">Brewing Now!</h1>
        <p class="text-[#CA8A4B] text-[10px] tracking-[0.5em] uppercase font-bold mb-10">Order Received</p>
        
        <p class="text-stone-400 text-sm leading-relaxed mb-10 px-6">
            Your selection is being prepared by our baristas. We'll bring the island taste to your doorstep shortly.
        </p>

        <div class="flex flex-col gap-4">
            <a href="profile.php?ordered=true" class="bg-[#CA8A4B] text-white py-5 rounded-2xl font-bold tracking-[0.2em] text-[10px] uppercase hover:bg-[#b07840] transition-all">View My Profile</a>
            <a href="index.php" class="text-stone-500 hover:text-white py-2 text-[10px] tracking-widest uppercase transition">Return to Menu</a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php
    } catch (PDOException $e) { 
        $pdo->rollBack(); // Cancel changes if something fails
        die("Order failed: " . $e->getMessage()); 
    }
} else { 
    header("Location: index.php"); 
    exit; 
}
?>