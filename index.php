<?php 
session_start();
include 'db.php'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kape de Isla | The Local Brew</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .hover-lift { transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .hover-lift:hover { transform: translateY(-10px); }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .category-btn.active { background-color: #CA8A4B; color: white; border-color: transparent; }
    </style>
</head>
<body class="text-stone-200 min-h-screen font-sans">

    <?php include 'product_info.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'cart_sidebar.php'; ?>

    <section id="home" class="min-h-screen flex flex-col-reverse lg:flex-row items-center justify-center px-6 md:px-12 pt-24 relative overflow-hidden">
        <div class="lg:w-1/2 text-center lg:text-left z-10 mt-12 lg:mt-0">
            <span class="text-coffee-accent text-xs tracking-[0.4em] uppercase mb-4 block font-semibold">Specialty Coffee E-Commerce</span>
            <h2 class="font-serif text-6xl md:text-8xl text-white mb-4 leading-tight drop-shadow-lg italic">Kape de Isla</h2>
            <h3 class="text-2xl md:text-4xl text-coffee-accent font-serif mb-6 italic">The Local Brew</h3>
            <p class="text-stone-400 max-w-lg mx-auto lg:mx-0 mb-10 text-lg leading-relaxed">A personal brand focused on artisanal coffee. Seamless ordering, interactive engagement, and island-wide delivery.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                <a href="#menu" class="inline-block bg-coffee-accent text-white px-10 py-4 rounded-full text-sm font-bold tracking-widest hover:bg-[#b07840] transition-colors duration-300 shadow-lg shadow-coffee-accent/20">BUY NOW</a>
                <a href="story.php" class="inline-block border border-stone-700 text-white px-10 py-4 rounded-full text-sm font-bold tracking-widest hover:bg-white hover:text-black transition-all duration-300">OUR STORY</a>
            </div>
        </div>

        <div class="lg:w-1/2 flex justify-center items-center relative z-0">
            <img src="src/images/coffee-beans.png" class="absolute top-0 right-0 w-32 opacity-50 animate-pulse" alt="Beans">
            <div class="relative w-80 h-80 md:w-[500px] md:h-[500px] animate-float">
                <img src="src/images/coffee-cup.png" alt="Kape de Isla" class="w-full h-full object-contain drop-shadow-[0_35px_35px_rgba(0,0,0,0.6)]">
            </div>
        </div>
    </section>

    <section id="about" class="max-w-7xl mx-auto px-6 md:px-12 py-24 relative z-10">
        <a href="story.php" class="block group">
            <div class="glass-dark rounded-[3rem] p-8 md:p-16 flex flex-col lg:flex-row gap-12 items-center transition-all duration-500 group-hover:border-coffee-accent/40 group-hover:bg-coffee-800/40">
                <div class="lg:w-1/3"><img src="src/images/coffee-beans1.png" class="w-full opacity-40 rotate-12 group-hover:scale-110 transition-transform duration-700" alt="Decor"></div>
                <div class="lg:w-2/3">
                    <h4 class="font-serif text-4xl text-white mb-6 italic group-hover:text-coffee-accent transition-colors">Artisanal Passion</h4>
                    <p class="text-stone-300 leading-loose text-lg mb-6">Kape de Isla is a passion project centered on delivering a premium coffee experience. The heart of the venture is a digital community hub.</p>
                    <p class="text-stone-400 leading-relaxed italic border-l-4 border-coffee-accent pl-6">"Hyper-focused delivery across the whole municipality of Bantayan Island."</p>
                    <span class="inline-block mt-8 text-coffee-accent text-[10px] font-bold tracking-[0.3em] uppercase">Click to Read Our Full Story →</span>
                </div>
            </div>
        </a>
    </section>

    <main id="menu" class="max-w-7xl mx-auto px-6 md:px-12 py-24 relative z-10">
        <div class="flex flex-col items-center mb-12 text-center">
            <span class="text-coffee-accent text-xs tracking-[0.5em] uppercase mb-4">Brewing Bold. Brewing Better.</span>
            <h3 class="font-serif text-5xl text-white">The Island Menu</h3>
            <div class="w-24 h-1 bg-coffee-accent mt-6 rounded-full"></div>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mb-16">
            <button onclick="filterProducts('all')" class="category-btn active px-8 py-3 rounded-full border border-white/10 text-[10px] font-bold uppercase tracking-widest transition-all">All Items</button>
            <?php
            $primaries = $pdo->query("SELECT * FROM primary_categories");
            while($p_cat = $primaries->fetch()):
            ?>
            <button onclick="filterProducts('<?= strtolower(str_replace(' ', '-', $p_cat['name'])) ?>')" class="category-btn px-8 py-3 rounded-full border border-white/10 text-[10px] font-bold uppercase tracking-widest transition-all hover:border-coffee-accent/50 text-stone-400 hover:text-white">
                <?= $p_cat['name'] ?>
            </button>
            <?php endwhile; ?>
        </div>

        <div id="productGrid" class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            if(isset($pdo)) {
                $stmt = $pdo->query("SELECT p.*, c.name as primary_name 
                                     FROM products p 
                                     JOIN sub_categories s ON p.sub_category_id = s.id 
                                     JOIN primary_categories c ON s.primary_id = c.id");
                while ($row = $stmt->fetch()) :
                    $filter_class = strtolower(str_replace(' ', '-', $row['primary_name']));
                    $img_path = $row['image_url']; 
                    // Extended params to include iced/hot availability and specific URLs
                    $params = "'".$row['id']."', '" . addslashes($row['name']) . "', '" . addslashes($row['description']) . "', '₱" . number_format($row['price'], 0) . "', '" . $row['category'] . "', '" . $img_path . "', '".$row['price']."', " . $row['has_iced'] . ", " . $row['has_hot'] . ", '" . $row['image_url_iced'] . "', '" . $row['image_url_hot'] . "'";
            ?>
            <div class="product-card glass-dark rounded-3xl p-6 hover-lift group relative transition-all duration-500" data-category="<?= $filter_class ?>">
                <div class="cursor-pointer" onclick="openModal(<?= $params ?>, false)">
                    <div class="relative h-64 mb-6 rounded-2xl overflow-hidden shadow-2xl bg-stone-900">
                        <img src="<?= $img_path ?>" onerror="this.src='https://images.unsplash.com/photo-1517701604599-bb29b5dd7348?q=80&w=800&auto=format&fit=crop'" class="w-full h-full object-cover group-hover:scale-110 transition duration-700 opacity-90">
                        <div class="absolute inset-0 bg-gradient-to-t from-coffee-900/80 to-transparent"></div>
                        <span class="absolute bottom-4 left-4 text-[10px] font-bold bg-coffee-accent/90 text-white px-3 py-1 rounded-full uppercase tracking-widest"><?=$row['category']?></span>
                    </div>

                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-serif text-2xl text-white mb-1"><?=$row['name']?></h4>
                            <p class="text-stone-400 text-xs leading-relaxed line-clamp-2"><?=$row['description']?></p>
                        </div>
                        <div class="text-right"><span class="text-xl font-serif font-bold text-coffee-accent">₱<?=number_format($row['price'], 0)?></span></div>
                    </div>
                </div>

                <button onclick="openModal(<?= $params ?>, true)" class="w-full py-4 mt-4 bg-coffee-800 border border-coffee-accent/30 rounded-xl text-[10px] tracking-[0.3em] font-bold text-white hover:bg-coffee-accent transition-all flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> ADD TO BASKET
                </button>
            </div>
            <?php endwhile; } ?>
        </div>
    </main>

    <footer class="bg-coffee-900 py-16 text-center relative z-10 border-t border-coffee-800">
        <p class="text-stone-500 text-[10px] tracking-[0.5em] uppercase">&copy; 2025. Locally Grounded in Bantayan.</p>
    </footer>

    <script>
        lucide.createIcons();

        function filterProducts(cat) {
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
                if(btn.innerText.toLowerCase().replace(' ', '-') === cat || (cat === 'all' && btn.innerText === 'ALL ITEMS')) {
                    btn.classList.add('active');
                }
            });

            document.querySelectorAll('.product-card').forEach(card => {
                if(cat === 'all' || card.getAttribute('data-category') === cat) {
                    card.style.display = 'block';
                    setTimeout(() => card.style.opacity = '1', 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        }

        // Updated global vars to hold modal images
        let currentDefaultImg = "";
        let currentIcedImg = "";
        let currentHotImg = "";

        function openModal(id, name, desc, price, category, img, rawPrice, hasIced, hasHot, icedImg, hotImg, isDirectAdd) {
            document.getElementById('formId').value = id;
            document.getElementById('formName').value = name;
            document.getElementById('formPrice').value = rawPrice;
            document.getElementById('formQty').value = 1; 
            document.getElementById('modalTitle').innerText = name;
            document.getElementById('modalDesc').innerText = desc;
            document.getElementById('modalPrice').innerText = price;
            document.getElementById('modalCategory').innerText = category;
            
            // Set up image references
            currentDefaultImg = img;
            currentIcedImg = icedImg && icedImg !== 'NULL' ? icedImg : img;
            currentHotImg = hotImg && hotImg !== 'NULL' ? hotImg : img;
            document.getElementById('modalImg').src = img;

            // Handle Mode Visibility
            const icedOption = document.getElementById('optionIced');
            const hotOption = document.getElementById('optionHot');
            const modeLabel = document.getElementById('modeLabel');

            icedOption.classList.toggle('hidden', !hasIced);
            hotOption.classList.toggle('hidden', !hasHot);
            modeLabel.classList.toggle('hidden', !hasIced && !hasHot);

            // Select first available mode
            if (hasIced) {
                document.querySelector('input[name="temp"][value="Iced"]').checked = true;
                if(icedImg && icedImg !== 'NULL') document.getElementById('modalImg').src = icedImg;
            } else if (hasHot) {
                document.querySelector('input[name="temp"][value="Hot"]').checked = true;
                if(hotImg && hotImg !== 'NULL') document.getElementById('modalImg').src = hotImg;
            }
            
            const btn = document.getElementById('modalSubmitBtn');
            btn.innerText = isDirectAdd ? "Confirm Add" : "Add to Basket";
            
            const modal = document.getElementById('coffeeModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function closeModal() {
            const modal = document.getElementById('coffeeModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>