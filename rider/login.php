<?php
session_start();
include '../db.php';
$msg = "";
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM riders WHERE email = ?");
    $stmt->execute([$email]);
    $rider = $stmt->fetch();
    
    if ($rider && password_verify($password, $rider['password'])) {
        $_SESSION['rider_id'] = $rider['id'];
        $_SESSION['rider_name'] = $rider['first_name']; 
        header("Location: dashboard.php"); 
        exit;
    } else { 
        $msg = "Rider Access Denied. Please check your credentials."; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Login | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;600&display=swap');
        
        body { 
            background-color: #1a0f0a; 
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); 
            background-blend-mode: soft-light;
            font-family: 'Inter', sans-serif;
        }
        
        .font-serif { font-family: 'Playfair Display', serif; }
        
        .glass-dark { 
            background: rgba(28, 18, 13, 0.8); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(202, 138, 75, 0.2); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .logo-glass { 
            background: rgba(202, 138, 75, 0.15); 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(202, 138, 75, 0.3); 
            box-shadow: 0 0 25px rgba(202, 138, 75, 0.2); 
        }

        /* Improved tap targets for mobile */
        input::placeholder { font-size: 10px; letter-spacing: 0.2em; }
    </style>
</head>
<body class="text-stone-200 min-h-screen flex items-center justify-center p-4 sm:p-6">
    
    <div class="glass-dark w-full max-w-[420px] p-8 sm:p-12 rounded-[2.5rem] sm:rounded-[3.5rem] relative overflow-hidden">
        
        <div class="flex flex-col items-center mb-8 sm:mb-10">
            <div class="w-24 h-24 sm:w-28 sm:h-28 logo-glass rounded-full flex items-center justify-center mb-6 p-1 overflow-hidden">
                <img src="../src/images/logo3.png" alt="Logo" class="w-full h-full object-cover rounded-full">
            </div>
            <h2 class="font-serif text-3xl sm:text-4xl text-white italic">Rider Portal</h2>
            <p class="text-[#CA8A4B] text-[9px] tracking-[0.4em] uppercase mt-2 font-bold">Delivery Fleet Access</p>
        </div>

        <?php if($msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] p-4 rounded-2xl mb-6 text-center uppercase tracking-widest font-bold">
                <?=$msg?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div class="relative group">
                <i data-lucide="mail" class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500 group-focus-within:text-[#CA8A4B] transition-colors"></i>
                <input type="email" name="email" placeholder="RIDER EMAIL" required 
                    class="w-full bg-black/40 border border-white/5 rounded-2xl py-4.5 py-5 pl-14 text-sm text-white outline-none focus:border-[#CA8A4B] transition-all uppercase tracking-widest">
            </div>
            
            <div class="relative group">
                <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500 group-focus-within:text-[#CA8A4B] transition-colors"></i>
                <input type="password" id="login_pass" name="password" placeholder="PASSWORD" required 
                    class="w-full bg-black/40 border border-white/5 rounded-2xl py-4.5 py-5 pl-14 pr-14 text-sm text-white outline-none focus:border-[#CA8A4B] transition-all tracking-widest">
                <button type="button" onclick="togglePass('login_pass', 'eye_login')" 
                    class="absolute right-5 top-1/2 -translate-y-1/2 text-stone-500 hover:text-white transition-colors">
                    <i id="eye_login" data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>

            <button name="login" class="w-full py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold tracking-[0.3em] text-[11px] uppercase hover:bg-[#b07840] active:scale-[0.98] transition-all shadow-xl shadow-[#CA8A4B]/20 mt-2">
                Enter Dashboard
            </button>
        </form>

        <div class="mt-10 flex flex-col items-center gap-4">
            <p class="text-stone-600 text-[8px] tracking-[0.3em] uppercase">
                Official Logistics Access Only
            </p>
            <a href="../index.php" class="text-[#CA8A4B]/50 hover:text-[#CA8A4B] text-[9px] uppercase tracking-widest transition-colors font-bold">
                Return to Shop
            </a>
        </div>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = "password";
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
        lucide.createIcons();
    </script>
</body>
</html>