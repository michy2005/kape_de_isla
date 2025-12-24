<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

// Get the pre-selected sub-category ID from the URL if it exists
$preselected_sub_id = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : 0;

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $sub_category_id = $_POST['sub_category_id']; // Updated to use the ID bridge
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $stock = $_POST['stock'];
    $has_iced = isset($_POST['has_iced']) ? 1 : 0;
    $has_hot = isset($_POST['has_hot']) ? 1 : 0;
    
    $target_dir = "../src/products/";
    
    function uploadProductImage($file_key, $target_dir) {
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != 0) return null;
        $file_name = time() . "_" . $file_key . "_" . basename($_FILES[$file_key]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_file)) {
            return "src/products/" . $file_name;
        }
        return null;
    }

    $main_img = uploadProductImage("product_image", $target_dir);
    $iced_img = uploadProductImage("iced_image", $target_dir);
    $hot_img = uploadProductImage("hot_image", $target_dir);

    $stmt = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ?");
    $stmt->execute([$sub_category_id]);
    $sub_name = $stmt->fetchColumn();

// Corrected SQL to match your DB: image_url_iced and image_url_hot
    $sql = "INSERT INTO products (name, description, price, category, sub_category_id, image_url, stock, has_iced, has_hot, image_url_iced, image_url_hot) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $pdo->prepare($sql)->execute([
        $name, 
        $desc, 
        $price, 
        $sub_name, 
        $sub_category_id, 
        $main_img, 
        $stock, 
        $has_iced, 
        $has_hot, 
        $iced_img,
        $hot_img 
    ]);
    
    header("Location: products.php?success=1"); exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Roast | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .custom-checkbox:checked + div { border-color: #CA8A4B; background: rgba(202, 138, 75, 0.2); }
    </style>
</head>
<body class="text-stone-200 min-h-screen p-8">
    <div class="max-w-3xl mx-auto">
        <a href="products.php" class="text-[#CA8A4B] text-[10px] font-bold uppercase tracking-widest mb-8 inline-block hover:translate-x-[-5px] transition-transform">← Back to Menu Manager</a>
        
        <div class="glass-dark p-10 rounded-[3rem] border-white/5">
            <h2 class="font-serif text-4xl text-white mb-8 italic">Add to Menu</h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Product Name</label>
                        <input type="text" name="name" placeholder="Latte, Muffin, etc." required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Placement (Sub-category)</label>
                        <select name="sub_category_id" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                            <?php
                            $primaries = $pdo->query("SELECT * FROM primary_categories ORDER BY name ASC")->fetchAll();
                            foreach ($primaries as $p):
                                echo "<optgroup label='{$p['name']}'>";
                                $subs = $pdo->prepare("SELECT * FROM sub_categories WHERE primary_id = ? ORDER BY name ASC");
                                $subs->execute([$p['id']]);
                                foreach ($subs->fetchAll() as $s):
                                    $selected = ($s['id'] == $preselected_sub_id) ? 'selected' : '';
                                    echo "<option value='{$s['id']}' {$selected}>{$s['name']}</option>";
                                endforeach;
                                echo "</optgroup>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                         <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Price (PHP)</label>
                         <input type="number" step="0.01" name="price" placeholder="0.00" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Current Stock</label>
                        <input type="number" name="stock" placeholder="Quantity" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                </div>

                <div class="bg-black/40 border-2 border-dashed border-white/10 rounded-2xl p-6 text-center group hover:border-[#CA8A4B]/30 transition-colors">
                    <input type="file" name="product_image" id="imgInput" required class="hidden" onchange="updateLabel(this, 'fileName')">
                    <label for="imgInput" class="cursor-pointer">
                        <p class="text-[#CA8A4B] text-[10px] font-bold uppercase tracking-widest">Upload Primary Display Image</p>
                        <p id="fileName" class="text-stone-500 text-xs mt-1 italic">JPG or PNG preferred</p>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="has_iced" checked class="hidden custom-checkbox" onchange="toggleMode('iced_upload', this)">
                            <div class="border border-white/10 rounded-2xl p-4 text-center transition-all hover:border-white/20">
                                <span class="text-[10px] font-bold uppercase tracking-widest">Enable Iced Mode</span>
                            </div>
                        </label>
                        <div id="iced_upload" class="bg-black/20 border border-white/5 rounded-2xl p-4 transition-opacity">
                            <input type="file" name="iced_image" id="icedInput" class="hidden" onchange="updateLabel(this, 'icedName')">
                            <label for="icedInput" class="cursor-pointer block">
                                <p class="text-[9px] text-stone-500 uppercase tracking-tighter">Iced Version Image (Optional)</p>
                                <p id="icedName" class="text-[#CA8A4B] text-[10px] mt-1 italic truncate">Click to upload</p>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="has_hot" class="hidden custom-checkbox" onchange="toggleMode('hot_upload', this)">
                            <div class="border border-white/10 rounded-2xl p-4 text-center transition-all hover:border-white/20">
                                <span class="text-[10px] font-bold uppercase tracking-widest">Enable Hot Mode</span>
                            </div>
                        </label>
                        <div id="hot_upload" class="bg-black/20 border border-white/5 rounded-2xl p-4 opacity-30 pointer-events-none transition-opacity">
                            <input type="file" name="hot_image" id="hotInput" class="hidden" onchange="updateLabel(this, 'hotName')">
                            <label for="hotInput" class="cursor-pointer block">
                                <p class="text-[9px] text-stone-500 uppercase tracking-tighter">Hot Version Image (Optional)</p>
                                <p id="hotName" class="text-[#CA8A4B] text-[10px] mt-1 italic truncate">Click to upload</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Description / Tasting Notes</label>
                    <textarea name="description" rows="3" class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]"></textarea>
                </div>
                
                <button type="submit" name="submit" class="w-full py-6 bg-[#CA8A4B] text-white rounded-2xl font-bold uppercase tracking-[0.2em] text-[11px] hover:bg-[#b07840] transition-all shadow-xl shadow-[#CA8A4B]/10">Finalize and Add Roast</button>
            </form>
        </div>
    </div>

    <script>
        function updateLabel(input, labelId) {
            if (input.files && input.files[0]) {
                document.getElementById(labelId).innerHTML = input.files[0].name;
                document.getElementById(labelId).classList.remove('text-stone-500');
                document.getElementById(labelId).classList.add('text-white');
            }
        }

        function toggleMode(containerId, checkbox) {
            const container = document.getElementById(containerId);
            if (checkbox.checked) {
                container.classList.remove('opacity-30', 'pointer-events-none');
            } else {
                container.classList.add('opacity-30', 'pointer-events-none');
                container.querySelector('input').value = "";
                const label = container.querySelector('p[id$="Name"]');
                label.innerHTML = "Click to upload";
            }
        }
    </script>
</body>
</html>