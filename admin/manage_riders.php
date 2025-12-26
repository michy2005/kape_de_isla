<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Handle Adding Rider
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_rider'])) {
    $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO riders (first_name, middle_name, last_name, email, phone, password, vehicle_details, status) VALUES (?,?,?,?,?,?,?, 'Available')");
    $stmt->execute([$_POST['fname'], $_POST['mname'], $_POST['lname'], $_POST['email'], $_POST['phone'], $pw, $_POST['vehicle']]);
    header("Location: manage_riders.php?success=1");
    exit;
}

// Handle Edit Rider
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_rider'])) {
    $sql = "UPDATE riders SET first_name=?, middle_name=?, last_name=?, email=?, phone=?, vehicle_details=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['fname'], $_POST['mname'], $_POST['lname'], $_POST['email'], $_POST['phone'], $_POST['vehicle'], $_POST['rider_id']]);
    header("Location: manage_riders.php?updated=1");
    exit;
}

// Handle Delete Rider
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM riders WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: manage_riders.php?deleted=1");
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle_id']) && isset($_GET['current_status'])) {
    $new_status = ($_GET['current_status'] == 'Available') ? 'Offline' : 'Available';
    $stmt = $pdo->prepare("UPDATE riders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $_GET['toggle_id']]);
    header("Location: manage_riders.php");
    exit;
}

$riders = $pdo->query("SELECT * FROM riders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Delivery Team | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap');
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; color: #e7e5e4; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .input-premium { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease; color: white; }
        .input-premium:focus { border-color: #CA8A4B; background: rgba(0, 0, 0, 0.5); outline: none; box-shadow: 0 0 15px rgba(202, 138, 75, 0.1); }
        .btn-gold { background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%); transition: all 0.3s ease; }
        .btn-gold:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .rider-card { transition: all 0.3s ease; border-left: 4px solid transparent; cursor: pointer; }
        .rider-card:hover { background: rgba(255, 255, 255, 0.03); transform: translateX(5px); }
        .status-Available { border-left-color: #22c55e; color: #4ade80; }
        .status-On.Delivery { border-left-color: #38bdf8; color: #38bdf8; }
        .status-Offline { border-left-color: #78716c; color: #a8a29e; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center; }
    </style>
</head>

<body class="p-6 md:p-12">
    <div class="max-w-6xl mx-auto">
        <header class="flex justify-between items-center mb-12">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-[#CA8A4B]/10 rounded-2xl border border-[#CA8A4B]/20">
                    <i data-lucide="users" class="w-8 h-8 text-[#CA8A4B]"></i>
                </div>
                <div>
                    <h1 class="font-serif text-4xl text-white italic">Logistics Personnel</h1>
                    <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold">Manage Delivery Riders</p>
                </div>
            </div>
            <a href="dashboard.php" class="glass-dark px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-1">
                <div class="glass-dark p-8 rounded-[2.5rem] sticky top-8">
                    <h3 class="font-serif text-xl text-white italic mb-6">Register New Rider</h3>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <input name="fname" placeholder="First Name" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                            <input name="lname" placeholder="Last Name" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                        </div>
                        <input name="mname" placeholder="Middle Name (Optional)" class="input-premium p-4 rounded-2xl text-sm w-full">
                        <input name="email" type="email" placeholder="Email Address" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                        <input name="phone" placeholder="Contact Number" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                        <input name="vehicle" placeholder="Vehicle Details (Model/Plate)" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                        <input name="password" type="password" placeholder="Access Password" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                        <button name="add_rider" class="btn-gold w-full py-4 rounded-2xl text-white font-black uppercase text-[10px] tracking-widest mt-4">Add to Fleet</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="flex justify-between items-center mb-6 px-4">
                    <h3 class="text-[10px] tracking-[0.2em] text-[#CA8A4B] uppercase font-black">Active Fleet Status</h3>
                    <span class="text-[10px] text-stone-500"><?= count($riders) ?> Registered Personnel</span>
                </div>

                <div class="grid gap-4">
                    <?php foreach($riders as $r): ?>
                        <div class="glass-dark p-6 rounded-[2rem] flex justify-between items-center rider-card status-<?= $r['status'] ?>" onclick='viewRider(<?= json_encode($r) ?>)'>
                            <div class="flex items-center gap-6">
                                <div class="w-12 h-12 rounded-full bg-[#CA8A4B]/10 flex items-center justify-center border border-[#CA8A4B]/20">
                                    <span class="text-[#CA8A4B] font-bold"><?= substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1) ?></span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-3">
                                        <p class="text-lg font-serif text-white italic"><?= $r['first_name'] ?> <?= $r['last_name'] ?></p>
                                        <span class="text-[8px] px-2 py-0.5 rounded-md border border-white/10 text-stone-500 uppercase font-black tracking-tighter">ID: <?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1">
                                        <p class="text-[10px] text-stone-400 flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3 text-[#CA8A4B]"></i> <?= $r['phone'] ?></p>
                                        <p class="text-[10px] text-stone-400 flex items-center gap-1"><i data-lucide="truck" class="w-3 h-3 text-[#CA8A4B]"></i> <?= $r['vehicle_details'] ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4" onclick="event.stopPropagation()">
                                <div class="text-right mr-4">
                                    <span class="text-[9px] font-black uppercase tracking-widest block mb-1">● <?= $r['status'] ?></span>
                                    <p class="text-[8px] text-stone-600 font-bold uppercase tracking-tighter">Current Status</p>
                                </div>
                                <a href="?toggle_id=<?= $r['id'] ?>&current_status=<?= $r['status'] ?>" class="p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-[#CA8A4B]/10 hover:border-[#CA8A4B]/30 transition-all" title="Toggle Status">
                                    <i data-lucide="power" class="w-4 h-4 text-stone-500"></i>
                                </a>
                                <button onclick='openEditModal(<?= json_encode($r) ?>)' class="p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-blue-500/10 hover:border-blue-500/30 transition-all" title="Edit">
                                    <i data-lucide="edit-3" class="w-4 h-4 text-stone-500"></i>
                                </button>
                                <a href="?delete_id=<?= $r['id'] ?>" onclick="return confirm('Archive this rider from the fleet?')" class="p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-red-500/10 hover:border-red-500/30 transition-all" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4 text-stone-500"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="glass-dark p-10 rounded-[3rem] w-full max-w-md border border-[#CA8A4B]/30 relative">
            <button onclick="closeModals()" class="absolute top-6 right-6 text-stone-500 hover:text-white"><i data-lucide="x"></i></button>
            <h2 class="font-serif text-2xl text-white italic mb-6">Update Rider Details</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="rider_id" id="edit_id">
                <div class="grid grid-cols-2 gap-4">
                    <input name="fname" id="edit_fname" placeholder="First Name" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                    <input name="lname" id="edit_lname" placeholder="Last Name" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                </div>
                <input name="mname" id="edit_mname" placeholder="Middle Name" class="input-premium p-4 rounded-2xl text-sm w-full">
                <input name="email" id="edit_email" type="email" placeholder="Email Address" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                <input name="phone" id="edit_phone" placeholder="Contact Number" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                <input name="vehicle" id="edit_vehicle" placeholder="Vehicle Details" class="input-premium p-4 rounded-2xl text-sm w-full" required>
                <button name="edit_rider" class="btn-gold w-full py-4 rounded-2xl text-white font-black uppercase text-[10px] tracking-widest mt-4">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="glass-dark p-10 rounded-[3rem] w-full max-w-md border border-[#CA8A4B]/30 relative text-center">
            <button onclick="closeModals()" class="absolute top-6 right-6 text-stone-500 hover:text-white"><i data-lucide="x"></i></button>
            <div id="view_initials" class="w-20 h-20 rounded-full bg-[#CA8A4B]/20 border border-[#CA8A4B]/40 flex items-center justify-center mx-auto mb-4 text-2xl font-bold text-[#CA8A4B]"></div>
            <h2 id="view_name" class="font-serif text-3xl text-white italic"></h2>
            <p id="view_status" class="text-[10px] font-black tracking-widest uppercase mt-1 mb-8"></p>
            
            <div class="space-y-3 text-left bg-black/20 p-6 rounded-2xl border border-white/5">
                <div class="flex justify-between"><span class="text-stone-500 text-[10px] uppercase font-bold">Email</span><span id="view_email" class="text-sm"></span></div>
                <div class="flex justify-between"><span class="text-stone-500 text-[10px] uppercase font-bold">Phone</span><span id="view_phone" class="text-sm"></span></div>
                <div class="flex justify-between"><span class="text-stone-500 text-[10px] uppercase font-bold">Vehicle</span><span id="view_vehicle" class="text-sm italic"></span></div>
                <div class="flex justify-between"><span class="text-stone-500 text-[10px] uppercase font-bold">Joined</span><span id="view_joined" class="text-sm"></span></div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function viewRider(rider) {
            document.getElementById('view_name').innerText = rider.first_name + ' ' + (rider.middle_name ? rider.middle_name + ' ' : '') + rider.last_name;
            document.getElementById('view_status').innerText = '● ' + rider.status;
            document.getElementById('view_status').className = 'text-[10px] font-black tracking-widest uppercase mt-1 mb-8 status-' + rider.status;
            document.getElementById('view_email').innerText = rider.email;
            document.getElementById('view_phone').innerText = rider.phone;
            document.getElementById('view_vehicle').innerText = rider.vehicle_details;
            document.getElementById('view_joined').innerText = new Date(rider.created_at).toLocaleDateString();
            document.getElementById('view_initials').innerText = rider.first_name[0] + rider.last_name[0];
            document.getElementById('viewModal').style.display = 'flex';
        }

        function openEditModal(rider) {
            document.getElementById('edit_id').value = rider.id;
            document.getElementById('edit_fname').value = rider.first_name;
            document.getElementById('edit_mname').value = rider.middle_name;
            document.getElementById('edit_lname').value = rider.last_name;
            document.getElementById('edit_email').value = rider.email;
            document.getElementById('edit_phone').value = rider.phone;
            document.getElementById('edit_vehicle').value = rider.vehicle_details;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModals() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('viewModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') closeModals();
        }
    </script>
</body>
</html>