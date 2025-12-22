<?php
session_start();
include '../db.php'; // Note the ../ because we are inside the 'auth' folder

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Fetch user current hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // 2. Validation
    if (!password_verify($current_pass, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        // 3. Update
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$hashed, $_SESSION['user_id']]);
        $success = "Password updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; color: #d6d3d1; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .input-field { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 16px; width: 100%; color: white; outline: none; }
        .input-field:focus { border-color: #CA8A4B; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-6">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="../profile.php" class="text-stone-500 hover:text-white text-[10px] tracking-widest uppercase flex items-center justify-center gap-2 mb-4">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Back to Profile
            </a>
            <h2 class="text-3xl font-serif italic text-white">Update Password</h2>
        </div>

        <div class="glass-dark p-8 rounded-[2.5rem] shadow-2xl">
            <?php if($error): ?>
                <div class="mb-6 p-3 bg-red-500/10 border border-red-500/50 text-red-400 text-xs rounded-xl text-center"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="mb-6 p-3 bg-green-500/10 border border-green-500/50 text-green-400 text-xs rounded-xl text-center"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="text-[9px] text-stone-500 uppercase tracking-[0.2em] font-bold block mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="input-field w-full">
                </div>

                <div class="pt-2 border-t border-white/5">
                    <label class="text-[9px] text-stone-500 uppercase tracking-[0.2em] font-bold block mb-2">New Password</label>
                    <input type="password" name="new_password" required class="input-field w-full">
                </div>

                <div>
                    <label class="text-[9px] text-stone-500 uppercase tracking-[0.2em] font-bold block mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="input-field w-full">
                </div>

                <button type="submit" class="w-full py-4 bg-coffee-accent text-white rounded-xl font-bold tracking-widest text-[10px] uppercase hover:bg-[#b07840] transition mt-4 shadow-lg shadow-coffee-accent/20">
                    Update Security
                </button>
            </form>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>