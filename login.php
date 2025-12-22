<?php
session_start();
include 'db.php';
$msg = "";
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name']; 
        header("Location: index.php"); exit;
    } else { $msg = "Access Denied. Please check your credentials."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.4); backdrop-filter: blur(20px); border: 1px solid rgba(202, 138, 75, 0.2); }
        .logo-glass { background: rgba(202, 138, 75, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(202, 138, 75, 0.3); box-shadow: 0 0 25px rgba(202, 138, 75, 0.2); }
    </style>
</head>
<body class="text-stone-200 min-h-screen flex items-center justify-center p-6">
    <div class="glass-dark max-w-md w-full p-12 rounded-[3.5rem] shadow-2xl relative overflow-hidden">
        <div class="flex flex-col items-center mb-10">
            <div class="w-28 h-28 logo-glass rounded-full flex items-center justify-center mb-6 p-1 overflow-hidden">
                <img src="src/images/logo3.png" alt="Logo" class="w-full h-full object-cover rounded-full">
            </div>
            <h2 class="font-serif text-4xl text-white italic">Welcome Back</h2>
            <p class="text-[#CA8A4B] text-[10px] tracking-[0.4em] uppercase mt-2 font-bold">Kape de Isla Member</p>
        </div>

        <?php if($msg): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] p-4 rounded-2xl mb-6 text-center uppercase tracking-widest font-bold"><?=$msg?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="relative group">
                <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500 group-focus-within:text-coffee-accent transition-colors"></i>
                <input type="email" name="email" placeholder="EMAIL ADDRESS" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-4 pl-12 text-sm text-white outline-none focus:border-[#CA8A4B] transition-all">
            </div>
            <div class="relative group">
                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500 group-focus-within:text-coffee-accent transition-colors"></i>
                <input type="password" id="login_pass" name="password" placeholder="PASSWORD" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-4 pl-12 pr-12 text-sm text-white outline-none focus:border-[#CA8A4B] transition-all">
                <button type="button" onclick="togglePass('login_pass', 'eye_login')" class="absolute right-4 top-1/2 -translate-y-1/2 text-stone-500 hover:text-white">
                    <i id="eye_login" data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
            <button name="login" class="w-full py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold tracking-[0.3em] text-[10px] uppercase hover:bg-[#b07840] transition-all shadow-xl shadow-[#CA8A4B]/20">Sign In</button>
        </form>

        <p class="text-center mt-10 text-stone-500 text-[10px] tracking-widest uppercase">
            New to the island? <a href="register.php" class="text-white hover:text-coffee-accent transition-all font-bold ml-1">Create Account</a>
        </p>
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