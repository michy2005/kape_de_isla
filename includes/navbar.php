<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

// FIXED: Changed SUM(quantity) to COUNT(*) to count unique items
$nav_cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as unique_items FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $nav_cart_count = $row['unique_items'] ?? 0;
}
?>

<nav class="fixed w-full z-50 px-6 md:px-12 py-6 flex justify-between items-center transition-all duration-300 bg-transparent" id="mainNav">
  <a href="index.php" class="flex items-center">
    <i data-lucide="coffee" class="w-8 h-8 text-coffee-accent mr-3"></i>
    <h1 class="font-serif text-2xl tracking-wider text-white">Kape de Isla</h1>
  </a>
  
  <div class="hidden md:flex items-center space-x-8 text-sm font-semibold tracking-widest uppercase">
    <a href="index.php#home" class="nav-link hover:text-coffee-accent transition pb-1">Home</a>
    <a href="index.php#menu" class="nav-link hover:text-coffee-accent transition pb-1">Menu</a>
    <a href="index.php#about" class="nav-link hover:text-coffee-accent transition pb-1">Our Story</a>
    
    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="my_orders.php" class="nav-link hover:text-coffee-accent transition pb-1">My Orders</a>
      <div class="h-4 w-[1px] bg-white/10 mx-2"></div>
      
<a href="profile.php" class="flex items-center gap-2 group">
  <span class="text-sm text-coffee-accent italic lowercase font-bold group-hover:text-white transition-colors">
    <?php
      // Pull the latest nickname for the user
      $nav_stmt = $pdo->prepare("SELECT nickname FROM users WHERE id = ?");
      $nav_stmt->execute([$_SESSION['user_id']]);
      $nav_user = $nav_stmt->fetch();
      echo "@" . htmlspecialchars($nav_user['nickname'] ?? 'User');
    ?>
  </span>
</a>

      <a href="logout.php" class="text-stone-500 hover:text-red-400 transition ml-2"><i data-lucide="log-out" class="w-4 h-4"></i></a>
    <?php else: ?>
      <a href="login.php" class="bg-coffee-accent/10 border border-coffee-accent/20 px-6 py-2 rounded-full hover:bg-coffee-accent hover:text-white transition">Login</a>
    <?php endif; ?>

    <div class="relative pl-4">
      <button onclick="toggleCart()" class="relative cursor-pointer focus:outline-none">
        <i data-lucide="shopping-bag" class="w-5 h-5 inline text-white hover:text-coffee-accent transition"></i>
<span id="cartCount" class="absolute -top-2 -right-2 bg-coffee-accent text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold transition-all duration-300">
    <?= $nav_cart_count ?>
</span>
      </button>
    </div>
  </div>
</nav>

<style>
  .nav-link { position: relative; }
  .nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 0;
    background-color: #CA8A4B;
    transition: width 0.3s ease;
  }
  .nav-link:hover::after { width: 100%; }
  
  .nav-scrolled {
    background: rgba(26, 15, 10, 0.9);
    backdrop-filter: blur(20px);
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(202, 138, 75, 0.1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  }
</style>

<script>
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 50) {
      nav.classList.add('nav-scrolled');
    } else {
      nav.classList.remove('nav-scrolled');
    }
  });
  lucide.createIcons();
  // Function to update badge count from anywhere
function refreshNavCount(count) {
    const badge = document.getElementById('cartCount');
    if (badge) {
        badge.innerText = count;
        // Trigger a tiny "pop" animation to show it updated
        badge.classList.add('scale-125');
        setTimeout(() => badge.classList.remove('scale-125'), 200);
    }
}
</script>