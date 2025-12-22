<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

// --- CATEGORY ACTIONS ---

// Add/Edit Primary Category
if (isset($_POST['save_primary'])) {
    $name = $_POST['cat_name'];
    if (!empty($_POST['primary_id'])) {
        $stmt = $pdo->prepare("UPDATE primary_categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $_POST['primary_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO primary_categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }
    header("Location: products.php?success=1"); exit;
}

// Add/Edit Sub-Category
if (isset($_POST['save_sub'])) {
    $name = $_POST['sub_name'];
    $p_id = $_POST['primary_id'];
    if (!empty($_POST['sub_id'])) {
        $stmt = $pdo->prepare("UPDATE sub_categories SET name = ?, primary_id = ? WHERE id = ?");
        $stmt->execute([$name, $p_id, $_POST['sub_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO sub_categories (name, primary_id) VALUES (?, ?)");
        $stmt->execute([$name, $p_id]);
    }
    header("Location: products.php?success=1"); exit;
}

// Delete Primary Category
if (isset($_GET['delete_primary'])) {
    $id = $_GET['delete_primary'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM sub_categories WHERE primary_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM primary_categories WHERE id = ?")->execute([$id]);
        header("Location: products.php?deleted=1"); exit;
    } else {
        header("Location: products.php?error=primary_not_empty"); exit;
    }
}

// Delete Sub-Category
if (isset($_GET['delete_sub'])) {
    $id = $_GET['delete_sub'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sub_category_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() == 0) {
        $pdo->prepare("DELETE FROM sub_categories WHERE id = ?")->execute([$id]);
        header("Location: products.php?deleted=1"); exit;
    } else {
        header("Location: products.php?error=sub_not_empty"); exit;
    }
}

// Delete Product
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT image_url, image_url_iced, image_url_hot FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $img = $stmt->fetch();
    if ($img) {
        $files_to_delete = [$img['image_url'], $img['image_url_iced'], $img['image_url_hot']];
        foreach ($files_to_delete as $file) {
            if (!empty($file) && file_exists("../" . $file)) { unlink("../" . $file); }
        }
    }
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: products.php?deleted=1"); exit;
}

$filter_id = isset($_GET['filter_cat']) ? $_GET['filter_cat'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Manager | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap');
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; font-family: 'Inter', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .glass-dark { background: rgba(30, 20, 15, 0.6); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); }
        .prime-gradient { background: linear-gradient(135deg, #CA8A4B 0%, #a66d35 100%); }
        .sub-header-line { height: 1px; background: linear-gradient(90deg, rgba(202, 138, 75, 0.3) 0%, rgba(202, 138, 75, 0) 100%); }
        
        /* Custom SweetAlert Styling to match theme */
        .swal2-popup { background: rgba(30, 20, 15, 0.95) !important; backdrop-filter: blur(16px); border: 1px solid rgba(202, 138, 75, 0.2) !important; border-radius: 2rem !important; color: #d6d3d1 !important; }
        .swal2-title { font-family: 'Playfair Display', serif !important; font-style: italic; color: white !important; }
        .swal2-confirm { background-color: #CA8A4B !important; border-radius: 1rem !important; font-size: 0.75rem !important; letter-spacing: 0.1em !important; text-transform: uppercase !important; }
    </style>
</head>
<body class="text-stone-300 min-h-screen p-6 md:p-12">

<script>
    // Check for URL parameters to show SweetAlert notifications
    window.onload = () => {
        const urlParams = new URLSearchParams(window.location.search);
        let title = '';
        let icon = 'success';
        let text = '';
        let trigger = false;

        if (urlParams.has('success')) {
            trigger = true;
            title = 'Saved!';
            text = 'Your changes have been stored in the vault.';
        } else if (urlParams.has('deleted')) {
            trigger = true;
            title = 'Removed';
            text = 'Item has been successfully purged.';
        } else if (urlParams.has('error')) {
            trigger = true;
            icon = 'error';
            title = 'Oops...';
            text = 'Cannot delete a section that still contains active items.';
        }

        if (trigger) {
            Swal.fire({ 
                icon: icon, 
                title: title, 
                text: text, 
                timer: 2000, 
                showConfirmButton: false 
            });

            // THIS IS THE FIX: Remove the parameters from the URL without reloading
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }
    };

    function confirmDelete(url, type) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete this ${type}. This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete it!',
            cancelButtonColor: '#303030',
            confirmButtonColor: '#CA8A4B'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-6 mb-16">
            <div>
                <a href="dashboard.php" class="text-[#CA8A4B] text-[9px] font-bold uppercase tracking-[0.4em] flex items-center gap-2 mb-3 opacity-80 hover:opacity-100 transition">
                    <i data-lucide="chevron-left" class="w-3 h-3"></i> Back to Dashboard
                </a>
                <h1 class="font-serif text-5xl text-white italic">Menu Manager</h1>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 w-full xl:w-auto">
                <div class="relative flex-1 md:flex-none md:w-64">
                    <input type="text" id="productSearch" onkeyup="searchProducts()" placeholder="SEARCH ROASTS..." class="w-full bg-black/40 border border-white/10 text-white text-[10px] font-bold uppercase tracking-widest px-12 py-4 rounded-2xl outline-none focus:border-[#CA8A4B] transition-all">
                    <i data-lucide="search" class="w-4 h-4 absolute left-5 top-1/2 -translate-y-1/2 text-stone-500"></i>
                </div>

                <div class="relative flex-1 md:flex-none">
                    <select onchange="window.location.href='?filter_cat='+this.value" class="w-full bg-black/40 border border-white/10 text-white text-[10px] font-bold uppercase tracking-widest px-6 py-4 rounded-2xl outline-none focus:border-[#CA8A4B] appearance-none cursor-pointer pr-12">
                        <option value="all">All Categories</option>
                        <?php
                        $filter_opts = $pdo->query("SELECT * FROM primary_categories");
                        while($opt = $filter_opts->fetch()) {
                            $selected = ($filter_id == $opt['id']) ? 'selected' : '';
                            echo "<option value='{$opt['id']}' {$selected}>{$opt['name']}</option>";
                        }
                        ?>
                    </select>
                    <i data-lucide="filter" class="w-3 h-3 absolute right-5 top-1/2 -translate-y-1/2 text-stone-500 pointer-events-none"></i>
                </div>

                <button onclick="openPrimaryModal()" class="group flex items-center gap-3 border border-white/10 text-white px-7 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:border-[#CA8A4B]/50 transition-all bg-white/5">
                    <i data-lucide="plus" class="w-4 h-4 text-[#CA8A4B] group-hover:scale-125 transition-transform"></i>
                    Add Primary
                </button>
            </div>
        </header>

        <div class="space-y-20">
            <?php
            $query = "SELECT * FROM primary_categories";
            if($filter_id !== 'all') $query .= " WHERE id = " . intval($filter_id);
            $query .= " ORDER BY id ASC";
            
            $primaries = $pdo->query($query)->fetchAll();
            foreach ($primaries as $p_cat):
            ?>
                <section class="primary-section">
                    <div class="flex justify-between items-center mb-8 border-b border-white/5 pb-4 group">
                        <div class="flex items-center gap-6">
                            <h2 class="text-3xl font-serif text-white italic tracking-wide"><?= htmlspecialchars($p_cat['name']) ?></h2>
                            <div class="flex gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="openPrimaryModal(<?= $p_cat['id'] ?>, '<?= addslashes($p_cat['name']) ?>')" class="text-stone-500 hover:text-white transition-colors"><i data-lucide="edit-2" class="w-4 h-4"></i></button>
                                <button onclick="confirmDelete('?delete_primary=<?= $p_cat['id'] ?>', 'Primary Category')" class="text-stone-500 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                        <button onclick="openSubModal(<?= $p_cat['id'] ?>, '<?= addslashes($p_cat['name']) ?>')" class="flex items-center gap-2 text-stone-500 hover:text-[#CA8A4B] text-[10px] font-bold uppercase tracking-[0.2em] transition-colors">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> New Sub-category
                        </button>
                    </div>

                    <div class="space-y-16 ml-4 md:ml-12 border-l border-white/5 pl-6 md:pl-10">
                        <?php
                        $subs = $pdo->prepare("SELECT * FROM sub_categories WHERE primary_id = ?");
                        $subs->execute([$p_cat['id']]);
                        foreach ($subs->fetchAll() as $s_cat):
                        ?>
                            <div class="relative group/sub sub-section">
                                <div class="flex justify-between items-center mb-8">
                                    <div class="flex-1 flex items-center gap-4">
                                        <h3 class="text-[11px] font-bold text-[#CA8A4B] uppercase tracking-[0.3em] mb-1"><?= htmlspecialchars($s_cat['name']) ?></h3>
                                        <div class="flex gap-3 opacity-0 group-hover/sub:opacity-100 transition-opacity">
                                            <button onclick="openSubModal(<?= $p_cat['id'] ?>, '<?= addslashes($p_cat['name']) ?>', <?= $s_cat['id'] ?>, '<?= addslashes($s_cat['name']) ?>')" class="text-stone-600 hover:text-white transition-colors"><i data-lucide="edit-2" class="w-3 h-3"></i></button>
                                            <button onclick="confirmDelete('?delete_sub=<?= $s_cat['id'] ?>', 'Sub-category')" class="text-stone-600 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                                        </div>
                                        <div class="sub-header-line flex-1"></div>
                                    </div>
                                    <a href="add_product.php?sub_id=<?= $s_cat['id'] ?>" class="ml-6 flex items-center gap-2 bg-white/5 hover:bg-[#CA8A4B] text-white px-5 py-2.5 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border border-white/5">
                                        <i data-lucide="plus" class="w-3 h-3"></i> Add Product
                                    </a>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 product-container">
                                    <?php
                                    $products_stmt = $pdo->prepare("SELECT * FROM products WHERE sub_category_id = ?");
                                    $products_stmt->execute([$s_cat['id']]);
                                    $all_products = $products_stmt->fetchAll();
                                    
                                    foreach ($all_products as $p):
                                        $is_out_of_stock = ($p['stock'] <= 0);
                                    ?>
                                        <div class="product-card glass-dark p-5 rounded-[2.5rem] border-white/5 flex gap-5 items-center group/prod hover:bg-white/[0.07] transition-all duration-500 hover:-translate-y-1">
                                            <div class="relative">
                                                <img src="../<?= $p['image_url'] ?>" class="w-16 h-16 rounded-2xl object-cover bg-black/40 shadow-2xl <?= $is_out_of_stock ? 'grayscale opacity-50' : '' ?>">
                                                <?php if($is_out_of_stock): ?>
                                                    <div class="absolute inset-0 flex items-center justify-center">
                                                        <span class="bg-red-600 text-[7px] text-white font-bold uppercase px-2 py-0.5 rounded-full">Empty</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="product-name text-white text-sm font-bold truncate mb-1"><?= htmlspecialchars($p['name']) ?></h4>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[#CA8A4B] text-xs font-bold">₱<?= number_format($p['price'], 2) ?></span>
                                                    <span class="text-[9px] <?= $is_out_of_stock ? 'text-red-500 font-bold' : 'text-stone-500' ?> uppercase">Stock: <?= $p['stock'] ?></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-3 opacity-0 group-hover/prod:opacity-100 transition-all duration-300">
                                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="text-stone-400 hover:text-white transition-colors"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                                                <button onclick="confirmDelete('?delete=<?= $p['id'] ?>', 'Product')" class="text-stone-600 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="primaryModal" class="fixed inset-0 bg-black/90 backdrop-blur-md hidden z-[110] flex items-center justify-center p-4">
        <div class="glass-dark p-10 rounded-[3rem] max-w-sm w-full border-white/10 shadow-2xl">
            <h3 id="primaryModalTitle" class="font-serif text-3xl text-white mb-2 italic">New Category</h3>
            <p class="text-stone-500 text-[10px] uppercase tracking-widest mb-8">Top-level Menu Section</p>
            <form method="POST">
                <input type="hidden" name="primary_id" id="primaryEditId">
                <input type="text" name="cat_name" id="primaryEditName" placeholder="CATEGORY NAME" required class="w-full bg-black/40 border border-white/10 rounded-2xl p-5 text-sm text-white mb-6 outline-none focus:border-[#CA8A4B] transition-colors uppercase">
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('primaryModal')" class="flex-1 py-4 text-stone-500 text-[10px] font-bold uppercase tracking-widest">Cancel</button>
                    <button type="submit" name="save_primary" class="flex-1 py-4 prime-gradient text-white rounded-2xl text-[10px] font-bold uppercase tracking-widest">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="subModal" class="fixed inset-0 bg-black/90 backdrop-blur-md hidden z-[110] flex items-center justify-center p-4">
        <div class="glass-dark p-10 rounded-[3rem] max-w-sm w-full border-white/10 shadow-2xl">
            <h3 id="subModalTitle" class="font-serif text-3xl text-white mb-2 italic">New Sub-category</h3>
            <p id="primaryRef" class="text-[10px] text-[#CA8A4B] uppercase tracking-widest mb-8 font-bold"></p>
            <form method="POST">
                <input type="hidden" name="primary_id" id="primaryIdInput">
                <input type="hidden" name="sub_id" id="subEditId">
                <input type="text" name="sub_name" id="subEditName" placeholder="SUB-CATEGORY NAME" required class="w-full bg-black/40 border border-white/10 rounded-2xl p-5 text-sm text-white mb-6 outline-none focus:border-[#CA8A4B] transition-colors uppercase">
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('subModal')" class="flex-1 py-4 text-stone-500 text-[10px] font-bold uppercase tracking-widest">Cancel</button>
                    <button type="submit" name="save_sub" class="flex-1 py-4 prime-gradient text-white rounded-2xl text-[10px] font-bold uppercase tracking-widest">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
        
        function openPrimaryModal(id = '', name = '') {
            document.getElementById('primaryEditId').value = id;
            document.getElementById('primaryEditName').value = name;
            document.getElementById('primaryModalTitle').innerText = id ? 'Edit Category' : 'New Category';
            openModal('primaryModal');
        }

        function openSubModal(pId, pName, sId = '', sName = '') {
            document.getElementById('primaryIdInput').value = pId;
            document.getElementById('subEditId').value = sId;
            document.getElementById('subEditName').value = sName;
            document.getElementById('primaryRef').innerText = "Inside: " + pName;
            document.getElementById('subModalTitle').innerText = sId ? 'Edit Sub-category' : 'New Sub-category';
            openModal('subModal');
        }

        function searchProducts() {
            const input = document.getElementById('productSearch').value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            const subSections = document.querySelectorAll('.sub-section');
            const primarySections = document.querySelectorAll('.primary-section');

            productCards.forEach(card => {
                const name = card.querySelector('.product-name').innerText.toLowerCase();
                card.style.display = name.includes(input) ? 'flex' : 'none';
            });

            subSections.forEach(sub => {
                const visibleProducts = sub.querySelectorAll('.product-card[style="display: flex;"]');
                sub.style.display = (input !== "" && visibleProducts.length === 0) ? 'none' : 'block';
            });

            primarySections.forEach(pri => {
                const visibleSubs = pri.querySelectorAll('.sub-section[style="display: block;"]');
                pri.style.display = (input !== "" && visibleSubs.length === 0) ? 'none' : 'block';
            });
        }
        
        lucide.createIcons();
    </script>
</body>
</html>