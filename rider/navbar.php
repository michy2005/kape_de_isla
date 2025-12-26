<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Premium Spring Animation */
    #smart-nav {
        transition: all 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        transform: translateY(0);
        opacity: 1;
    }
    
    /* Elegant hide state - slides further down with scale effect */
    .nav-hidden {
        transform: translateY(110%) scale(0.95) !important;
        opacity: 0;
        pointer-events: none;
    }

    .nav-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 85px;
        /* Safe area padding for iPhones/Modern Androids */
        padding-bottom: env(safe-area-inset-bottom);
        background: rgba(22, 14, 10, 0.92);
        backdrop-filter: blur(25px) saturate(180%);
        border-top: 1px solid rgba(202, 138, 75, 0.25);
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
        z-index: 9999;
        border-radius: 2.5rem 2.5rem 0 0;
        box-shadow: 0 -10px 40px rgba(0,0,0,0.6);
    }

    /* Active State Glow */
    .nav-active {
        color: #CA8A4B !important;
        opacity: 1 !important;
        text-shadow: 0 0 15px rgba(202, 138, 75, 0.4);
    }
</style>

<nav id="smart-nav" class="nav-footer">
    <a href="dashboard.php" class="nav-item flex flex-col items-center gap-1.5 <?= ($current_page == 'dashboard.php') ? 'nav-active' : '' ?>">
        <i data-lucide="bike" class="w-5 h-5"></i>
        <span class="text-[8px] font-black uppercase tracking-[0.2em]">Transit</span>
    </a>
    
    <a href="history.php" class="nav-item flex flex-col items-center gap-1.5 <?= ($current_page == 'history.php') ? 'nav-active' : '' ?>">
        <i data-lucide="scroll-text" class="w-5 h-5"></i>
        <span class="text-[8px] font-black uppercase tracking-[0.2em]">Log</span>
    </a>
    
    <a href="profile.php" class="nav-item flex flex-col items-center gap-1.5 <?= ($current_page == 'profile.php') ? 'nav-active' : '' ?>">
        <i data-lucide="user-circle-2" class="w-5 h-5"></i>
        <span class="text-[8px] font-black uppercase tracking-[0.2em]">Profile</span>
    </a>
    
    <div class="w-px h-6 bg-white/10"></div>
    
    <a href="logout.php" class="nav-item flex flex-col items-center gap-1.5 text-red-500/50 hover:text-red-500">
        <i data-lucide="power" class="w-5 h-5"></i>
        <span class="text-[8px] font-black uppercase tracking-[0.2em]">Off</span>
    </a>
</nav>

<script>
    let isScrolling;
    const nav = document.getElementById('smart-nav');

    window.addEventListener('scroll', function (event) {
        // Hide immediately
        nav.classList.add('nav-hidden');

        window.clearTimeout(isScrolling);

        isScrolling = setTimeout(function() {
            // Smooth reveal
            nav.classList.remove('nav-hidden');
        }, 250); 
    }, false);
</script>