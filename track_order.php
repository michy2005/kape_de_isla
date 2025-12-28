<?php
session_start();
include 'db.php';
$order_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

$status = $order['status'];
$progress = 20;
if($status == 'Brewing') $progress = 50;
if($status == 'Out for Delivery') $progress = 80;
if($status == 'Delivered') $progress = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .progress-glow { box-shadow: 0 0 15px #CA8A4B; }
        #track-map { height: 300px; border-radius: 2rem; margin-top: 2rem; }
    </style>
</head>
<body class="bg-[#1a0f0a] text-white p-6">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-serif italic mb-8 text-center">Tracking Your Brew</h2>

        <div class="relative pt-1 mb-10">
            <div class="flex mb-2 items-center justify-between text-[10px] uppercase font-bold tracking-tighter">
                <div class="text-[#CA8A4B]">Pending</div>
                <div class="<?= $progress >= 50 ? 'text-[#CA8A4B]' : 'text-stone-600' ?>">Brewing</div>
                <div class="<?= $progress >= 80 ? 'text-[#CA8A4B]' : 'text-stone-600' ?>">Out for Delivery</div>
                <div class="<?= $progress == 100 ? 'text-[#CA8A4B]' : 'text-stone-600' ?>">Arrived</div>
            </div>
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-stone-800">
                <div style="width:<?= $progress ?>%" class="progress-glow shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-[#CA8A4B] transition-all duration-1000"></div>
            </div>
        </div>

        <?php if($status == 'Out for Delivery'): ?>
            <div id="track-map" class="border border-white/10"></div>
            <p class="text-center mt-4 text-stone-500 text-xs animate-pulse">Rider is approaching your island...</p>
        <?php else: ?>
            <div class="bg-black/40 p-12 rounded-[3rem] text-center border border-dashed border-white/10">
                <i data-lucide="coffee" class="w-12 h-12 mx-auto mb-4 text-stone-700"></i>
                <p class="text-stone-500 uppercase text-xs tracking-widest">Our baristas are crafting your coffee.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        lucide.createIcons();
        <?php if($status == 'Out for Delivery'): ?>
        var map = L.map('track-map', {zoomControl: false}).setView([14.5995, 120.9842], 14);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);
        
        // Custom Coffee Icon
        var riderIcon = L.divIcon({
            html: '<div style="background:#CA8A4B; padding:5px; border-radius:50%; border:2px solid white;"><img src="https://cdn-icons-png.flaticon.com/512/2853/2853820.png" width="20"></div>',
            className: 'custom-div-icon'
        });

        L.marker([14.5995, 120.9842], {icon: riderIcon}).addTo(map);
        L.circle([14.6050, 120.9900], {color: '#CA8A4B', radius: 200, fillOpacity: 0.1}).addTo(map);
        <?php endif; ?>
    </script>
</body>
</html>