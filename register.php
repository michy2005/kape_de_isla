<?php
include 'db.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $mname = $_POST['middle_name'] ?? null;
    $nname = $_POST['nickname'];
    $email = $_POST['email'];
    $pass  = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validation
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nickname = ?");
    $check->execute([$email, $nname]);
    
    if ($check->rowCount() > 0) {
        $error = "Email or Nickname already taken.";
    } elseif ($pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, middle_name, nickname, email, password) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$fname, $lname, $mname, $nname, $email, $hashed_pass])) {
            header("Location: login.php?registered=true");
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join the Island | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.4); backdrop-filter: blur(20px); border: 1px solid rgba(202, 138, 75, 0.2); }
        .logo-glass { background: rgba(202, 138, 75, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(202, 138, 75, 0.3); box-shadow: 0 0 25px rgba(202, 138, 75, 0.2); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-stone-200">
    <div class="max-w-xl w-full glass-dark p-12 rounded-[3.5rem] shadow-2xl relative">
        <div class="flex flex-col items-center mb-8">
            <div class="w-32 h-32 logo-glass rounded-full flex items-center justify-center mb-6 p-1 overflow-hidden">
                <img src="src/images/logo3.png" alt="Logo" class="w-full h-full object-cover rounded-full">
            </div>
            <h2 class="text-4xl font-serif italic text-white text-center">Join the Island</h2>
            <p class="text-stone-500 text-[10px] uppercase tracking-[0.3em] text-center mt-2 font-bold">Start your artisanal journey</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-xl mb-6 text-xs text-center uppercase tracking-widest font-bold"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <input type="text" name="first_name" placeholder="FIRST NAME" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
                <input type="text" name="last_name" placeholder="LAST NAME" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <input type="text" name="middle_name" placeholder="MIDDLE NAME (OPTIONAL)" class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
                <input type="text" name="nickname" placeholder="NICKNAME / @USERNAME" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
            </div>
            <input type="email" name="email" placeholder="EMAIL ADDRESS" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
            
            <div class="relative">
                <input type="password" id="password" name="password" placeholder="CREATE PASSWORD" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
                <button type="button" onclick="togglePass('password', 'eye1')" class="absolute right-4 top-1/2 -translate-y-1/2 text-stone-500 hover:text-white">
                    <i id="eye1" data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="CONFIRM PASSWORD" required class="w-full bg-black/40 border border-white/5 p-4 rounded-2xl outline-none focus:border-[#CA8A4B] text-xs text-white">
                <button type="button" onclick="togglePass('confirm_password', 'eye2')" class="absolute right-4 top-1/2 -translate-y-1/2 text-stone-500 hover:text-white">
                    <i id="eye2" data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
            
            <button type="submit" class="w-full bg-[#CA8A4B] py-5 rounded-2xl font-bold uppercase tracking-[0.3em] text-[10px] text-white mt-4 shadow-xl shadow-[#CA8A4B]/20 hover:bg-[#b07840] transition">Create Account</button>
        </form>
        <p class="mt-8 text-center text-[10px] tracking-widest uppercase text-stone-500">
            Already a member? <a href="login.php" class="text-white hover:text-coffee-accent transition-all font-bold ml-1">Login here</a>
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