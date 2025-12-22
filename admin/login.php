<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) { header("Location: dashboard.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'kape123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: dashboard.php");
    } else { $error = "Invalid Credentials"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Access | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
 body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        
        .ultra-glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="ultra-glass w-full max-w-md p-12 rounded-[3rem] text-center">
        <div class="mb-8 flex justify-center">
            <div class="w-20 h-20 bg-[#CA8A4B] rounded-full flex items-center justify-center shadow-lg shadow-[#CA8A4B]/20">
                <img src="../src/images/coffee-cup.png" alt="Logo" class="w-12 h-12 object-contain" onerror="this.style.display='none'">
                <span class="text-white font-serif text-3xl">K</span>
            </div>
        </div>
        
        <h2 class="text-white font-serif text-3xl mb-2 italic">Welcome back</h2>
        <p class="text-stone-500 text-[10px] tracking-[0.4em] uppercase mb-10">Admin Portal</p>
        
        <?php if(isset($error)): ?>
            <div class="bg-red-500/10 border border-red-500/20 py-3 rounded-xl mb-6">
                <p class="text-red-400 text-[10px] uppercase tracking-widest"><?=$error?></p>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="relative">
                <input type="text" name="username" placeholder="Username" required class="w-full bg-white/5 border border-white/10 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]/50 transition-all">
            </div>
            <div class="relative">
                <input type="password" name="password" placeholder="Password" required class="w-full bg-white/5 border border-white/10 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]/50 transition-all">
            </div>
            <button type="submit" class="w-full py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold tracking-widest text-[10px] uppercase hover:bg-[#b07840] hover:-translate-y-1 transition-all duration-300 shadow-xl shadow-[#CA8A4B]/20">
                Sign In
            </button>
        </form>
    </div>
</body>
</html>