<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$order_id = $_GET['id'];

// Fetch order, address, rider details, and product images
$stmt = $pdo->prepare("
    SELECT o.*, 
           ua.latitude AS dest_lat, ua.longitude AS dest_lng, ua.house_no, ua.street, ua.barangay, ua.municipality,
           r.first_name, r.last_name, r.phone AS rider_phone,
           GROUP_CONCAT(p.image_url SEPARATOR '|') as product_images,
           GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items
    FROM orders o 
    JOIN user_addresses ua ON o.address_id = ua.id
    LEFT JOIN riders r ON o.rider_id = r.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) { die("Order not found."); }

$status = $order['status'];
$progress = 20;
if($status == 'Brewing') $progress = 50;
if($status == 'Out for Delivery') $progress = 80;
if($status == 'Delivered') $progress = 100;

$dest_lat = $order['dest_lat'] ?? 11.1541; 
$dest_lng = $order['dest_lng'] ?? 123.8055;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Elite Tracking | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=Inter:wght@300;400;600;700&display=swap');
        
        body { background: #1a0f0a; color: white; font-family: 'Inter', sans-serif; overflow: hidden; position: fixed; width: 100%; height: 100%; }
        #map { height: 100vh; width: 100%; z-index: 1; background: #1a0f0a; }

        /* Map Buttons - Dark Glass Style */
        .map-btn { 
            background: rgba(28, 18, 13, 0.9); 
            border: 1px solid rgba(202, 138, 75, 0.4); 
            color: #CA8A4B; 
            width: 50px; height: 50px; 
            border-radius: 15px; 
            display: flex; align-items: center; justify-content: center; 
            backdrop-filter: blur(10px); 
            box-shadow: 0 8px 32px rgba(0,0,0,0.5); 
        }
        .map-btn.active { background: #CA8A4B; color: white; }

        /* Icon Fixes - REMOVE WHITE SQUARES */
        .leaflet-marker-icon { background: transparent !important; border: none !important; }
        .custom-div-icon { background: transparent !important; border: none !important; }

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

        .coffee-marker-bg {
            background: #CA8A4B; width: 44px; height: 44px;
            border-radius: 50%; border: 3px solid #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 15px rgba(202, 138, 75, 0.6);
        }

        .glow-line { stroke-dasharray: 10, 10; animation: flow 30s linear infinite; filter: drop-shadow(0 0 5px #CA8A4B); }
        @keyframes flow { to { stroke-dashoffset: -1000; } }

        .progress-bar-bg { background: rgba(255,255,255,0.05); border-radius: 10px; height: 6px; overflow: hidden; }
        .progress-fill { background: #CA8A4B; height: 100%; transition: width 1s ease-in-out; box-shadow: 0 0 10px #CA8A4B; }
        .product-img { width: 70px; height: 70px; object-fit: cover; border-radius: 20px; border: 2px solid rgba(202, 138, 75, 0.2); flex-shrink: 0; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="flex flex-col">

    <div class="fixed top-6 left-6 right-6 z-[1000] flex justify-between items-center">
        <a href="index.php" class="map-btn"><i data-lucide="chevron-left"></i></a>
        <div class="px-6 py-2 rounded-full bg-stone-900/90 border border-[#CA8A4B]/30 flex items-center gap-3">
            <span class="text-[9px] tracking-[0.3em] text-[#CA8A4B] font-black uppercase">Elite Track</span>
            <div class="w-[1px] h-3 bg-white/20"></div>
            <span class="text-[10px] font-bold text-white uppercase" id="status-label"><?= $status ?></span>
        </div>
        <button id="layerToggle" class="map-btn"><i data-lucide="layers"></i></button>
    </div>

    <div class="fixed right-6 top-1/4 z-[1000] flex flex-col gap-3">
        <button onclick="map.zoomIn()" class="map-btn"><i data-lucide="plus"></i></button>
        <button onclick="map.zoomOut()" class="map-btn"><i data-lucide="minus"></i></button>
        <button onclick="recenterToCustomer()" class="map-btn"><i data-lucide="home"></i></button>
        <button onclick="recenterToRider()" class="map-btn"><i data-lucide="bike"></i></button>
    </div>

    <div id="map"></div>

    <div id="drawer">
        <div id="drag-handle" class="w-full pt-4 pb-6 flex flex-col items-center cursor-pointer">
            <div class="w-16 h-1.5 bg-[#CA8A4B]/40 rounded-full mb-2"></div>
            <span class="text-[8px] uppercase tracking-[0.3em] font-black text-[#CA8A4B]/80" id="eta-label">Order Details</span>
        </div>

        <div class="overflow-y-auto h-full pb-32">
            <div class="mb-10 px-2">
                <div class="flex justify-between text-[8px] font-black uppercase tracking-widest text-stone-500 mb-3">
                    <span class="<?= $progress >= 20 ? 'text-[#CA8A4B]' : '' ?>">Placed</span>
                    <span class="<?= $progress >= 50 ? 'text-[#CA8A4B]' : '' ?>">Brewing</span>
                    <span class="<?= $progress >= 80 ? 'text-[#CA8A4B]' : '' ?>">On Way</span>
                    <span class="<?= $progress == 100 ? 'text-[#CA8A4B]' : '' ?>">Arrived</span>
                </div>
                <div class="progress-bar-bg"><div class="progress-fill" style="width: <?= $progress ?>%"></div></div>
            </div>

            <div class="flex justify-between items-start mb-8">
                <div class="flex-1">
                    <h3 class="text-[10px] text-[#CA8A4B] tracking-[0.4em] font-black uppercase mb-2">Designated Rider</h3>
                    <h2 class="text-3xl font-serif italic text-white"><?= $order['first_name'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Searching...' ?></h2>
                    <div class="flex items-center gap-2 mt-2 text-stone-500">
                        <i data-lucide="bike" class="w-4 h-4 text-[#CA8A4B]"></i>
                        <span class="text-xs italic font-medium">Out for delivery to your location</span>
                    </div>
                </div>
                <?php if($order['rider_phone']): ?>
                <a href="tel:<?= $order['rider_phone'] ?>" class="w-16 h-16 bg-[#CA8A4B] rounded-3xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-[#CA8A4B]/20">
                    <i data-lucide="phone" class="w-7 h-7"></i>
                </a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-3 gap-4 py-6 border-y border-white/5 mb-8 text-center">
                <div>
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">Distance</p>
                    <p class="text-xl font-bold text-white" id="dist-val">--</p>
                </div>
                <div class="border-x border-white/5">
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">ETA</p>
                    <p class="text-xl font-bold text-white" id="time-val">--</p>
                </div>
                <div>
                    <p class="text-[9px] text-stone-500 uppercase font-black mb-1">Total</p>
                    <p class="text-xl font-bold text-[#CA8A4B]">₱<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>

            <div class="mb-8">
                <h4 class="text-[10px] text-stone-500 font-black uppercase mb-4 tracking-[0.2em]">Your Package</h4>
                <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
                    <?php
                    $imgs = explode('|', $order['product_images']);
                    foreach($imgs as $img): ?>
                        <img src="<?= trim($img) ?>" class="product-img" onerror="this.src='src/images/coffee-cup.png'">
                    <?php endforeach; ?>
                </div>
                <div class="bg-black/40 border border-white/5 rounded-2xl p-5 mt-2">
                    <p class="text-sm text-stone-300 font-medium italic leading-relaxed"><?= htmlspecialchars($order['items']) ?></p>
                </div>
            </div>

            <a href="tel:09123456789" class="w-full bg-stone-900 border border-[#CA8A4B]/20 flex items-center justify-center gap-3 py-5 rounded-2xl text-[11px] font-black uppercase tracking-[0.3em] text-stone-300">
                <i data-lucide="help-circle" class="w-4 h-4 text-[#CA8A4B]"></i> Support Center
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const destCoords = [<?= $dest_lat ?>, <?= $dest_lng ?>];
        
        // Map Layers
        const streetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        const satelliteMap = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}');

        const map = L.map('map', { zoomControl: false, layers: [streetMap] }).setView(destCoords, 16);

        let isSatellite = false;
        document.getElementById('layerToggle').addEventListener('click', function() {
            if(!isSatellite) {
                map.removeLayer(streetMap);
                satelliteMap.addTo(map);
                this.classList.add('active');
            } else {
                map.removeLayer(satelliteMap);
                streetMap.addTo(map);
                this.classList.remove('active');
            }
            isSatellite = !isSatellite;
        });

        const customerIcon = L.divIcon({
            html: `<div class="coffee-marker-bg"><i data-lucide="home" style="width:20px; color:white;"></i></div>`,
            className: 'custom-div-icon', iconSize: [44, 44], iconAnchor: [22, 22]
        });

        const riderIcon = L.divIcon({
            html: `<div style="background: #1a0f0a; width: 48px; height: 48px; border-radius: 50%; border: 3px solid #CA8A4B; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.6); color: #CA8A4B;"><i data-lucide="bike" style="width:28px; height:28px;"></i></div>`,
            className: 'custom-div-icon', iconSize: [48, 48], iconAnchor: [24, 24]
        });

        L.marker(destCoords, { icon: customerIcon }).addTo(map);
        let riderMarker = null;
        let hasVibrated = false;

        function recenterToCustomer() { map.flyTo(destCoords, 17); }
        function recenterToRider() { if(window.currentRiderPos) map.flyTo(window.currentRiderPos, 17); }

        async function updateTracking() {
            try {
                const response = await fetch(`get_rider_location.php?order_id=<?= $order_id ?>`);
                const data = await response.json();
                
                if (data.lat && data.lng) {
                    const riderPos = [data.lat, data.lng];
                    window.currentRiderPos = riderPos;

                    if (!riderMarker) {
                        riderMarker = L.marker(riderPos, { icon: riderIcon }).addTo(map);
                    } else {
                        riderMarker.setLatLng(riderPos);
                    }

                    // Road Routing with Timeout handling
                    const routeUrl = `https://router.project-osrm.org/route/v1/driving/${data.lng},${data.lat};${destCoords[1]},${destCoords[0]}?overview=full&geometries=geojson`;
                    
                    fetch(routeUrl).then(r => r.json()).then(routeData => {
                        if (routeData.routes && routeData.routes[0]) {
                            const coords = routeData.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                            if (window.routeLine) map.removeLayer(window.routeLine);
                            window.routeLine = L.polyline(coords, { color: '#CA8A4B', weight: 5, className: 'glow-line' }).addTo(map);

                            const dist = (routeData.routes[0].distance / 1000).toFixed(1);
                            const time = Math.ceil(routeData.routes[0].duration / 60);
                            
                            document.getElementById('dist-val').innerText = dist + " km";
                            document.getElementById('time-val').innerText = time + " min";
                            document.getElementById('eta-label').innerText = "Arriving in " + time + " mins";

                            // Vibration Alert (Within 100m)
                            if (routeData.routes[0].distance < 100 && !hasVibrated) {
                                if ("vibrate" in navigator) navigator.vibrate([500, 200, 500]);
                                hasVibrated = true;
                            }
                        }
                    }).catch(e => {
                        // Fallback straight line
                        if (window.routeLine) map.removeLayer(window.routeLine);
                        window.routeLine = L.polyline([riderPos, destCoords], { color: '#CA8A4B', weight: 3, dashArray: '10, 10' }).addTo(map);
                    });
                }
            } catch (e) { console.log("Updating..."); }
            lucide.createIcons();
        }

        // Drawer logic
        const drawer = document.getElementById('drawer');
        const snapPoints = [window.innerHeight * 0.05, window.innerHeight * 0.55, window.innerHeight * 0.82];
        let currentY = snapPoints[1];

        interact('#drawer').draggable({
            allowFrom: '#drag-handle',
            listeners: {
                move(event) {
                    currentY += event.dy;
                    if(currentY < snapPoints[0]) currentY = snapPoints[0];
                    drawer.style.transition = 'none';
                    drawer.style.transform = `translateY(${currentY}px)`;
                },
                end() {
                    const closest = snapPoints.reduce((p, c) => Math.abs(c - currentY) < Math.abs(p - currentY) ? c : p);
                    currentY = closest;
                    drawer.style.transition = 'transform 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.1)';
                    drawer.style.transform = `translateY(${currentY}px)`;
                }
            }
        });

        setInterval(updateTracking, 5000);
        updateTracking();
        lucide.createIcons();
    </script>
</body>
</html>