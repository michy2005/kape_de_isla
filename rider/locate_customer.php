<?php
session_start();
include '../db.php';

$order_id = $_GET['order_id'];
$stmt = $pdo->prepare("
    SELECT o.*, u.phone, ua.house_no, ua.street, ua.barangay, ua.notes, ua.latitude, ua.longitude
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN user_addresses ua ON o.address_id = ua.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Default coordinates if address doesn't have lat/lng yet
$dest_lat = $order['latitude'] ?? 14.5995; 
$dest_lng = $order['longitude'] ?? 120.9842;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Elite Navigator | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Inter:wght@300;400;600&display=swap');
        
        body { 
            background: radial-gradient(circle at top, #2c1a12 0%, #0f0a08 100%);
            color: #d4d4d8;
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Mobile app feel */
        }

        .font-premium { font-family: 'Cinzel', serif; }

        #map { 
            height: 50vh; 
            width: 100%;
            filter: grayscale(1) invert(1) contrast(1.2) opacity(0.8); /* Unique Dark Gold Theme */
        }

        .premium-glass {
            background: rgba(15, 10, 8, 0.7);
            backdrop-filter: blur(25px);
            border-top: 1px solid rgba(202, 138, 75, 0.3);
            box-shadow: 0 -10px 40px rgba(0,0,0,0.8);
            border-radius: 40px 40px 0 0;
        }

        .btn-call {
            background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%);
            box-shadow: 0 0 20px rgba(202, 138, 75, 0.4);
        }

        /* Custom Marker pulse effect */
        .pulse-marker {
            width: 20px; height: 20px;
            background: #CA8A4B;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(202, 138, 75, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(202, 138, 75, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(202, 138, 75, 0); }
            100% { box-shadow: 0 0 0 0 rgba(202, 138, 75, 0); }
        }
    </style>
</head>
<body class="h-screen flex flex-col">

    <div class="fixed top-6 left-6 right-6 z-50 flex justify-between items-center">
        <a href="dashboard.php" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-[#0f0a08]/80 border border-white/10 backdrop-blur-md">
            <i data-lucide="chevron-left" class="text-[#CA8A4B]"></i>
        </a>
        <div class="px-6 py-2 rounded-full bg-[#0f0a08]/80 border border-[#CA8A4B]/20 backdrop-blur-md">
            <span class="font-premium text-[10px] tracking-[0.3em] text-[#CA8A4B]">Active Track</span>
        </div>
        <div class="w-12"></div>
    </div>

    <div id="map" class="flex-grow"></div>

    <div class="premium-glass p-8 pb-12 transition-all duration-700 transform translate-y-0">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h3 class="font-premium text-[#CA8A4B] text-[10px] tracking-[0.4em] mb-2 uppercase">Destination</h3>
                <h2 class="text-3xl font-premium text-white leading-tight"><?= htmlspecialchars($order['customer_name']) ?></h2>
                <div class="flex items-center gap-2 mt-2 text-stone-500">
                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                    <span class="text-xs italic"><?= $order['street'] ?>, <?= $order['barangay'] ?></span>
                </div>
            </div>
            <a href="tel:<?= $order['phone'] ?>" class="btn-call w-16 h-16 rounded-3xl flex items-center justify-center text-white">
                <i data-lucide="phone" class="w-7 h-7"></i>
            </a>
        </div>

        <div class="grid grid-cols-3 gap-4 border-t border-white/5 pt-8">
            <div class="text-center">
                <p class="text-[9px] text-stone-500 uppercase font-bold mb-1">Distance</p>
                <p class="text-lg font-bold text-white" id="dist-val">--</p>
            </div>
            <div class="text-center border-x border-white/5">
                <p class="text-[9px] text-stone-500 uppercase font-bold mb-1">Time</p>
                <p class="text-lg font-bold text-white">8 Mins</p>
            </div>
            <div class="text-center">
                <p class="text-[9px] text-stone-500 uppercase font-bold mb-1">Order</p>
                <p class="text-lg font-bold text-[#CA8A4B]">#<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        lucide.createIcons();

        // 1. Initialize Map
        const destCoords = [<?= $dest_lat ?>, <?= $dest_lng ?>];
        const map = L.map('map', { zoomControl: false }).setView(destCoords, 15);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);

        // 2. Custom Premium Icons
        const customerIcon = L.divIcon({
            html: '<div class="pulse-marker"></div>',
            className: 'custom-div-icon',
            iconSize: [20, 20]
        });

        const riderIcon = L.divIcon({
            html: '<div style="color: #CA8A4B;"><i data-lucide="navigation" style="transform: rotate(45deg);"></i></div>',
            className: 'rider-icon'
        });

        // Add Customer Marker
        L.marker(destCoords, { icon: customerIcon }).addTo(map);

        // 3. Real-Time Tracking Logic
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(position => {
                const riderCoords = [position.coords.latitude, position.coords.longitude];
                
                // Update/Create Rider Marker
                if (window.riderMarker) {
                    window.riderMarker.setLatLng(riderCoords);
                } else {
                    window.riderMarker = L.marker(riderCoords, { icon: riderIcon }).addTo(map);
                }

                // Update Line (Sleek Gold Curved Look)
                if (window.routeLine) map.removeLayer(window.routeLine);
                window.routeLine = L.polyline([riderCoords, destCoords], {
                    color: '#CA8A4B',
                    weight: 2,
                    dashArray: '5, 10',
                    lineCap: 'round'
                }).addTo(map);

                // Zoom to fit both
                const group = new L.featureGroup([window.riderMarker, L.marker(destCoords)]);
                map.fitBounds(group.getBounds().pad(0.2));

                // Calculate Simple Distance
                const dist = (map.distance(riderCoords, destCoords) / 1000).toFixed(1);
                document.getElementById('dist-val').innerText = dist + " km";
            }, err => console.log(err), { enableHighAccuracy: true });
        }
    </script>
</body>
</html>