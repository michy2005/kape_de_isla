<?php
session_start();
include '../db.php';
if (!isset($_SESSION['rider_id'])) { header("Location: login.php"); exit; }

$rider_id = $_SESSION['rider_id'];

// Get Lifetime Stats
$stats_stmt = $pdo->prepare("SELECT COUNT(*) as total_deliveries, SUM(total_amount) as total_value 
                            FROM orders WHERE rider_id = ? AND status = 'Delivered'");
$stats_stmt->execute([$rider_id]);
$stats = $stats_stmt->fetch();

// Fetch Delivered History
$stmt = $pdo->prepare("SELECT o.*, ua.barangay, ua.street 
                       FROM orders o 
                       LEFT JOIN user_addresses ua ON o.address_id = ua.id 
                       WHERE o.rider_id = ? AND o.status = 'Delivered' 
                       ORDER BY o.created_at DESC");
$stmt->execute([$rider_id]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Log | Kape de Isla</title>
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
            border: 1px solid rgba(202, 138, 75, 0.15); 
        }

        .premium-card { 
            background: linear-gradient(145deg, rgba(44, 26, 18, 0.6), rgba(15, 10, 7, 0.8)); 
            border: 1px solid rgba(202, 138, 75, 0.1); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stats-card {
            border-bottom: 3px solid #CA8A4B;
            background: linear-gradient(to bottom, rgba(202, 138, 75, 0.05), transparent);
        }

        .nav-item { transition: all 0.3s ease; opacity: 0.5; color: #e7e5e4; }
        .nav-active { opacity: 1 !important; color: #CA8A4B !important; }
    </style>
</head>
<body class="p-6">
    <header class="mb-12 text-center max-w-xl mx-auto">
        <p class="text-[9px] tracking-[0.4em] text-[#CA8A4B] uppercase font-bold mb-1 text-center">Performance Archive</p>
        <h1 class="font-serif text-4xl text-white italic text-center">Delivery Log</h1>
    </header>

    <main class="max-w-xl mx-auto">
        <div class="grid grid-cols-2 gap-4 mb-12">
            <div class="premium-card p-7 rounded-[2.5rem] text-center stats-card">
                <div class="w-8 h-8 bg-[#CA8A4B]/10 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="package-check" class="w-4 h-4 text-[#CA8A4B]"></i>
                </div>
                <p class="text-[8px] tracking-[0.3em] text-stone-500 uppercase font-black mb-1">Total Trips</p>
                <h2 class="text-3xl font-serif italic text-white"><?= number_format($stats['total_deliveries']) ?></h2>
            </div>
            <div class="premium-card p-7 rounded-[2.5rem] text-center stats-card">
                <div class="w-8 h-8 bg-[#CA8A4B]/10 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="banknote" class="w-4 h-4 text-[#CA8A4B]"></i>
                </div>
                <p class="text-[8px] tracking-[0.3em] text-stone-500 uppercase font-black mb-1">Total Value</p>
                <h2 class="text-3xl font-serif italic text-white">₱<?= number_format($stats['total_value'] ?: 0, 0) ?></h2>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between px-2 mb-6">
                <h3 class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-black">Past Assignments</h3>
                <span class="text-[8px] px-3 py-1 bg-[#CA8A4B]/10 border border-[#CA8A4B]/20 rounded-full text-[#CA8A4B] font-bold uppercase tracking-widest">Archive</span>
            </div>

            <?php if(empty($history)): ?>
                <div class="premium-card p-20 text-center rounded-[3rem] border-dashed border-white/10">
                    <i data-lucide="history" class="w-10 h-10 text-stone-800 mx-auto mb-4 opacity-50"></i>
                    <p class="text-stone-600 uppercase text-[9px] font-bold tracking-[0.2em]">The log is currently empty.</p>
                </div>
            <?php endif; ?>

            <?php foreach($history as $h): ?>
                <div class="premium-card p-6 rounded-[2.2rem] flex justify-between items-center group active:scale-[0.98]">
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 rounded-2xl bg-black/40 flex items-center justify-center text-[#CA8A4B] border border-white/5 shadow-inner">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white"><?= htmlspecialchars($h['customer_name']) ?></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[9px] text-stone-500 uppercase font-bold tracking-tighter italic"><?= date('M d, Y', strtotime($h['created_at'])) ?></span>
                                <span class="text-stone-800">•</span>
                                <span class="text-[9px] text-[#CA8A4B]/70 uppercase font-bold tracking-tighter italic"><?= htmlspecialchars($h['barangay']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-white tracking-tighter">₱<?= number_format($h['total_amount'], 2) ?></p>
                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span class="w-1 h-1 rounded-full bg-green-500"></span>
                            <p class="text-[8px] text-green-500/60 uppercase font-black tracking-widest">Delivered</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'navbar.php'; ?>
    <script>lucide.createIcons();</script>
</body>
</html>