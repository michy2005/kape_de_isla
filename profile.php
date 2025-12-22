<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";

// 1. Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $mname = $_POST['middle_name'];
    $nname = $_POST['nickname'];
    $address = $_POST['default_address'];
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, middle_name = ?, nickname = ?, default_address = ? WHERE id = ?");
    if ($stmt->execute([$fname, $lname, $mname, $nname, $address, $user_id])) {
        $_SESSION['user_name'] = $fname; 
        $success_msg = "Profile updated successfully!";
    }
}

// 2. Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 3. Stats and Rank logic
$order_stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE user_id = ?");
$order_stmt->execute([$user_id]);
$stats = $order_stmt->fetch();

$total_orders = $stats['total_orders'] ?? 0;
$total_spent = $stats['total_spent'] ?? 0;

// Merged Rank Logic from Old Code
if ($total_orders > 10) $rank = "Coffee Connoisseur";
elseif ($total_orders > 5) $rank = "Caffeine Regular";
elseif ($total_orders > 0) $rank = "First Brewer";
else $rank = "Island Guest";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; color: #d6d3d1; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .input-field { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 10px 14px; width: 100%; color: white; outline: none; font-size: 13px; transition: all 0.3s; }
        .input-field:focus { border-color: #CA8A4B; background: rgba(0,0,0,0.5); }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main class="max-w-5xl mx-auto px-6 pt-32 pb-20">
        
        <?php if($success_msg): ?>
            <div class="max-w-xl mx-auto mb-8 p-4 bg-green-500/10 border border-green-500/30 text-green-400 rounded-2xl text-center text-[10px] uppercase tracking-widest font-bold animate-fade-in">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="glass-dark p-8 rounded-[3rem] text-center border-coffee-accent/10">
                    <div class="w-24 h-24 bg-coffee-accent/20 rounded-full mx-auto mb-4 flex items-center justify-center border-2 border-coffee-accent p-1 overflow-hidden">
                        <span class="text-3xl font-serif italic text-coffee-accent"><?= strtoupper(substr($user['first_name'] ?? 'G', 0, 1)) ?></span>
                    </div>
                    <h2 class="text-white font-serif text-2xl italic"><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></h2>
                    <p class="text-[10px] text-coffee-accent font-bold tracking-[0.3em] uppercase mt-2"><?= $rank ?></p>
                    <p class="text-[9px] text-stone-500 mt-1">@<?= htmlspecialchars($user['nickname']) ?></p>
                </div>

                <div class="glass-dark p-6 rounded-[2.5rem] text-center">
                    <p class="text-stone-500 text-[9px] uppercase tracking-widest mb-4 font-bold">Island Activity</p>
                    <div class="flex justify-around items-center">
                        <div>
                            <p class="text-xl font-serif text-white italic"><?= $total_orders ?></p>
                            <p class="text-[8px] uppercase tracking-tighter text-stone-500 font-bold">Orders</p>
                        </div>
                        <div class="w-px h-8 bg-white/10"></div>
                        <div>
                            <p class="text-xl font-serif text-white italic">₱<?= number_format($total_spent, 0) ?></p>
                            <p class="text-[8px] uppercase tracking-tighter text-stone-500 font-bold">Spent</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['ordered'])): ?>
                <div class="bg-coffee-accent/10 border border-coffee-accent/30 p-6 rounded-3xl">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-400"></i>
                        <p class="text-coffee-accent text-[10px] font-bold uppercase tracking-widest">Order Success!</p>
                    </div>
                    <p class="text-[10px] text-stone-400 leading-relaxed">Check your email <span class="text-white font-bold"><?= $user['email'] ?></span> for the receipt.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-2 glass-dark p-10 rounded-[3rem]">
                <h3 class="font-serif text-3xl text-white mb-8 italic">Account Settings</h3>
                
                <form action="" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">First Name</label>
                            <input type="text" name="first_name" value="<?= $user['first_name'] ?>" class="input-field" required>
                        </div>
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Last Name</label>
                            <input type="text" name="last_name" value="<?= $user['last_name'] ?>" class="input-field" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Middle Name (Optional)</label>
                            <input type="text" name="middle_name" value="<?= $user['middle_name'] ?>" class="input-field">
                        </div>
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Nickname / @Username</label>
                            <input type="text" name="nickname" value="<?= $user['nickname'] ?>" class="input-field" required>
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Email (Fixed)</label>
                        <input type="email" value="<?= $user['email'] ?>" readonly class="input-field opacity-40 cursor-not-allowed">
                    </div>

<div class="glass-dark p-6 rounded-2xl border border-white/5 flex justify-between items-center">
    <div>
        <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-1">Saved Addresses</label>
        <p class="text-xs text-stone-300">Manage your multiple delivery locations</p>
    </div>
    <a href="delivery_address.php" class="bg-white/5 px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-white/10 hover:bg-white/10 transition">
        Manage
    </a>
</div>

                    <button type="submit" name="update_profile" class="w-full bg-coffee-accent py-4 rounded-xl text-[10px] font-bold uppercase tracking-widest text-white hover:bg-[#b07840] transition shadow-xl shadow-coffee-accent/10">
                        Save Profile Changes
                    </button>
                </form>

                <div class="mt-10 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex gap-8">
                        <a href="my_orders.php" class="text-[9px] text-stone-500 hover:text-white uppercase tracking-widest flex items-center gap-2 transition">
                            <i data-lucide="history" class="w-3.5 h-3.5"></i> Order History
                        </a>
                        <a href="auth/change_password.php" class="text-[9px] text-stone-500 hover:text-white uppercase tracking-widest flex items-center gap-2 transition">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Security
                        </a>
                    </div>
                    <a href="logout.php" class="text-[9px] text-red-400 hover:text-red-300 uppercase tracking-widest font-bold flex items-center gap-2">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Log Out
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>