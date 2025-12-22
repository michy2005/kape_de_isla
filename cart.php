<?php 
session_start();
include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. ADD TO DATABASE CART
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['id'];
    $name = ($_POST['temp'] ?? 'Iced') . " " . ($_POST['name'] ?? 'Coffee');
    $price = (float)$_POST['price'];
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $temp = $_POST['temp'] ?? 'Iced';

    $check = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND temp = ?");
    $check->execute([$user_id, $product_id, $temp]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$qty, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, name, price, quantity, temp) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $name, $price, $qty, $temp]);
    }

    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') { exit; }
    header("Location: cart.php"); exit();
}

// 2. UPDATE CART ITEM (NEW LOGIC)
if (isset($_POST['update_cart'])) {
    $cart_id = $_POST['cart_id'];
    $new_qty = (int)$_POST['quantity'];
    $new_temp = $_POST['temp'];
    
    // Update name to reflect temp change (e.g., "Hot Coffee" instead of "Iced Coffee")
    $current_item = $pdo->prepare("SELECT name FROM cart WHERE id = ?");
    $current_item->execute([$cart_id]);
    $item_data = $current_item->fetch();
    
    // Simple string replacement for the name
    $clean_name = str_replace(['Iced ', 'Hot '], '', $item_data['name']);
    $new_name = $new_temp . " " . $clean_name;

    $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, temp = ?, name = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_qty, $new_temp, $new_name, $cart_id, $user_id]);
    
    header("Location: cart.php"); exit();
}

// 3. REMOVE FROM DATABASE CART
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    header("Location: cart.php"); exit();
}

// 4. CLEAR DATABASE CART
if (isset($_GET['clear'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: index.php"); exit();
}

$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Basket | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        input[type="number"]::-webkit-inner-spin-button { display: none; }
    </style>
</head>
<body class="text-stone-200 min-h-screen font-sans">
    
    <nav class="p-8 flex justify-between items-center max-w-7xl mx-auto">
        <a href="index.php" class="text-[#CA8A4B] text-xs font-bold tracking-widest flex items-center gap-2 uppercase">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Continue Brewing
        </a>
        <a href="?clear=1" class="text-stone-500 hover:text-white text-[10px] tracking-widest font-bold uppercase transition">Clear Basket</a>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-12">
        <h2 class="font-serif text-5xl mb-12 text-white italic">Your Selection</h2>

        <?php if (count($cart_items) > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <div class="lg:col-span-2 space-y-4">
                <?php 
                $total = 0; 
                foreach($cart_items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal; 
                ?>
                <div class="glass-dark p-6 rounded-2xl border-white/5 animate-fade-in relative overflow-hidden group">
                    <div id="view-<?= $item['id'] ?>" class="flex justify-between items-center">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 bg-stone-800 rounded-xl flex items-center justify-center text-[#CA8A4B]">
                                <i data-lucide="coffee"></i>
                            </div>
                            <div>
                                <h4 class="text-white font-serif text-xl italic"><?= htmlspecialchars($item['name']) ?></h4>
                                <p class="text-stone-500 text-[10px] tracking-widest uppercase"><?= $item['temp'] ?> • Qty: <?= $item['quantity'] ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="text-white font-bold">₱<?= number_format($subtotal, 2) ?></span>
                            <div class="flex gap-3">
                                <button onclick="toggleEdit(<?= $item['id'] ?>)" class="text-stone-600 hover:text-[#CA8A4B] transition-colors">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <a href="?remove=<?= $item['id'] ?>" class="text-stone-600 hover:text-red-400 transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <form id="edit-<?= $item['id'] ?>" method="POST" class="hidden">
                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <select name="temp" class="bg-black/40 border border-white/10 rounded-lg p-2 text-[10px] uppercase font-bold text-stone-300 outline-none">
                                    <option value="Iced" <?= $item['temp'] == 'Iced' ? 'selected' : '' ?>>Iced</option>
                                    <option value="Hot" <?= $item['temp'] == 'Hot' ? 'selected' : '' ?>>Hot</option>
                                </select>
                                <div class="flex items-center bg-black/40 border border-white/10 rounded-lg">
                                    <button type="button" onclick="this.nextElementSibling.stepDown()" class="px-3 text-stone-500 hover:text-white">-</button>
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99" class="bg-transparent w-10 text-center text-sm font-bold text-white outline-none">
                                    <button type="button" onclick="this.previousElementSibling.stepUp()" class="px-3 text-stone-500 hover:text-white">+</button>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="toggleEdit(<?= $item['id'] ?>)" class="px-4 py-2 text-[10px] uppercase font-bold text-stone-500">Cancel</button>
                                <button type="submit" name="update_cart" class="bg-[#CA8A4B] px-6 py-2 rounded-xl text-[10px] uppercase font-bold text-white shadow-lg shadow-[#CA8A4B]/20">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="lg:col-span-1">
                <div class="glass-dark p-8 rounded-[2.5rem] border border-coffee-accent/20 sticky top-24">
                    <h3 class="font-serif text-2xl text-white mb-6 italic text-center">Checkout</h3>
                    <form action="process_order.php" method="POST" class="space-y-6">
                        <div>
                            <label class="text-[10px] text-stone-500 uppercase tracking-widest mb-2 block font-bold">Customer Name</label>
                            <input type="text" name="customer_name" value="<?= htmlspecialchars($_SESSION['user_name']) ?>" readonly 
                                   class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-white opacity-60 text-sm outline-none">
                        </div>
                        
                        <div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] text-stone-500 uppercase tracking-widest font-bold">Delivery Spot</label>
                                    <a href="delivery_address.php" class="text-[#CA8A4B] text-[9px] uppercase font-bold hover:underline">Manage Addresses</a>
                                </div>

                                <?php
                                $addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
                                $addr_stmt->execute([$user_id]);
                                $default_addr = $addr_stmt->fetch();
                                ?>

                                <?php if($default_addr): ?>
                                    <div class="p-5 rounded-2xl bg-black/40 border border-white/5">
                                        <div class="flex gap-3 mb-2">
                                            <i data-lucide="map-pin" class="w-4 h-4 text-[#CA8A4B]"></i>
                                            <span class="text-xs text-white font-bold uppercase tracking-wider"><?= $default_addr['label'] ?></span>
                                        </div>
                                        <p class="text-xs text-stone-400">
                                            <?= "{$default_addr['house_no']} {$default_addr['street']}, {$default_addr['barangay']}, {$default_addr['municipality']}" ?>
                                        </p>
                                    </div>
                                    <input type="hidden" name="address_id" value="<?= $default_addr['id'] ?>">
                                <?php else: ?>
                                    <a href="delivery_address.php" class="block p-8 border-2 border-dashed border-white/10 rounded-3xl text-center group hover:border-[#CA8A4B] transition-all">
                                        <i data-lucide="plus" class="w-6 h-6 mx-auto mb-2 text-stone-600 group-hover:text-[#CA8A4B]"></i>
                                        <p class="text-[10px] uppercase font-bold text-stone-500 tracking-widest">Add a Delivery Address</p>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-white/5">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-stone-400 text-xs uppercase tracking-widest font-bold">Total Amount</span>
                                <span class="text-2xl font-serif text-[#CA8A4B] italic">₱<?= number_format($total, 2) ?></span>
                            </div>
                            <input type="hidden" name="total" value="<?= $total ?>">
                            <button type="submit" class="w-full py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold tracking-[0.3em] uppercase text-[10px] hover:bg-[#b07840] transition shadow-lg shadow-[#CA8A4B]/20">
                                Confirm Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="text-center py-20 glass-dark rounded-[3rem] border border-white/5">
                <div class="w-20 h-20 bg-stone-900/50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="shopping-cart" class="w-8 h-8 text-stone-700"></i>
                </div>
                <p class="text-stone-500 italic mb-8">Your basket is currently empty.</p>
                <a href="index.php" class="inline-block bg-[#CA8A4B] text-white px-10 py-4 rounded-full text-[10px] font-bold uppercase tracking-widest hover:bg-[#b07840] transition shadow-lg shadow-[#CA8A4B]/20">
                    Explore Menu
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        function toggleEdit(id) {
            const view = document.getElementById(`view-${id}`);
            const edit = document.getElementById(`edit-${id}`);
            if(view.classList.contains('hidden')) {
                view.classList.remove('hidden');
                edit.classList.add('hidden');
            } else {
                view.classList.add('hidden');
                edit.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>