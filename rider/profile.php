<?php
session_start();
include '../db.php';
if (!isset($_SESSION['rider_id'])) { header("Location: login.php"); exit; }

$rider_id = $_SESSION['rider_id'];
$msg = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $vehicle = $_POST['vehicle'];
    $stmt = $pdo->prepare("UPDATE riders SET phone = ?, vehicle_details = ? WHERE id = ?");
    if($stmt->execute([$phone, $vehicle, $rider_id])) {
        $msg = "Profile updated successfully.";
    }
}

// Fetch Rider Data
$stmt = $pdo->prepare("SELECT * FROM riders WHERE id = ?");
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Profile | Kape de Isla</title>
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
            backdrop-filter: blur(25px); 
            border: 1px solid rgba(202, 138, 75, 0.2); 
            box-shadow: 0 10px 40px rgba(0,0,0,0.5); 
        }

        .premium-card { 
            background: linear-gradient(145deg, rgba(44, 26, 18, 0.6), rgba(15, 10, 7, 0.8)); 
            border: 1px solid rgba(202, 138, 75, 0.15); 
        }

        .input-gold { 
            background: rgba(0,0,0,0.4); 
            border: 1px solid rgba(255,255,255,0.05); 
            color: white; 
            transition: all 0.3s; 
        }
        
        .input-gold:focus { 
            border-color: #CA8A4B; 
            outline: none; 
            background: rgba(202, 138, 75, 0.05); 
            box-shadow: 0 0 15px rgba(202, 138, 75, 0.1);
        }

        .btn-gold { 
            background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%); 
            box-shadow: 0 4px 20px rgba(202, 138, 75, 0.3); 
        }

        .nav-item { transition: all 0.3s ease; opacity: 0.5; color: #e7e5e4; }
        .nav-active { opacity: 1 !important; color: #CA8A4B !important; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-xl mx-auto">
        <header class="text-center mb-10">
            <p class="text-[9px] tracking-[0.4em] text-[#CA8A4B] uppercase font-bold mb-1">Fleet Identification</p>
            <h1 class="font-serif text-4xl text-white italic">Rider Profile</h1>
        </header>

        <div class="premium-card p-8 rounded-[3rem] shadow-2xl mb-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <i data-lucide="shield-check" class="w-32 h-32 text-[#CA8A4B]"></i>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 relative z-10">
                <div class="w-24 h-24 rounded-full border-2 border-[#CA8A4B] p-1.5 shadow-lg shadow-black/50">
                    <div class="w-full h-full rounded-full bg-gradient-to-br from-[#442a12] to-[#150a07] flex items-center justify-center text-3xl font-serif italic text-[#CA8A4B]">
                        <?= substr($rider['first_name'], 0, 1) . substr($rider['last_name'], 0, 1) ?>
                    </div>
                </div>
                <div class="text-center sm:text-left">
                    <h2 class="text-3xl font-serif text-white italic leading-tight">
                        <?= htmlspecialchars($rider['first_name']) ?> <?= htmlspecialchars($rider['last_name']) ?>
                    </h2>
                    <div class="inline-block bg-[#CA8A4B]/10 border border-[#CA8A4B]/20 px-3 py-1 rounded-full mt-2">
                        <p class="text-[9px] text-[#CA8A4B] uppercase tracking-[0.2em] font-black">
                            Personnel ID: <?= str_pad($rider['id'], 5, '0', STR_PAD_LEFT) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 relative z-10">
                <div class="flex justify-between items-center bg-black/20 p-4 rounded-2xl border border-white/5">
                    <span class="text-[9px] uppercase font-black text-stone-500 tracking-widest">Email Access</span>
                    <span class="text-sm text-stone-300"><?= htmlspecialchars($rider['email']) ?></span>
                </div>
                <div class="flex justify-between items-center bg-black/20 p-4 rounded-2xl border border-white/5">
                    <span class="text-[9px] uppercase font-black text-stone-500 tracking-widest">Date Joined</span>
                    <span class="text-sm text-stone-300"><?= date('M d, Y', strtotime($rider['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-400 text-[10px] p-5 rounded-2xl mb-8 text-center font-bold uppercase tracking-widest animate-pulse">
                <i data-lucide="check-circle" class="w-4 h-4 inline-block mr-2 mb-0.5"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="glass-dark p-8 sm:p-10 rounded-[3rem]">
            <div class="flex items-center gap-3 mb-8">
                <div class="h-px flex-1 bg-white/5"></div>
                <h3 class="text-[10px] tracking-[0.3em] text-[#CA8A4B] uppercase font-black flex items-center gap-2 px-4">
                    <i data-lucide="settings-2" class="w-3 h-3"></i> Fleet Settings
                </h3>
                <div class="h-px flex-1 bg-white/5"></div>
            </div>

            <form method="POST" class="space-y-8">
                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest ml-4 mb-3 block">Mobile Contact</label>
                        <div class="relative">
                            <i data-lucide="phone" class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-600"></i>
                            <input type="text" name="phone" value="<?= htmlspecialchars($rider['phone']) ?>" 
                                class="input-gold w-full p-5 pl-14 rounded-3xl text-sm" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-[9px] text-stone-500 uppercase font-black tracking-widest ml-4 mb-3 block">Assigned Vehicle Info</label>
                        <div class="relative">
                            <i data-lucide="truck" class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-600"></i>
                            <input type="text" name="vehicle" value="<?= htmlspecialchars($rider['vehicle_details']) ?>" 
                                class="input-gold w-full p-5 pl-14 rounded-3xl text-sm" required>
                        </div>
                    </div>
                </div>

                <button name="update_profile" class="w-full py-5 btn-gold text-white rounded-3xl font-black uppercase text-[11px] tracking-[0.3em] hover:brightness-110 active:scale-[0.98] transition-all">
                    Update Fleet Records
                </button>
            </form>
        </div>
    </div>

    <?php include 'navbar.php'; ?>
    <script>lucide.createIcons();</script>
</body>
</html>