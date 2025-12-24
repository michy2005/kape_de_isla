<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// --- 1. CSV EXPORT LOGIC ---
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Kape_de_Isla_Sales_'.$start_date.'_to_'.$end_date.'.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Date', 'Customer Name', 'Total Amount', 'Status']);

    $export_query = "SELECT id, created_at, customer_name, total_amount, status 
                     FROM orders WHERE status IN ('Delivered', 'Archived') 
                     AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute([$start_date, $end_date]);
    
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// --- 2. REGULAR PAGE DATA ---
$query = "SELECT orders.*, ua.barangay FROM orders 
          LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
          WHERE orders.status IN ('Delivered', 'Archived') 
          AND DATE(orders.created_at) BETWEEN ? AND ?
          ORDER BY orders.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$start_date, $end_date]);
$archives = $stmt->fetchAll();

$chart_query = "SELECT DATE(created_at) as date, SUM(total_amount) as daily_total 
                FROM orders WHERE status IN ('Delivered', 'Archived') 
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";
$chart_stmt = $pdo->prepare($chart_query);
$chart_stmt->execute([$start_date, $end_date]);
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = json_encode(array_column($chart_data, 'date'));
$totals = json_encode(array_column($chart_data, 'daily_total'));

$bs_query = "SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * p.price) as revenue
             FROM order_items oi JOIN products p ON oi.product_id = p.id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.status IN ('Delivered', 'Archived') AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY p.id ORDER BY total_qty DESC LIMIT 5";
$bs_stmt = $pdo->prepare($bs_query);
$bs_stmt->execute([$start_date, $end_date]);
$best_sellers = $bs_stmt->fetchAll();

$filtered_total = array_sum(array_column($chart_data, 'daily_total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Archives | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .date-input { background: rgba(0,0,0,0.3); border: 1px solid rgba(202, 138, 75, 0.2); transition: all 0.3s ease; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.8) sepia(100%) saturate(500%) hue-rotate(10deg); cursor: pointer; }
        @media print { .no-print { display: none; } body { background: white; color: black; } }
    </style>
</head>
<body class="text-stone-200 min-h-screen p-6 md:p-12 font-sans">

    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-8 no-print">
            <div class="flex items-center gap-5">
                <a href="dashboard.php" class="p-4 bg-white/5 rounded-2xl border border-white/10 hover:bg-[#CA8A4B] transition-all">
                    <i data-lucide="chevron-left" class="w-6 h-6 text-[#CA8A4B] hover:text-white"></i>
                </a>
                <div>
                    <h1 class="font-serif text-4xl text-white italic">Business Intelligence</h1>
                    <p class="text-[10px] tracking-[0.4em] text-[#CA8A4B] uppercase font-black">Sales Performance & Archives</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <form class="flex items-center gap-4 bg-white/5 p-3 rounded-[2rem] border border-white/10">
                    <div class="flex items-center gap-3 px-4 py-2 rounded-xl date-input">
                        <label class="text-[9px] uppercase font-black text-stone-500">Start</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2 rounded-xl date-input">
                        <label class="text-[9px] uppercase font-black text-stone-500">End</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="bg-transparent text-sm font-bold text-white outline-none">
                    </div>
                    <button type="submit" class="bg-[#CA8A4B] text-white px-5 py-3 rounded-xl hover:bg-[#b07840] transition-all">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </form>

                <div class="flex gap-2">
                    <a href="?export=csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="bg-emerald-600/20 text-emerald-400 border border-emerald-600/30 px-5 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 hover:bg-emerald-600 hover:text-white transition-all">
                        <i data-lucide="download" class="w-4 h-4"></i> CSV
                    </a>
                    <button onclick="window.print()" class="bg-white/5 border border-white/10 px-5 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 hover:bg-white/10 transition-all">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print
                    </button>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <div class="lg:col-span-1 glass-dark p-8 rounded-[2.5rem] border-l-4 border-[#CA8A4B]">
                <p class="text-[10px] text-stone-500 uppercase font-black mb-1">Total Revenue</p>
                <h2 class="text-4xl font-serif text-white">₱<?= number_format($filtered_total, 2) ?></h2>
                <p class="mt-4 text-[10px] text-stone-400 font-bold"><?= count($archives) ?> Orders in this period</p>
            </div>

            <div class="lg:col-span-3 glass-dark p-6 rounded-[2.5rem] min-h-[200px]">
                <canvas id="salesChart" style="max-height: 180px;"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="glass-dark p-8 rounded-[2.5rem] h-fit">
                <h3 class="text-xs uppercase font-black tracking-widest text-white mb-6">Top Sellers</h3>
                <div class="space-y-6">
                    <?php foreach($best_sellers as $bs): 
                        $percentage = ($bs['revenue'] / ($filtered_total ?: 1)) * 100;
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-[11px] font-bold text-stone-300 uppercase"><?= $bs['name'] ?></span>
                            <span class="text-[11px] font-black text-[#CA8A4B]"><?= $bs['total_qty'] ?></span>
                        </div>
                        <div class="w-full bg-black/40 h-1 rounded-full overflow-hidden">
                            <div class="bg-[#CA8A4B] h-full" style="width: <?= $percentage ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lg:col-span-2 glass-dark rounded-[2.5rem] overflow-hidden border border-white/5">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] tracking-widest text-stone-500 uppercase bg-black/20">
                            <th class="p-6">Date</th>
                            <th class="p-6">Client</th>
                            <th class="p-6 text-right">Settlement</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($archives as $row): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="p-6 text-xs text-stone-400"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="p-6">
                                <p class="text-white font-bold text-xs uppercase"><?= $row['customer_name'] ?></p>
                                <p class="text-[9px] font-mono text-[#CA8A4B]">#<?= $row['id'] ?></p>
                            </td>
                            <td class="p-6 text-right">
                                <p class="text-white font-serif italic text-lg">₱<?= number_format($row['total_amount'], 2) ?></p>
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
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $labels ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?= $totals ?>,
                    borderColor: '#CA8A4B',
                    backgroundColor: 'rgba(202, 138, 75, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#CA8A4B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#78716c', font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { color: '#78716c', font: { size: 10 } } }
                }
            }
        });
    </script>
</body>
</html>