<?php 
session_start();
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Story | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { coffee: { 900: '#1a0f0a', 800: '#2C1A12', accent: '#CA8A4B' } },
                    fontFamily: { sans: ['Inter', 'sans-serif'], serif: ['Playfair Display', 'serif'] }
                }
            }
        }
    </script>
    <style>
        body { background-color: #1a0f0a; color: #d6d3d1; }
        .parallax {
            background-image: url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=2000');
            height: 60vh;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .glass-story { background: rgba(26, 15, 10, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(202, 138, 75, 0.1); }
    </style>
</head>
<body class="font-sans overflow-x-hidden">

    <?php include 'includes/navbar.php'; ?>
    <?php include 'cart_sidebar.php'; ?>

    <div class="parallax flex items-center justify-center relative">
        <div class="absolute inset-0 bg-black/50"></div>
        <div class="relative text-center">
            <h1 class="font-serif text-7xl md:text-9xl text-white italic drop-shadow-2xl">The Legend</h1>
            <p class="text-coffee-accent tracking-[0.5em] uppercase text-xs mt-4 font-bold">Rooted in Bantayan Island</p>
        </div>
    </div>

    <main class="max-w-4xl mx-auto px-6 py-24">
        <div class="space-y-16 text-center md:text-left">
            <section>
                <h2 class="font-serif text-4xl text-white mb-8 italic italic">The Island Roots</h2>
                <p class="text-lg leading-loose text-stone-400">
                    Kape de Isla began as a whisper among the coconut trees of Bantayan. We noticed that while the island was rich in beauty, it lacked a truly <span class="text-white font-bold">artisanal coffee experience</span> that locals and travelers could call their own. 
                </p>
            </section>

            <div class="w-full h-px bg-gradient-to-r from-transparent via-coffee-accent/30 to-transparent"></div>

            <section class="flex flex-col md:flex-row gap-12 items-center">
                <div class="md:w-1/2">
                    <h2 class="font-serif text-4xl text-white mb-8 italic">Artisanal Passion</h2>
                    <p class="text-lg leading-loose text-stone-400">
                        Our project is more than a shop—it is a digital community hub. Every bean is selected with the island's spirit in mind: bold, warm, and welcoming. We focus on small-batch roasting to ensure that every cup tells a story of the soil it came from.
                    </p>
                </div>
                <div class="md:w-1/2 grid grid-cols-2 gap-4">
                    <img src="https://images.unsplash.com/photo-1447933630913-bb7991290936?q=80&w=500" class="rounded-2xl grayscale hover:grayscale-0 transition-all duration-700 shadow-2xl" alt="Coffee">
                    <img src="https://images.unsplash.com/photo-1559056199-641a0ac8b55e?q=80&w=500" class="rounded-2xl mt-8 shadow-2xl" alt="Cafe">
                </div>
            </section>

            <section class="glass-story p-12 rounded-[3rem] text-center border-coffee-accent/20">
                <i data-lucide="map-pin" class="w-8 h-8 text-coffee-accent mx-auto mb-6"></i>
                <h3 class="font-serif text-3xl text-white mb-4 italic">Bantayan-Wide Delivery</h3>
                <p class="text-stone-300 italic text-xl">
                    "Hyper-focused delivery across the whole municipality of Bantayan Island. From the port to the hidden shores, your brew arrives fresh."
                </p>
            </section>
        </div>
        
        <div class="mt-24 text-center">
            <a href="index.php#menu" class="bg-coffee-accent text-white px-12 py-5 rounded-full font-bold tracking-widest text-xs hover:bg-[#b07840] transition-all shadow-xl shadow-coffee-accent/20">
                TASTE THE STORY
            </a>
        </div>
    </main>

    <footer class="py-12 text-center opacity-30">
        <p class="text-[10px] tracking-[0.4em] uppercase">Kape de Isla &copy; 2025</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>