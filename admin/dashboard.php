<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

// --- STATS LOGIC ---
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn() ?: 0;
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pending', 'Brewing')")->fetchColumn();

// Update Status Logic
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: dashboard.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .stat-card { transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.05); }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(202, 138, 75, 0.3); }
        /* Custom scrollbar for a cleaner look */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #CA8A4B; border-radius: 10px; }
    </style>
</head>
<body class="text-stone-200 min-h-screen p-6 md:p-12 font-sans">
    <div class="max-w-7xl mx-auto">
        
        <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
            <div class="flex items-center">
                <div class="p-3 bg-[#CA8A4B]/10 rounded-2xl mr-4 border border-[#CA8A4B]/20">
                    <i data-lucide="layout-dashboard" class="w-8 h-8 text-[#CA8A4B]"></i>
                </div>
                <div>
                    <h1 class="font-serif text-3xl text-white italic">Executive Overview</h1>
                    <p id="liveClock" class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold">Loading Time...</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="products.php" class="glass-dark px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition flex items-center gap-2">
                    <i data-lucide="coffee" class="w-4 h-4"></i> Manage Menu
                </a>
                <a href="logout.php" class="bg-red-900/10 text-red-400 px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest border border-red-900/20 hover:bg-red-900/20 transition">
                    Logout
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                <i data-lucide="banknote" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">Total Revenue</p>
                <h3 class="text-4xl font-serif text-white">₱<?= number_format($total_revenue, 2) ?></h3>
                <p class="text-[10px] text-[#CA8A4B] mt-4 font-bold uppercase tracking-widest">Lifetime Earnings</p>
            </div>

            <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                <i data-lucide="shopping-cart" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">Orders Today</p>
                <h3 class="text-4xl font-serif text-white"><?= $today_orders ?></h3>
                <p class="text-[10px] text-[#CA8A4B] mt-4 font-bold uppercase tracking-widest">New Customer Requests</p>
            </div>

            <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                <i data-lucide="flame" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">In the Kitchen</p>
                <h3 class="text-4xl font-serif text-white"><?= $pending_count ?></h3>
                <p class="text-[10px] text-[#CA8A4B] mt-4 font-bold uppercase tracking-widest">Pending & Brewing</p>
            </div>
        </div>

        <div class="glass-dark rounded-[3rem] overflow-hidden border border-white/5">
            <div class="p-8 border-b border-white/5 flex justify-between items-center">
                <h4 class="font-serif text-xl text-white italic">Recent Orders</h4>
                <span class="text-[10px] text-stone-500 uppercase tracking-widest">Live Updates</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] tracking-[0.2em] text-[#CA8A4B] uppercase bg-black/20">
                            <th class="p-8">Customer</th>
                            <th class="p-8">Delivery Location & Note</th>
                            <th class="p-8">Total Bill</th>
                            <th class="p-8">Live Status</th>
                            <th class="p-8 text-right">Update</th>
                        </tr>
                    </thead>
                    <?php

// UPDATE THE ORDER QUERY TO USE JOIN
$query = "SELECT orders.*, 
          ua.house_no, ua.street, ua.barangay, ua.municipality, ua.notes AS palatandaan, ua.label
          FROM orders 
          LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
          ORDER BY orders.created_at DESC LIMIT 10";

$orders = $pdo->query($query)->fetchAll();
?>

<tbody class="divide-y divide-white/5">
    <?php
    $query = "SELECT orders.*, 
              ua.house_no, ua.street, ua.barangay, ua.municipality, ua.notes AS palatandaan 
              FROM orders 
              LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
              ORDER BY orders.created_at DESC LIMIT 10";
    $orders = $pdo->query($query)->fetchAll();

    foreach ($orders as $o): 
    ?>
    <tr class="hover:bg-white/5 transition duration-300">
        <td class="p-8">
            <p class="text-white font-bold tracking-wide"><?=$o['customer_name']?></p>
            <p class="text-stone-500 text-[9px] mt-1 uppercase"><?=date('h:i A', strtotime($o['created_at']))?></p>
        </td>
        <td class="p-8">
            <?php if ($o['house_no']): ?>
                <p class="text-stone-400 text-xs italic max-w-xs truncate mb-1">
                    <?= "{$o['house_no']} {$o['street']}, {$o['barangay']}, {$o['municipality']}" ?>
                </p>
                <?php if (!empty($o['palatandaan'])): ?>
                <div class="flex items-start gap-2 text-[#CA8A4B]">
                    <i data-lucide="info" class="w-3 h-3 mt-0.5"></i>
                    <p class="text-[10px] font-bold uppercase tracking-widest">Note: <span class="text-stone-300 normal-case italic font-normal"><?= htmlspecialchars($o['palatandaan']) ?></span></p>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-red-400 text-[10px] uppercase font-bold">Address Link Missing</p>
            <?php endif; ?>
        </td>
        <td class="p-8">
            <span class="text-[#CA8A4B] font-serif font-bold">₱<?=number_format($o['total_amount'], 2)?></span>
        </td>
        <td class="p-8">
            <span class="px-4 py-1.5 rounded-full text-[9px] font-bold uppercase tracking-widest 
                <?= ($o['status'] == 'Pending') ? 'bg-stone-800 text-stone-400' : 'bg-[#CA8A4B] text-white shadow-lg shadow-[#CA8A4B]/20' ?>">
                <?=$o['status']?>
            </span>
        </td>
        <td class="p-8 text-right">
            <form method="POST" class="flex justify-end gap-2">
                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                <select name="status" class="bg-black/40 border border-white/10 rounded-xl text-[10px] p-2 text-white outline-none uppercase font-bold tracking-tighter">
                    <option value="Pending" <?= $o['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Brewing" <?= $o['status'] == 'Brewing' ? 'selected' : '' ?>>Brewing</option>
                    <option value="Out for Delivery" <?= $o['status'] == 'Out for Delivery' ? 'selected' : '' ?>>Out</option>
                    <option value="Delivered" <?= $o['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                </select>
                <button name="update_status" class="bg-white/5 p-2.5 rounded-xl text-[#CA8A4B] hover:bg-[#CA8A4B] hover:text-white transition-all"><i data-lucide="check" class="w-4 h-4"></i></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('en-US', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>