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

// --- EDIT LOGIC: Fetch existing data if editing ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $user_id]);
    $edit_data = $stmt->fetch();
}

// --- ACTION HANDLERS ---

// 1. Delete Address
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    header("Location: delivery_address.php"); exit;
}

// 2. Set Default
if (isset($_GET['set_default'])) {
    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$_GET['set_default'], $user_id]);
    header("Location: delivery_address.php"); exit;
}

// 3. Save / Update Address
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_address'])) {
    $label = $_POST['label'];
    $house_no = $_POST['house_no'];
    $street = $_POST['street'];
    $brgy = $_POST['barangay'];
    $mun = $_POST['municipality'];
    $note = $_POST['note'];
    
    // Check limit only for new addresses (4-spot limit)
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $current_count = $count_stmt->fetchColumn();

    if (!isset($_POST['address_id']) && $current_count >= 4) {
        $error_msg = "Limit reached! You can only save up to 4 addresses.";
    } else {
        if (isset($_POST['address_id']) && !empty($_POST['address_id'])) {
            // UPDATE EXISTING
            $stmt = $pdo->prepare("UPDATE user_addresses SET label=?, house_no=?, street=?, barangay=?, municipality=?, additional_info=? WHERE id=? AND user_id=?");
            $stmt->execute([$label, $house_no, $street, $brgy, $mun, $note, $_POST['address_id'], $user_id]);
        } else {
            // INSERT NEW
            $is_default = ($current_count == 0) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, label, house_no, street, barangay, municipality, additional_info, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $label, $house_no, $street, $brgy, $mun, $note, $is_default]);
        }
        header("Location: delivery_address.php"); exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Addresses | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; color: white; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .input-field { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 14px; width: 100%; color: white; outline: none; transition: all 0.3s; font-size: 13px; }
        .input-field:focus { border-color: #CA8A4B; background: rgba(0,0,0,0.6); }
        select option { background: #2C1A12; color: white; }
    </style>
</head>
<body class="min-h-screen pb-20">
    
    <div class="max-w-4xl mx-auto px-6 pt-12">
        <a href="<?= $back_url ?>" class="inline-flex items-center gap-2 text-stone-500 hover:text-[#CA8A4B] transition uppercase text-[10px] font-bold tracking-[0.2em] mb-10">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= $back_label ?>
        </a>

        <?php if(isset($error_msg)): ?>
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-2xl text-red-200 text-[10px] uppercase font-bold tracking-widest text-center">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <div>
                <h2 class="font-serif text-4xl italic mb-2">Your Locations</h2>
                <p class="text-[#CA8A4B] text-[9px] uppercase tracking-[0.3em] font-bold mb-8">Up to 4 Saved Spots</p>

                <div class="space-y-4">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
                    $stmt->execute([$user_id]);
                    $addresses = $stmt->fetchAll();

                    foreach($addresses as $addr): ?>
                    <div class="glass-dark p-6 rounded-[2rem] border <?= $addr['is_default'] ? 'border-[#CA8A4B]/50' : 'border-white/5' ?> relative group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-[#CA8A4B]/10 rounded-lg">
                                    <i data-lucide="<?= $addr['label'] == 'Work' ? 'briefcase' : ($addr['label'] == 'Home' ? 'home' : 'map-pin') ?>" class="w-4 h-4 text-[#CA8A4B]"></i>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest"><?= $addr['label'] ?></span>
                                <?php if($addr['is_default']): ?>
                                    <span class="bg-[#CA8A4B] text-white text-[7px] px-2 py-1 rounded-full uppercase">Default</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                                <a href="?edit=<?= $addr['id'] ?>" class="p-2 hover:bg-blue-500/20 rounded-full text-blue-400" title="Edit"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                                <?php if(!$addr['is_default']): ?>
                                    <a href="?set_default=<?= $addr['id'] ?>" class="p-2 hover:bg-green-500/20 rounded-full text-green-500" title="Set Default"><i data-lucide="check"></i></a>
                                <?php endif; ?>
                                <a href="?delete=<?= $addr['id'] ?>" class="p-2 hover:bg-red-500/20 rounded-full text-red-500" onclick="return confirm('Remove address?')"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                            </div>
                        </div>
                        <p class="text-stone-300 text-sm leading-relaxed">
                            <?= "{$addr['house_no']} {$addr['street']}, Brgy. {$addr['barangay']}, {$addr['municipality']}" ?>
                        </p>
                        <?php if($addr['additional_info']): ?>
                            <p class="text-[10px] text-stone-500 mt-2 italic flex items-center gap-1">
                                <i data-lucide="info" class="w-3 h-3"></i> <?= htmlspecialchars($addr['additional_info']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if(count($addresses) == 0): ?>
                        <div class="text-center py-10 opacity-40 italic text-sm">No addresses saved yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-dark p-10 rounded-[3rem] border border-white/10">
                <h3 class="font-serif text-2xl italic mb-6 text-white"><?= $edit_data ? 'Edit Address' : 'Add New Address' ?></h3>
                
                <button onclick="getLocation()" class="w-full mb-6 py-4 bg-[#CA8A4B]/10 border border-[#CA8A4B]/20 rounded-2xl text-[10px] uppercase tracking-widest font-bold text-[#CA8A4B] flex items-center justify-center gap-3 hover:bg-[#CA8A4B]/20 transition">
                    <i data-lucide="navigation" class="w-4 h-4"></i> Auto-Detect My Location
                </button>

                <form method="POST" class="space-y-4">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="address_id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Address Label</label>
                        <select name="label" class="input-field">
                            <?php $labels = ['Home', 'Work', 'Office', 'Other']; 
                            foreach($labels as $l): ?>
                                <option value="<?= $l ?>" <?= ($edit_data && $edit_data['label'] == $l) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" id="house_no" name="house_no" placeholder="House/Bldg #" class="input-field" value="<?= $edit_data['house_no'] ?? '' ?>" required>
                        <input type="text" id="street" name="street" placeholder="Street Name" class="input-field" value="<?= $edit_data['street'] ?? '' ?>" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Municipality</label>
                            <select name="municipality" id="mun_select" class="input-field" onchange="updateBarangays()">
                                <option value="Santa Fe" <?= ($edit_data && $edit_data['municipality'] == 'Santa Fe') ? 'selected' : '' ?>>Santa Fe</option>
                                <option value="Bantayan" <?= ($edit_data && $edit_data['municipality'] == 'Bantayan') ? 'selected' : '' ?>>Bantayan</option>
                                <option value="Madridejos" <?= ($edit_data && $edit_data['municipality'] == 'Madridejos') ? 'selected' : '' ?>>Madridejos</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Barangay</label>
                            <select name="barangay" id="brgy_select" class="input-field"></select>
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] text-stone-500 uppercase font-bold tracking-widest block mb-2">Additional Info (Palatandaan)</label>
                        <textarea name="note" id="note" class="input-field h-20" placeholder="E.g. Green gate near the big Mango tree..."><?= $edit_data['additional_info'] ?? '' ?></textarea>
                    </div>

                    <div class="flex gap-4">
                        <?php if($edit_data): ?>
                            <a href="delivery_address.php" class="w-1/3 py-5 bg-white/5 text-white rounded-2xl font-bold uppercase tracking-widest text-[11px] text-center border border-white/10">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" name="save_address" class="flex-1 py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold uppercase tracking-widest text-[11px] shadow-lg shadow-[#CA8A4B]/20 hover:scale-[1.02] transition">
                            <?= $edit_data ? 'Update Address' : 'Confirm & Save Address' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const locationData = {
            "Santa Fe": ["Pooc", "Poblacion", "Talisay", "Balidbid", "Maricaban", "Okoy"],
            "Bantayan": ["Binaobao", "Suba", "Ticad", "Sillion", "Mojon"],
            "Madridejos": ["Poblacion", "Malocloc", "Mancilang", "Tarong"]
        };

        function updateBarangays() {
            const mun = document.getElementById('mun_select').value;
            const brgySelect = document.getElementById('brgy_select');
            const savedBrgy = "<?= $edit_data['barangay'] ?? '' ?>";
            brgySelect.innerHTML = "";
            locationData[mun].forEach(brgy => {
                let opt = document.createElement('option');
                opt.value = brgy;
                opt.innerHTML = brgy;
                if(brgy === savedBrgy) opt.selected = true;
                brgySelect.appendChild(opt);
            });
        }

        updateBarangays();

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    try {
                        // FIX: Calling through your local API folder to solve CORS
                        const response = await fetch(`api/get_location.php?lat=${lat}&lon=${lon}`);
                        const data = await response.json();
                        
                        if(data.address) {
                            document.getElementById('street').value = data.address.road || '';
                            document.getElementById('note').value = "Auto-located near: " + (data.display_name.split(',')[0]);
                            alert("Location Detected: " + (data.address.suburb || data.address.neighbourhood || "Area found"));
                        }
                    } catch (e) {
                        alert("Could not fetch address details through the proxy. Please fill manually.");
                    }
                });
            }
        }
    </script>
</body>
</html>