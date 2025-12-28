<?php
session_start();
include '../db.php';

if (!isset($_GET['order_id'])) { header("Location: dashboard.php"); exit; }

$order_id = $_GET['order_id'];

$stmt = $pdo->prepare("
    SELECT o.*, 
           u.first_name, u.last_name, u.phone AS user_phone, 
           ua.house_no, ua.street, ua.barangay, ua.municipality, ua.additional_info, ua.latitude, ua.longitude,
           GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as item_summary
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN user_addresses ua ON o.address_id = ua.id 
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) { die("Order not found."); }

$dest_lat = $order['latitude'] ?? 11.1541; 
$dest_lng = $order['longitude'] ?? 123.8055;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Elite Navigator | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=Inter:wght@300;400;600;700&display=swap');
        
        body { 
            background: #1a0f0a; 
            color: white; 
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: fixed; width: 100%; height: 100%;
        }

        #map { height: 100vh; width: 100%; z-index: 1; }

        #drawer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: rgba(28, 18, 13, 0.98);
            backdrop-filter: blur(25px);
            border-top: 2px solid #CA8A4B;
            border-radius: 40px 40px 0 0;
            z-index: 2000;
            height: 85vh;
            will-change: transform;
            touch-action: none;
            padding: 0 2rem;
            transform: translateY(50vh);
        }

        .map-btn {
            background: rgba(28, 18, 13, 0.9);
            border: 1px solid rgba(202, 138, 75, 0.4);
            color: #CA8A4B;
            width: 50px; height: 50px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .map-btn svg { margin: auto; display: block; }

        .coffee-marker-bg {
            background: #CA8A4B; width: 44px; height: 44px;
            border-radius: 50%; border: 3px solid #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 15px rgba(202, 138, 75, 0.6);
        }

        .glow-line { stroke-dasharray: 10, 10; animation: flow 30s linear infinite; filter: drop-shadow(0 0 5px #CA8A4B); }
        @keyframes flow { to { stroke-dashoffset: -1000; } }
    </style>
</head>
<body class="flex flex-col">

    <div class="fixed top-6 left-6 right-6 z-[1000] flex justify-between items-center">
        <a href="dashboard.php" class="map-btn"><i data-lucide="arrow-left"></i></a>
        <div class="px-6 py-2 rounded-full bg-stone-900/90 border border-[#CA8A4B]/30 flex items-center gap-3">
            <span class="text-[9px] tracking-[0.3em] text-[#CA8A4B] font-black uppercase">Elite Track</span>
            <div class="w-[1px] h-3 bg-white/20"></div>
            <span class="text-[10px] font-bold text-white" id="speed-val">0 km/h</span>
        </div>
        <button onclick="toggleMapType()" class="map-btn"><i data-lucide="map"></i></button>
    </div>

    <div class="fixed right-6 top-1/4 z-[1000] flex flex-col gap-3">
        <button onclick="map.zoomIn()" class="map-btn"><i data-lucide="plus"></i></button>
        <button onclick="map.zoomOut()" class="map-btn"><i data-lucide="minus"></i></button>
        <button onclick="recenterToCustomer()" class="map-btn"><i data-lucide="locate-fixed"></i></button>
        <button onclick="recenterToRider()" class="map-btn"><i data-lucide="navigation"></i></button>
    </div>

    <div id="map"></div>

    <div id="drawer">
        <div id="drag-handle" class="w-full pt-4 pb-6 flex flex-col items-center cursor-pointer">
            <div class="w-16 h-1.5 bg-[#CA8A4B]/40 rounded-full mb-2"></div>
            <span class="text-[8px] uppercase tracking-[0.3em] font-black text-[#CA8A4B]/80">Pull for Details</span>
        </div>

        <div class="overflow-y-auto h-full pb-32">
            <div class="flex justify-between items-start mb-8">
                <div class="flex-1">
                    <h3 class="text-[10px] text-[#CA8A4B] tracking-[0.4em] font-black uppercase mb-2">Recipient</h3>
                    <h2 class="text-3xl font-serif italic text-white"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></h2>
                    <div class="flex items-center gap-2 mt-2 text-stone-500">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#CA8A4B]"></i>
                        <span class="text-xs italic font-medium"><?= $order['house_no'] ?> <?= $order['street'] ?>, Brgy. <?= $order['barangay'] ?></span>
                    </div>
                </div>
                <a href="tel:<?= $order['user_phone'] ?>" class="w-16 h-16 bg-[#CA8A4B] rounded-3xl flex items-center justify-center text-white shrink-0">
                    <i data-lucide="phone" class="w-7 h-7"></i>
                </a>
            </div>

            <div class="grid grid-cols-3 gap-4 py-6 border-y border-white/5 mb-8">
                <div class="text-center">
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">Distance</p>
                    <p class="text-xl font-bold text-white" id="dist-val">--</p>
                </div>
                <div class="text-center border-x border-white/5">
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">ETA</p>
                    <p class="text-xl font-bold text-white" id="time-val">--</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">Total</p>
                    <p class="text-xl font-bold text-[#CA8A4B]">₱<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>

            <div>
                <h4 class="text-[10px] text-stone-500 font-black uppercase mb-3 tracking-[0.2em]">Package Details</h4>
                <div class="bg-black/40 border border-white/5 rounded-2xl p-5 mb-8">
                    <p class="text-sm text-stone-300 font-medium italic"><?= htmlspecialchars($order['item_summary']) ?></p>
                </div>
            </div>

            <button onclick="confirmDelivery()" class="w-full bg-[#CA8A4B] py-5 rounded-2xl text-[11px] font-black uppercase tracking-[0.3em] text-white">
                Complete Delivery
            </button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const destCoords = [<?= $dest_lat ?>, <?= $dest_lng ?>];
        const satelliteTheme = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}');
        const streetTheme = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

        const map = L.map('map', { zoomControl: false, layers: [satelliteTheme] }).setView(destCoords, 18);

        function toggleMapType() {
            if (map.hasLayer(satelliteTheme)) {
                map.removeLayer(satelliteTheme); streetTheme.addTo(map);
            } else {
                map.removeLayer(streetTheme); satelliteTheme.addTo(map);
            }
        }

        function recenterToCustomer() { map.flyTo(destCoords, 18); }
        function recenterToRider() { if(window.lastRiderPos) map.flyTo(window.lastRiderPos, 18); }

        lucide.createIcons();

        // Specific Rider Icon requested
        const riderIcon = L.divIcon({
            html: `
                <div style="
                    background: #1a0f0a; 
                    width: 48px; 
                    height: 48px; 
                    border-radius: 50%; 
                    border: 3px solid #CA8A4B; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.6);
                    color: #CA8A4B;">
                    <i data-lucide="bike" style="width:28px; height:28px;"></i>
                </div>`,
            className: 'rider-marker-container',
            iconSize: [48, 48], 
            iconAnchor: [24, 24]
        });

        const coffeeIcon = L.divIcon({
            html: `<div class="coffee-marker-bg"><svg width="22" height="22" viewBox="0 0 24 24" fill="white" stroke="currentColor" stroke-width="2"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg></div>`,
            className: '', iconSize: [44, 44], iconAnchor: [22, 22]
        });

        L.marker(destCoords, { icon: coffeeIcon }).addTo(map);

        // Road-based Routing Function
        async function updateRoadRoute(start, end) {
            try {
                const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.routes && data.routes.length > 0) {
                    const coordinates = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                    
                    if (window.routeLine) map.removeLayer(window.routeLine);
                    window.routeLine = L.polyline(coordinates, {
                        color: '#CA8A4B', weight: 5, className: 'glow-line'
                    }).addTo(map);

                    // Update precise road distance
                    const distanceKm = (data.routes[0].distance / 1000).toFixed(2);
                    document.getElementById('dist-val').innerText = distanceKm + " km";
                    document.getElementById('time-val').innerText = Math.ceil(data.routes[0].duration / 60) + " mins";
                }
            } catch (e) { console.log("Routing error:", e); }
        }

        // Tracking Logic
        let hasVibrated = false;
        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(position => {
                const riderCoords = [position.coords.latitude, position.coords.longitude];
            // ADD THIS PART TO SAVE TO DATABASE:
fetch(`update_location.php?order_id=<?= $order_id ?>&lat=${riderCoords[0]}&lng=${riderCoords[1]}`)
    .then(response => console.log("Location synced"));
            
                window.lastRiderPos = riderCoords;
                
                const speed = position.coords.speed ? Math.round(position.coords.speed * 3.6) : 0;
                document.getElementById('speed-val').innerText = speed + " km/h";

                if (window.riderMarker) {
                    window.riderMarker.setLatLng(riderCoords);
                } else {
                    window.riderMarker = L.marker(riderCoords, { icon: riderIcon }).addTo(map);
                }

                // Call road-based routing instead of direct line
                updateRoadRoute(riderCoords, destCoords);

                // Proximity Vibration
                const directDist = map.distance(riderCoords, destCoords);
                if (directDist < 50 && !hasVibrated) {
                    if ("vibrate" in navigator) navigator.vibrate([300, 100, 300]);
                    hasVibrated = true;
                }

                lucide.createIcons();
            }, null, { enableHighAccuracy: true });
        }

        // Drawer Animation
        const drawer = document.getElementById('drawer');
        const h = window.innerHeight;
        const snapPoints = [h * 0.05, h * 0.45, h * 0.78];
        let currentY = snapPoints[1];

        interact('#drawer').draggable({
            allowFrom: '#drag-handle',
            listeners: {
                move(event) {
                    currentY += event.dy;
                    if(currentY < snapPoints[0]) currentY = snapPoints[0];
                    if(currentY > snapPoints[2]) currentY = snapPoints[2];
                    drawer.style.transition = 'none';
                    drawer.style.transform = `translateY(${currentY}px)`;
                },
                end() {
                    const closest = snapPoints.reduce((prev, curr) => 
                        Math.abs(curr - currentY) < Math.abs(prev - currentY) ? curr : prev
                    );
                    currentY = closest;
                    drawer.style.transition = 'transform 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.1)';
                    drawer.style.transform = `translateY(${currentY}px)`;
                }
            }
        });

        function confirmDelivery() {
            if(confirm("Confirm Delivery?")) { window.location.href = "complete_delivery.php?order_id=<?= $order_id ?>"; }
        }
    </script>
</body>
</html>