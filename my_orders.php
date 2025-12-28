<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Querying orders with address details
$query = "SELECT orders.*, 
          ua.house_no, ua.street, ua.barangay, ua.municipality, ua.label
          FROM orders 
          LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
          WHERE orders.user_id = ? 
          ORDER BY orders.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Island Orders | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: #1a0f0a;
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png');
            background-blend-mode: soft-light;
            color: #d6d3d1;
        }

        .glass-dark {
            background: rgba(44, 26, 18, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(202, 138, 75, 0.1);
        }

        /* Premium Status Designs */
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: rgba(202, 138, 75, 0.1);
            color: #CA8A4B;
            border: 1px solid rgba(202, 138, 75, 0.2);
        }

        .status-brewing {
            background: rgba(234, 179, 8, 0.1);
            color: #fbbf24;
            border: 1px solid rgba(234, 179, 8, 0.2);
        }

        .status-outfordelivery {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .status-delivered {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        /* Custom Scrollbar for Item List */
        .item-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .item-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .item-scroll::-webkit-scrollbar-thumb {
            background: rgba(202, 138, 75, 0.3);
            border-radius: 10px;
        }
    </style>
</head>

<body class="min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-6 pt-32 pb-20">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
            <div>
                <h2 class="font-serif text-5xl text-white italic">Island Orders</h2>
                <p class="text-[#CA8A4B] text-[10px] tracking-[0.4em] uppercase font-bold mt-2">Track your caffeine
                    journey</p>
            </div>
            <button onclick="handleGoBack()"
                class="text-stone-500 hover:text-white text-[10px] tracking-widest font-bold uppercase flex items-center gap-2 transition cursor-pointer">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back (<span id="prev-page-name">Menu</span>)
            </button>
        </div>

        <?php if (count($orders) > 0): ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order): ?>
                    <div
                        class="glass-dark rounded-[2.5rem] overflow-hidden border border-white/5 hover:border-[#CA8A4B]/30 transition-all duration-500 group">
                        <div class="p-8">
                            <div class="flex flex-col lg:flex-row gap-8">

                                <div class="lg:w-1/4 space-y-4">
                                    <div class="flex flex-wrap gap-3">
                                        <?php
                                        $stat = strtolower(str_replace(' ', '', $order['status']));
                                        $icon = 'clock';
                                        if ($stat == 'delivered')
                                            $icon = 'check-circle-2';
                                        if ($stat == 'brewing')
                                            $icon = 'flame';
                                        if ($stat == 'outfordelivery')
                                            $icon = 'truck';
                                        ?>
                                        <div class="status-badge status-<?= $stat ?>">
                                            <i data-lucide="<?= $icon ?>" class="w-3 h-3"></i>
                                            <?= $order['status'] ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-white font-serif text-xl italic">
                                            TXN-<?= date('Ymd', strtotime($order['created_at'])) ?>-<?= $order['id'] ?></h3>
                                        <p class="text-stone-500 text-[10px] uppercase tracking-tighter mt-1">
                                            <?= date('F j, Y • g:i A', strtotime($order['created_at'])) ?></p>
                                    </div>
                                    <div class="pt-2">
                                        <p class="text-stone-400 text-[11px] leading-relaxed flex gap-2">
                                            <i data-lucide="map-pin" class="w-3 h-3 text-[#CA8A4B] shrink-0"></i>
                                            <span>
                                                <?= htmlspecialchars("{$order['house_no']} {$order['street']}, {$order['barangay']}") ?><br>
                                                <span
                                                    class="text-[#CA8A4B] uppercase text-[9px] font-bold"><?= $order['label'] ?></span>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="lg:w-2/4 bg-black/20 rounded-3xl p-6 border border-white/5">
                                    <p class="text-[9px] text-stone-500 uppercase tracking-widest mb-4 font-bold">Items in this
                                        Brew</p>
                                    <div class="item-scroll space-y-4 overflow-y-auto max-h-[160px] pr-2">
                                        <?php
                                        $item_query = "SELECT oi.*, p.name, p.image_url, p.image_url_iced, p.image_url_hot 
                                                       FROM order_items oi 
                                                       JOIN products p ON oi.product_id = p.id 
                                                       WHERE oi.order_id = ?";
                                        $item_stmt = $pdo->prepare($item_query);
                                        $item_stmt->execute([$order['id']]);
                                        while ($item = $item_stmt->fetch()):
                                            $display_img = $item['image_url'];
                                            if ($item['mode'] == 'Iced' && !empty($item['image_url_iced']))
                                                $display_img = $item['image_url_iced'];
                                            if ($item['mode'] == 'Hot' && !empty($item['image_url_hot']))
                                                $display_img = $item['image_url_hot'];
                                            ?>
                                            <div class="flex items-center gap-4 group/item">
                                                <div
                                                    class="w-12 h-12 rounded-xl overflow-hidden bg-stone-900 border border-white/10 shrink-0">
                                                    <img src="<?= $display_img ?>"
                                                        class="w-full h-full object-cover opacity-80 group-hover/item:opacity-100 transition">
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm text-white font-medium">
                                                        <?= $item['name'] ?>
                                                        <span class="text-stone-500 text-xs ml-2">x<?= $item['quantity'] ?></span>
                                                    </p>
                                                    <span
                                                        class="text-[9px] text-[#CA8A4B] uppercase tracking-widest"><?= $item['mode'] ?>
                                                        Mode</span>
                                                </div>
                                                <p class="text-sm font-serif text-stone-400">
                                                    ₱<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></p>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>

                                <div class="lg:w-1/4 text-right flex flex-col justify-between border-l border-white/5 pl-8">
                                    <div>
                                        <p class="text-[9px] text-stone-500 uppercase tracking-widest font-bold">Total Bill</p>
                                        <p class="text-3xl font-serif text-white italic">
                                            ₱<?= number_format($order['total_amount'], 2) ?></p>
                                    </div>

                                    <?php if ($order['status'] == 'Delivered'): ?>
                                        <a href="receipt.php?id=<?= $order['id'] ?>"
                                            class="w-full py-3 bg-[#4ade80]/10 hover:bg-[#4ade80] text-[#4ade80] hover:text-[#1a0f0a] border border-[#4ade80]/20 rounded-xl text-[9px] uppercase tracking-widest font-bold transition flex items-center justify-center gap-2">
                                            View Receipt <i data-lucide="receipt" class="w-3 h-3"></i>
                                        </a>
                                    <?php else: ?>
<a href="track_order.php?id=<?= $order['id'] ?>"
    class="w-full py-3 bg-white/5 hover:bg-[#CA8A4B] hover:text-white border border-white/10 rounded-xl text-[9px] uppercase tracking-widest font-bold transition flex items-center justify-center gap-2">
    Track Order <i data-lucide="map" class="w-3 h-3"></i>
</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-dark p-20 rounded-[4rem] text-center border border-white/5">
                <i data-lucide="coffee" class="w-12 h-12 text-stone-700 mx-auto mb-6"></i>
                <h3 class="font-serif text-2xl text-white italic mb-2">No orders yet</h3>
                <p class="text-stone-500 text-sm mb-8">Start your first island tradition today.</p>
                <a href="index.php"
                    class="inline-block bg-[#CA8A4B] text-white px-10 py-4 rounded-full text-[10px] font-bold uppercase tracking-widest hover:bg-[#b07840] transition">
                    Explore Menu
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();

        // 1. Dynamic Back Button Logic
        const prevUrl = document.referrer;
        const btnText = document.getElementById('prev-page-name');

        if (prevUrl.includes('cart.php')) {
            btnText.innerText = "Cart";
        } else if (prevUrl.includes('process_order.php')) {
            btnText.innerText = "Cart"; // Overriding process_order to show Cart
        } else if (prevUrl.includes('index.php')) {
            btnText.innerText = "Menu";
        }

        function handleGoBack() {
            // If coming from process_order, skip it and go to cart
            if (prevUrl.includes('process_order.php')) {
                window.location.href = 'cart.php';
            } else if (prevUrl === "") {
                window.location.href = 'index.php';
            } else {
                history.back();
            }
        }
    </script>
</body>

</html>