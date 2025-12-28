<?php
session_start();
include '../db.php';
if (!isset($_SESSION['rider_id'])) { header("Location: login.php"); exit; }

$rider_id = $_SESSION['rider_id'];

// Handle Marking Order as Delivered
if (isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ? AND rider_id = ?")->execute([$order_id, $rider_id]);
    $pdo->prepare("UPDATE riders SET status = 'Available' WHERE id = ?")->execute([$rider_id]);
    $pdo->commit();
    header("Location: dashboard.php?delivered=1"); exit;
}

// Handle Status Toggle (Online/Offline)
if (isset($_POST['toggle_status'])) {
    $new_status = $_POST['current_status'] == 'Available' ? 'Offline' : 'Available';
    $pdo->prepare("UPDATE riders SET status = ? WHERE id = ?")->execute([$new_status, $rider_id]);
    header("Location: dashboard.php"); exit;
}

// Fetch Rider Info
$rider = $pdo->prepare("SELECT * FROM riders WHERE id = ?");
$rider->execute([$rider_id]);
$rider_data = $rider->fetch();

// Fetch Orders with Items
$stmt = $pdo->prepare("SELECT o.*, ua.house_no, ua.street, ua.barangay, ua.notes 
                       FROM orders o 
                       LEFT JOIN user_addresses ua ON o.address_id = ua.id 
                       WHERE o.rider_id = ? AND o.status = 'Out for Delivery'
                       ORDER BY o.created_at DESC");
$stmt->execute([$rider_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Hub | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;600;700&display=swap');
        
        body {
            background-color: #1a0f0a;
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png');
            background-blend-mode: soft-light;
            color: #e7e5e4;
            font-family: 'Inter', sans-serif;
            padding-bottom: 180px !important; 
            min-height: 100vh;
        }

        .font-serif { font-family: 'Playfair Display', serif; }
        
        .glass-dark { 
            background: rgba(28, 18, 13, 0.85); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(202, 138, 75, 0.2); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        }

        .premium-card { 
            background: linear-gradient(145deg, rgba(44, 26, 18, 0.6), rgba(15, 10, 7, 0.8)); 
            border: 1px solid rgba(202, 138, 75, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .order-expanded { border-color: #CA8A4B; transform: scale(1.02); }

        .btn-gold { 
            background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%); 
            box-shadow: 0 4px 15px rgba(202, 138, 75, 0.3); 
        }

        .status-dot { box-shadow: 0 0 10px currentColor; }

        /* Hidden detail section */
        .details-content { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-out; }
        .is-open .details-content { max-height: 500px; margin-top: 1.5rem; }
    </style>
</head>
<body class="p-6">
    <header class="flex justify-between items-start mb-10 max-w-xl mx-auto">
        <div>
            <p class="text-[9px] tracking-[0.4em] text-[#CA8A4B] uppercase font-bold mb-1">Fleet Personnel</p>
            <h1 class="font-serif text-3xl text-white italic leading-none"><?= htmlspecialchars($rider_data['first_name']) ?></h1>
        </div>
        <form method="POST">
            <input type="hidden" name="current_status" value="<?= $rider_data['status'] ?>">
            <button name="toggle_status" class="flex items-center gap-3 px-5 py-2.5 rounded-2xl glass-dark hover:border-[#CA8A4B]/40 transition-all">
                <span class="w-2 h-2 rounded-full status-dot <?= $rider_data['status'] == 'Available' ? 'bg-green-500 text-green-500 animate-pulse' : 'bg-stone-600 text-stone-600' ?>"></span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-white"><?= $rider_data['status'] ?></span>
            </button>
        </form>
    </header>

    <main class="max-w-xl mx-auto">
        <div class="flex items-center justify-between mb-8 px-2">
            <h3 class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-black">Active Assignments</h3>
            <span class="text-[10px] text-[#CA8A4B] font-bold"><?= count($orders) ?> Orders</span>
        </div>

        <div class="space-y-6">
            <?php if(empty($orders)): ?>
                <div class="premium-card p-20 text-center rounded-[3rem] border-dashed border-white/10">
                    <i data-lucide="coffee" class="w-12 h-12 text-stone-700 mx-auto mb-4"></i>
                    <p class="text-stone-500 uppercase text-[10px] font-bold tracking-[0.2em]">All caught up.</p>
                </div>
            <?php endif; ?>

            <?php foreach($orders as $o): 
                // Fetch Items for this order
                $item_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $item_stmt->execute([$o['id']]);
                $items = $item_stmt->fetchAll();
            ?>
                <div onclick="this.classList.toggle('is-open'); this.classList.toggle('order-expanded')" 
                     class="premium-card p-7 rounded-[2.5rem] relative overflow-hidden cursor-pointer group">
                    
                    <div class="flex justify-between items-start">
                        <div class="bg-white/5 border border-white/10 px-4 py-1.5 rounded-full">
                             <span class="text-[#CA8A4B] text-[9px] font-black uppercase">Order #<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-stone-600 group-[.is-open]:rotate-180 transition-transform"></i>
                    </div>

                    <div class="mt-6">
                        <h2 class="text-2xl font-serif italic text-white"><?= htmlspecialchars($o['customer_name']) ?></h2>
                        <div class="flex items-center gap-2 mt-2">
                            <i data-lucide="map-pin" class="w-3 h-3 text-[#CA8A4B]"></i>
                            <p class="text-[11px] text-stone-400 font-medium"><?= $o['street'] ?>, <?= $o['barangay'] ?></p>
                        </div>
                    </div>

                    <div class="details-content border-t border-white/5 pt-4">
                        <p class="text-[9px] font-black text-[#CA8A4B] uppercase tracking-widest mb-3">Order Manifest</p>
                        <div class="space-y-3 mb-6">
                            <?php foreach($items as $item): ?>
                            <div class="flex justify-between text-xs">
                                <span class="text-stone-300"><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?> <span class="text-[9px] text-stone-500 ml-1">(<?= $item['mode'] ?>)</span></span>
                                <span class="text-stone-400">₱<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="flex justify-between border-t border-white/5 pt-3 mt-2">
                                <span class="text-white font-bold uppercase text-[10px]">Total Amount</span>
                                <span class="text-[#CA8A4B] font-bold">₱<?= number_format($o['total_amount'], 2) ?></span>
                            </div>
                        </div>

                        <?php if($o['notes']): ?>
                            <div class="mb-6 p-4 rounded-2xl bg-black/30 border border-white/5">
                                <p class="text-[8px] text-[#CA8A4B] uppercase font-black mb-1">Rider Note</p>
                                <p class="text-[11px] italic text-stone-400">"<?= htmlspecialchars($o['notes']) ?>"</p>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-3" onclick="event.stopPropagation();">
<a href="locate_customer.php?order_id=<?= $o['id'] ?>" class="flex-1 py-4 glass-dark rounded-2xl flex items-center justify-center gap-2 text-stone-400 hover:text-white transition-all border border-white/5">
    <i data-lucide="navigation-2" class="w-4 h-4"></i>
    <span class="text-[10px] font-black uppercase tracking-widest">Locate</span>
</a>
                            <form method="POST" class="flex-[2]">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button name="complete_order" class="btn-gold w-full py-4 rounded-2xl text-white font-black uppercase text-[10px] tracking-widest">
                                    Complete Delivery
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'navbar.php'; ?>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>