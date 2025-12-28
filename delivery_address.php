<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// --- NAVIGATION LOGIC ---
if (!isset($_SESSION['addr_back_url']) || (strpos($_SERVER['HTTP_REFERER'] ?? '', 'delivery_address.php') === false)) {
    $_SESSION['addr_back_url'] = $_SERVER['HTTP_REFERER'] ?? 'index.php';
}
$back_url = $_SESSION['addr_back_url'];
$back_label = (strpos($back_url, 'cart.php') !== false) ? 'Back to Basket' : 'Back to Profile';

// --- EDIT LOGIC ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $user_id]);
    $edit_data = $stmt->fetch();
}

// --- ACTION HANDLERS ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Address removed successfully!'];
    header("Location: delivery_address.php"); exit;
}

if (isset($_GET['set_default'])) {
    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$_GET['set_default'], $user_id]);
    $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Default address updated!'];
    header("Location: delivery_address.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_address'])) {
    $label = $_POST['label'];
    $phone = $_POST['phone'];
    $house_no = $_POST['house_no'];
    $street = $_POST['street'];
    $brgy = $_POST['barangay'];
    $mun = $_POST['municipality'];
    $note = $_POST['note'];
    $lat = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $lng = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $current_count = $count_stmt->fetchColumn();

    if (!isset($_POST['address_id']) && $current_count >= 4) {
        $_SESSION['alert'] = ['type' => 'error', 'msg' => 'Limit reached! You can only save up to 4 addresses.'];
    } else {
        if (isset($_POST['address_id']) && !empty($_POST['address_id'])) {
            $stmt = $pdo->prepare("UPDATE user_addresses SET label=?, phone=?, house_no=?, street=?, barangay=?, municipality=?, additional_info=?, latitude=?, longitude=? WHERE id=? AND user_id=?");
            $stmt->execute([$label, $phone, $house_no, $street, $brgy, $mun, $note, $lat, $lng, $_POST['address_id'], $user_id]);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'Address updated successfully!'];
        } else {
            $is_default = ($current_count == 0) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, label, phone, house_no, street, barangay, municipality, additional_info, is_default, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $label, $phone, $house_no, $street, $brgy, $mun, $note, $is_default, $lat, $lng]);
            $_SESSION['alert'] = ['type' => 'success', 'msg' => 'New address pinned successfully!'];
        }
        header("Location: delivery_address.php"); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=Inter:wght@300;400;600;700&display=swap');
        body { background: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; color: white; font-family: 'Inter', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .glass-dark { background: rgba(28, 18, 13, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .input-field { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 14px; width: 100%; color: white; outline: none; transition: all 0.3s; font-size: 13px; }
        .input-field:focus { border-color: #CA8A4B; background: rgba(0,0,0,0.6); box-shadow: 0 0 15px rgba(202, 138, 75, 0.1); }
        
        #map-wrapper { display: none; position: relative; margin-bottom: 20px; transition: all 0.5s ease; opacity: 0; transform: translateY(10px); }
        #map-wrapper.active { display: block; opacity: 1; transform: translateY(0); }
        #map-container { height: 400px; width: 100%; border-radius: 25px; border: 1px solid rgba(202, 138, 75, 0.3); overflow: hidden; position: relative; z-index: 10; }
        
        /* Fixed UI Overlays */
        .map-close-btn { position: absolute; top: 15px; right: 15px; z-index: 1001; background: rgba(28, 18, 13, 0.9); border: 1px solid rgba(202, 138, 75, 0.4); color: #CA8A4B; padding: 8px; border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .map-close-btn:hover { background: #CA8A4B; color: white; transform: rotate(90deg); }

        .search-trigger-btn { position: absolute; top: 15px; left: 15px; z-index: 1001; background: rgba(28, 18, 13, 0.9); border: 1px solid rgba(202, 138, 75, 0.4); color: #CA8A4B; padding: 8px; border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .search-trigger-btn:hover { background: #CA8A4B; color: white; }

        #search-box-container { position: absolute; top: 15px; left: 60px; right: 60px; z-index: 1002; display: none; }
        #search-box-container.visible { display: block; }
        .search-input { background: rgba(28, 18, 13, 0.95); border: 1px solid #CA8A4B; border-radius: 12px; padding: 8px 15px; color: white; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5); outline: none; }

        /* Repositioned Leaflet Controls */
        .leaflet-bottom.leaflet-left { margin-bottom: 10px; margin-left: 10px; }
        .leaflet-bottom.leaflet-right { margin-bottom: 10px; margin-right: 10px; }
        .leaflet-control-zoom { border: none !important; margin-bottom: 20px !important; }
        .leaflet-control-zoom-in, .leaflet-control-zoom-out { background: rgba(28, 18, 13, 0.9) !important; color: #CA8A4B !important; border: 1px solid rgba(202, 138, 75, 0.3) !important; }

        #map-loader { position: absolute; inset: 0; background: rgba(26, 15, 10, 0.8); z-index: 1050; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .spinner { width: 40px; height: 40px; border: 3px solid rgba(202, 138, 75, 0.1); border-top: 3px solid #CA8A4B; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .btn-gold { background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%); transition: all 0.4s; }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(202, 138, 75, 0.3); }

        /* SweetAlert Dark Theme Override */
        .swal2-popup { background: #1c120d !important; color: white !important; border-radius: 2rem !important; border: 1px solid rgba(202, 138, 75, 0.2) !important; }
        .swal2-title { color: white !important; font-family: 'Playfair Display', serif !important; }
        .swal2-confirm { background: #CA8A4B !important; border-radius: 12px !important; }
    </style>
</head>
<body class="min-h-screen pb-20">
    <div class="max-w-6xl mx-auto px-6 pt-12">
        <header class="flex justify-between items-center mb-12">
            <div>
                <a href="<?= $back_url ?>" class="inline-flex items-center gap-2 text-stone-500 hover:text-[#CA8A4B] transition uppercase text-[10px] font-bold tracking-[0.2em] mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= $back_label ?>
                </a>
                <h1 class="font-serif text-5xl italic text-white">Delivery Spots</h1>
            </div>
            <div class="text-right">
                <a href="my_orders.php" class="inline-flex items-center gap-2 text-[#CA8A4B] hover:text-white transition text-[10px] uppercase tracking-[0.3em] font-bold mb-2">
                    <i data-lucide="map" class="w-4 h-4"></i> Track Orders
                </a>
                <p class="text-[#CA8A4B] text-[10px] uppercase tracking-[0.3em] font-bold">Island Logistics</p>
                <p class="text-stone-500 text-xs mt-1">Santa Fe • Bantayan • Madridejos</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <div class="lg:col-span-5 space-y-6">
                <h3 class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-black mb-4">Saved Locations</h3>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
                $stmt->execute([$user_id]);
                $addresses = $stmt->fetchAll();
                foreach($addresses as $addr): ?>
                <div class="glass-dark p-6 rounded-[2.5rem] border <?= $addr['is_default'] ? 'border-[#CA8A4B]/40 shadow-lg shadow-[#CA8A4B]/5' : 'border-white/5' ?> relative group transition-all duration-500 hover:scale-[1.01]">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#CA8A4B]/10 rounded-xl flex items-center justify-center">
                                <i data-lucide="<?= $addr['label'] == 'Work' ? 'briefcase' : ($addr['label'] == 'Home' ? 'home' : 'map-pin') ?>" class="w-5 h-5 text-[#CA8A4B]"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-black uppercase tracking-widest block text-white"><?= $addr['label'] ?></span>
                                <span class="text-[9px] text-stone-500 font-bold"><?= $addr['phone'] ?></span>
                            </div>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                            <a href="?edit=<?= $addr['id'] ?>" class="p-2 hover:bg-white/10 rounded-full text-[#CA8A4B]"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                            <?php if(!$addr['is_default']): ?>
                                <a href="?set_default=<?= $addr['id'] ?>" class="p-2 hover:bg-white/10 rounded-full text-green-500"><i data-lucide="check" class="w-4 h-4"></i></a>
                            <?php endif; ?>
                            <a href="javascript:void(0)" onclick="confirmDelete(<?= $addr['id'] ?>)" class="p-2 hover:bg-white/10 rounded-full text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                        </div>
                    </div>
                    
                    <p class="text-stone-300 text-sm leading-relaxed font-medium">
                        <?= "{$addr['house_no']} {$addr['street']}, Brgy. {$addr['barangay']}, {$addr['municipality']}" ?>
                    </p>

                    <?php if(!empty($addr['additional_info'])): ?>
                    <div class="mt-3 p-3 bg-[#CA8A4B]/5 rounded-xl border border-[#CA8A4B]/10">
                         <p class="text-[10px] text-stone-400 italic flex gap-2">
                            <i data-lucide="info" class="w-3 h-3 text-[#CA8A4B]"></i>
                            "<?= htmlspecialchars($addr['additional_info']) ?>"
                         </p>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-4 border-t border-white/5 flex items-center justify-between">
                        <div class="flex gap-2">
                            <?php if($addr['latitude']): ?>
                                <span class="flex items-center gap-1 text-[8px] bg-green-500/10 text-green-400 border border-green-500/20 px-2.5 py-1 rounded-full uppercase font-black">
                                    <span class="w-1 h-1 bg-green-400 rounded-full animate-pulse"></span> GPS Verified
                                </span>
                            <?php endif; ?>
                            <?php if($addr['is_default']): ?>
                                <span class="text-[8px] bg-[#CA8A4B] text-white px-2.5 py-1 rounded-full uppercase font-black tracking-tighter">Default</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="lg:col-span-7">
                <div class="glass-dark p-10 rounded-[3.5rem] border border-white/10 sticky top-12">
                    <h3 class="font-serif text-3xl italic mb-8 text-white"><?= $edit_data ? 'Update Location' : 'New Island Spot' ?></h3>
                    
                    <div id="map-wrapper">
                        <button type="button" onclick="toggleSearch()" class="search-trigger-btn"><i data-lucide="search" class="w-5 h-5"></i></button>
                        <div id="search-box-container">
                            <input type="text" id="map-search" class="search-input" placeholder="Search landmark (e.g. Santa Fe Beach)...">
                        </div>
                        <button type="button" onclick="closeMap()" class="map-close-btn"><i data-lucide="x" class="w-5 h-5"></i></button>
                        
                        <div id="map-loader"><div class="spinner"></div></div>
                        <div id="map-container"><div id="map" class="w-full h-full"></div></div>
                    </div>

                    <form method="POST" id="addressForm" class="space-y-6">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="address_id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Latitude</label>
                                <input type="text" name="latitude" id="lat_input" class="input-field bg-white/5" readonly value="<?= $edit_data['latitude'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Longitude</label>
                                <input type="text" name="longitude" id="lng_input" class="input-field bg-white/5" readonly value="<?= $edit_data['longitude'] ?? '' ?>">
                            </div>
                            <button type="button" id="mark_btn" onclick="detectGPS()" class="col-span-2 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] uppercase tracking-[0.2em] font-black text-[#CA8A4B] flex items-center justify-center gap-3 hover:bg-[#CA8A4B]/10 transition-all">
                                <i data-lucide="map-pin" class="w-4 h-4"></i> Mark Location
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Location Type</label>
                                <select name="label" class="input-field">
                                    <?php foreach(['Home', 'Work', 'Office', 'Partner', 'Other'] as $l): ?>
                                        <option value="<?= $l ?>" <?= ($edit_data && $edit_data['label'] == $l) ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Mobile Number</label>
                                <input type="text" name="phone" placeholder="0912..." class="input-field" value="<?= $edit_data['phone'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" id="house_no" name="house_no" placeholder="House/Lot #" class="input-field" value="<?= $edit_data['house_no'] ?? '' ?>" required>
                            <input type="text" id="street" name="street" placeholder="Street/Road Name" class="input-field" value="<?= $edit_data['street'] ?? '' ?>" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Municipality</label>
                                <input type="text" name="municipality" id="mun_input" class="input-field" placeholder="Municipality" value="<?= $edit_data['municipality'] ?? '' ?>" required>
                            </div>
                            <div>
                                <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Barangay</label>
                                <input type="text" name="barangay" id="brgy_input" class="input-field" placeholder="Barangay" value="<?= $edit_data['barangay'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest block mb-2">Additional Information</label>
                            <textarea name="note" id="note" class="input-field h-24" placeholder="Landmarks, building colors..."><?= $edit_data['additional_info'] ?? '' ?></textarea>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="submit" name="save_address" class="btn-gold flex-[2] py-5 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-2xl">
                                <?= $edit_data ? 'Update Delivery Spot' : 'Pin Address to Map' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        lucide.createIcons();

        // SweetAlert Handler for PHP Sessions
        <?php if(isset($_SESSION['alert'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['alert']['type'] ?>',
                title: '<?= $_SESSION['alert']['type'] == 'success' ? 'Success!' : 'Oops!' ?>',
                text: '<?= $_SESSION['alert']['msg'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        function confirmDelete(id) {
            Swal.fire({
                title: 'Remove this spot?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete=${id}`;
                }
            });
        }

        let map, marker;
        const initialLat = <?= $edit_data['latitude'] ?? 11.1541 ?>;
        const initialLng = <?= $edit_data['longitude'] ?? 123.8055 ?>;

        function closeMap() {
            const wrapper = document.getElementById('map-wrapper');
            wrapper.classList.remove('active');
            setTimeout(() => { wrapper.style.display = 'none'; }, 500);
        }

        function toggleSearch() {
            document.getElementById('search-box-container').classList.toggle('visible');
        }

        function initMap(lat, lng) {
            const wrapper = document.getElementById('map-wrapper');
            const loader = document.getElementById('map-loader');
            wrapper.style.display = 'block';
            setTimeout(() => { wrapper.classList.add('active'); }, 10);
            
            if (map) {
                map.setView([lat, lng], 17);
                marker.setLatLng([lat, lng]);
                loader.style.display = 'none';
                return;
            }

            const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' });
            const satellite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { attribution: '&copy; Google' });

            map = L.map('map', {
                center: [lat, lng],
                zoom: 17,
                zoomControl: false, 
                layers: [satellite]
            });

            L.control.zoom({ position: 'bottomright' }).addTo(map);
            L.control.layers({ "Satellite": satellite, "Streets": streets }, null, { position: 'bottomleft' }).addTo(map);
            
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            map.whenReady(() => { loader.style.display = 'none'; });

            marker.on('dragend', () => { updateInputsFromCoords(marker.getLatLng().lat, marker.getLatLng().lng); });
            map.on('click', (e) => {
                marker.setLatLng(e.latlng);
                updateInputsFromCoords(e.latlng.lat, e.latlng.lng);
            });

            document.getElementById('map-search').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value;
                    fetch(`api/get_location.php?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.length > 0) {
                                const result = data[0];
                                const nLat = parseFloat(result.lat);
                                const nLng = parseFloat(result.lon);
                                map.setView([nLat, nLng], 17);
                                marker.setLatLng([nLat, nLng]);
                                updateInputsFromCoords(nLat, nLng);
                            } else {
                                Swal.fire({ icon: 'info', title: 'Not found', text: 'Location not found on island.' });
                            }
                        });
                }
            });
        }

        async function updateInputsFromCoords(lat, lng) {
            document.getElementById('lat_input').value = lat.toFixed(6);
            document.getElementById('lng_input').value = lng.toFixed(6);
            document.getElementById('map-loader').style.display = 'flex';

            const url = `api/get_location.php?lat=${lat}&lon=${lng}`;

            fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.address) {
                    const a = data.address;
                    document.getElementById('street').value = a.road || a.pedestrian || a.suburb || '';
                    document.getElementById('brgy_input').value = a.village || a.neighbourhood || a.suburb || a.hamlet || '';
                    document.getElementById('mun_input').value = a.town || a.city || a.municipality || '';
                }
                document.getElementById('map-loader').style.display = 'none';
            })
            .catch(err => {
                console.error("Geocoding Error:", err);
                document.getElementById('map-loader').style.display = 'none';
            });
        }

        function detectGPS() {
            document.getElementById('map-loader').style.display = 'flex';
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    initMap(pos.coords.latitude, pos.coords.longitude);
                    updateInputsFromCoords(pos.coords.latitude, pos.coords.longitude);
                }, () => {
                    Swal.fire({ icon: 'error', title: 'GPS Failed', text: 'Please enable location services.' });
                    initMap(initialLat, initialLng);
                }, { enableHighAccuracy: true });
            }
        }

        <?php if($edit_data): ?> window.onload = () => { initMap(initialLat, initialLng); }; <?php endif; ?>
    </script>
</body>
</html>