<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }

$id = $_GET['id'];
$product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$id]);
$p = $product->fetch();

if (!$p) { header("Location: products.php"); exit; }

if (isset($_POST['update'])) {
    try {
        $name = $_POST['name'];
        $sub_category_id = $_POST['sub_category_id'];
        $price = $_POST['price'];
        $desc = $_POST['description'];
        $stock = $_POST['stock'];
        $has_iced = isset($_POST['has_iced']) ? 1 : 0;
        $has_hot = isset($_POST['has_hot']) ? 1 : 0;

        function handleUpdateImage($file_key, $existing_path) {
            if (!empty($_FILES[$file_key]["name"])) {
                $target_dir = "../src/products/";
                $file_name = time() . "_" . $file_key . "_" . basename($_FILES[$file_key]["name"]);
                if (move_uploaded_file($_FILES[$file_key]["tmp_name"], $target_dir . $file_name)) {
                    // Delete old image if it exists to save space
                    if(!empty($existing_path) && file_exists("../".$existing_path)) {
                        unlink("../".$existing_path);
                    }
                    return "src/products/" . $file_name;
                }
            }
            return $existing_path;
        }

        $main_img = handleUpdateImage("product_image", $p['image_url']);
        $iced_img = handleUpdateImage("iced_image", $p['image_url_iced']);
        $hot_img  = handleUpdateImage("hot_image", $p['image_url_hot']);

        $stmt = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ?");
        $stmt->execute([$sub_category_id]);
        $sub_name = $stmt->fetchColumn();

        $sql = "UPDATE products SET 
                name=?, sub_category_id=?, category=?, price=?, description=?, 
                stock=?, has_iced=?, has_hot=?, 
                image_url=?, image_url_iced=?, image_url_hot=? 
                WHERE id=?";
        
        $pdo->prepare($sql)->execute([
            $name, $sub_category_id, $sub_name, $price, $desc, 
            $stock, $has_iced, $has_hot, 
            $main_img, $iced_img, $hot_img, $id
        ]);

        header("Location: products.php?updated=1");
        exit;

    } catch (Exception $e) {
        // Redirect with error if update fails
        header("Location: products.php?error=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Brew | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #1a0f0a; background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); background-blend-mode: soft-light; }
        .glass-dark { background: rgba(44, 26, 18, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(202, 138, 75, 0.1); }
        .custom-checkbox:checked + div { border-color: #CA8A4B; background: rgba(202, 138, 75, 0.2); }
    </style>
</head>
<body class="text-stone-200 min-h-screen p-8">
    <div class="max-w-3xl mx-auto">
        <a href="products.php" class="text-[#CA8A4B] text-[10px] font-bold uppercase tracking-widest mb-8 inline-block hover:translate-x-[-5px] transition-transform">← Cancel Edit</a>
        
        <div class="glass-dark p-10 rounded-[3rem] border-white/5">
            <h2 class="font-serif text-4xl text-white mb-8 italic">Modify Roast</h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Product Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Placement</label>
                        <select name="sub_category_id" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                            <?php
                            $primaries = $pdo->query("SELECT * FROM primary_categories ORDER BY name ASC")->fetchAll();
                            foreach ($primaries as $pc):
                                echo "<optgroup label='{$pc['name']}'>";
                                $subs = $pdo->prepare("SELECT * FROM sub_categories WHERE primary_id = ?");
                                $subs->execute([$pc['id']]);
                                foreach ($subs->fetchAll() as $sc):
                                    $selected = ($sc['id'] == $p['sub_category_id']) ? 'selected' : '';
                                    echo "<option value='{$sc['id']}' {$selected}>{$sc['name']}</option>";
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
                         <input type="number" step="0.01" name="price" value="<?= $p['price'] ?>" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Adjust Stock</label>
                        <input type="number" name="stock" value="<?= $p['stock'] ?>" required class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]">
                    </div>
                </div>

                <div class="bg-black/40 border border-white/5 rounded-3xl p-6 flex items-center gap-6">
                    <img src="../<?= $p['image_url'] ?>" class="w-20 h-20 rounded-xl object-cover border border-white/10 shadow-xl">
                    <div class="flex-1">
                        <p class="text-[#CA8A4B] text-[10px] font-bold uppercase tracking-widest mb-2">Change Main Image</p>
                        <input type="file" name="product_image" class="text-xs text-stone-500">
                    </div>
                </div>

               <div class="grid grid-cols-2 gap-6">
    <div class="space-y-4">
        <label class="relative cursor-pointer">
            <input type="checkbox" name="has_iced" <?= $p['has_iced'] ? 'checked' : '' ?> class="hidden custom-checkbox" onchange="toggleMode('iced_upload', this)">
            <div class="border border-white/10 rounded-2xl p-4 text-center transition-all hover:border-white/20">
                <span class="text-[10px] font-bold uppercase tracking-widest">Iced Enabled</span>
            </div>
        </label>
        <div id="iced_upload" class="bg-black/20 border border-white/5 rounded-2xl p-4 <?= $p['has_iced'] ? '' : 'opacity-30 pointer-events-none' ?>">
            <?php if(!empty($p['image_url_iced'])): ?>
                <img src="../<?= $p['image_url_iced'] ?>" class="w-10 h-10 rounded mb-2 object-cover opacity-50">
            <?php endif; ?>
            <input type="file" name="iced_image" class="text-[9px] text-stone-500">
        </div>
    </div>

    <div class="space-y-4">
        <label class="relative cursor-pointer">
            <input type="checkbox" name="has_hot" <?= $p['has_hot'] ? 'checked' : '' ?> class="hidden custom-checkbox" onchange="toggleMode('hot_upload', this)">
            <div class="border border-white/10 rounded-2xl p-4 text-center transition-all hover:border-white/20">
                <span class="text-[10px] font-bold uppercase tracking-widest">Hot Enabled</span>
            </div>
        </label>
        <div id="hot_upload" class="bg-black/20 border border-white/5 rounded-2xl p-4 <?= $p['has_hot'] ? '' : 'opacity-30 pointer-events-none' ?>">
            <?php if(!empty($p['image_url_hot'])): ?>
                <img src="../<?= $p['image_url_hot'] ?>" class="w-10 h-10 rounded mb-2 object-cover opacity-50">
            <?php endif; ?>
            <input type="file" name="hot_image" class="text-[9px] text-stone-500">
        </div>
    </div>
</div>

                <div class="space-y-2">
                    <label class="text-[9px] uppercase tracking-[0.2em] text-stone-500 ml-2">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-black/40 border border-white/5 rounded-2xl p-5 text-sm text-white outline-none focus:border-[#CA8A4B]"><?= htmlspecialchars($p['description']) ?></textarea>
                </div>
                
                <button type="submit" name="update" class="w-full py-6 bg-[#CA8A4B] text-white rounded-2xl font-bold uppercase tracking-[0.2em] text-[11px] shadow-xl">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMode(containerId, checkbox) {
            const container = document.getElementById(containerId);
            if (checkbox.checked) {
                container.classList.remove('opacity-30', 'pointer-events-none');
            } else {
                container.classList.add('opacity-30', 'pointer-events-none');
            }
        }
    </script>
</body>
</html>