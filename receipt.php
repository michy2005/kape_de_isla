<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: my_orders.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Order & Address
$query = "SELECT orders.*, 
          ua.house_no, ua.street, ua.barangay, ua.municipality, ua.label
          FROM orders 
          LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
          WHERE orders.id = ? AND orders.user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// Fetch Order Items
$item_query = "SELECT oi.*, p.name 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$item_stmt = $pdo->prepare($item_query);
$item_stmt->execute([$order_id]);
$items = $item_stmt->fetchAll();

// NEW QR CODE LOGIC (Using QuickChart API - More reliable for localhost)
$current_url = "http://" . $_SERVER['HTTP_HOST'] . "/kape_de_isla/index.php";
$qr_code_url = "https://quickchart.io/qr?text=" . urlencode($current_url) . "&size=150&margin=1";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt_#<?= $order['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime&family=Inter:wght@400;600;800&family=Playfair+Display:ital,wght@1,700&display=swap');
        
        body { background-color: #1a0f0a; color: #d6d3d1; font-family: 'Inter', sans-serif; }
        
        .receipt-paper {
            background: #FFFCF9; 
            color: #2C1A12;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            position: relative;
            border-top: 8px solid #CA8A4B;
        }

        .receipt-paper::after {
            content: "";
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 12px;
            background: linear-gradient(-45deg, transparent 6px, #FFFCF9 6px), 
                        linear-gradient(45deg, transparent 6px, #FFFCF9 6px);
            background-size: 12px 12px;
        }

        .mono { font-family: 'Courier Prime', monospace; }
        .serif-italic { font-family: 'Playfair Display', serif; font-style: italic; }
        .coffee-text { color: #2C1A12; }
        .accent-text { color: #CA8A4B; }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; display: block !important; }
            .receipt-paper { 
                box-shadow: none !important; 
                border: 1px solid #eee !important; 
                border-top: 8px solid #CA8A4B !important;
                margin: 0 auto !important; 
                width: 100% !important; 
                max-width: 100% !important;
            }
            .receipt-paper::after { display: none !important; }
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4 md:p-10">

    <div class="no-print w-full max-w-md flex flex-wrap justify-between gap-4 mb-8">
        <a href="my_orders.php" class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 text-stone-500 hover:text-white transition">
            <i data-lucide="arrow-left" class="w-4 h-4 text-coffee-accent"></i> Back
        </a>
        <div class="flex gap-2">
            <button onclick="saveReceiptImage()" class="bg-stone-800 text-white px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 hover:bg-stone-700 transition">
                Save <i data-lucide="download" class="w-4 h-4 text-coffee-accent"></i>
            </button>
            <button onclick="window.print()" class="bg-[#CA8A4B] text-white px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest flex items-center gap-2 hover:bg-[#b07840] shadow-lg shadow-orange-900/20 transition">
                Print <i data-lucide="printer" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div id="receipt-content" class="receipt-paper w-full max-w-md p-8 md:p-12 overflow-hidden">
        
        <div class="flex flex-col items-center mb-8">
            <div class="w-20 h-20 bg-white border-2 border-[#CA8A4B] rounded-full flex items-center justify-center mb-4 p-2 shadow-sm">
                <img src="src/images/coffee-cup.png" alt="Kape de Isla Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="serif-italic text-3xl font-bold coffee-text italic">Kape de Isla</h1>
            <p class="text-[9px] uppercase tracking-[0.4em] accent-text font-bold">The Local Brew • Bantayan Island</p>
            <div class="w-full border-b border-dashed border-stone-300 mt-8"></div>
        </div>

        <div class="flex justify-between items-start mb-10 mono text-[11px]">
            <div class="space-y-1">
                <p class="text-stone-400 uppercase tracking-tighter text-[9px]">Transaction ID</p>
                <p class="font-bold">TXN-<?= date('Ymd', strtotime($order['created_at'])) ?>-<?= $order['id'] ?></p>
                
                <p class="text-stone-400 uppercase tracking-tighter text-[9px] pt-2">Customer Name</p>
                <p class="font-bold uppercase"><?= htmlspecialchars($order['customer_name']) ?></p>
            </div>
            <div class="text-right space-y-1">
                <p class="text-stone-400 uppercase tracking-tighter text-[9px]">Order Date</p>
                <p class="font-bold"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                <p class="font-bold"><?= date('h:i A', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <div class="flex justify-between text-[9px] font-extrabold uppercase text-stone-400 mb-3 tracking-widest">
            <span>Product Description</span>
            <span>Total</span>
        </div>
        
        <div class="space-y-4 mb-8">
            <?php foreach ($items as $item): ?>
            <div class="flex justify-between items-start group">
                <div class="text-[12px]">
                    <p class="font-bold coffee-text uppercase"><?= $item['name'] ?></p>
                    <p class="text-stone-500 text-[10px] mono">
                        <?= $item['quantity'] ?> unit(s) • ₱<?= number_format($item['price_at_purchase'], 2) ?> 
                        <span class="accent-text ml-1">[<?= $item['mode'] ?>]</span>
                    </p>
                </div>
                <span class="text-[13px] font-bold mono coffee-text">₱<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="border-b-2 border-double border-stone-200 mb-6"></div>

        <div class="space-y-2 mb-10">
            <div class="flex justify-between text-xs font-semibold">
                <span class="text-stone-500 uppercase">Subtotal</span>
                <span class="mono coffee-text">₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <div class="flex justify-between text-xs font-semibold">
                <span class="text-stone-500 uppercase">Delivery Fee</span>
                <span class="mono text-green-600">FREE</span>
            </div>
            <div class="flex justify-between items-end pt-4 border-t border-stone-100 mt-4">
                <span class="serif-italic text-lg coffee-text italic font-bold">Total Bill</span>
                <span class="text-2xl font-bold coffee-text">₱<?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>

        <div class="bg-stone-100/50 p-5 rounded-xl border border-stone-200 mb-10">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="truck" class="w-3 h-3 accent-text"></i>
                <p class="text-[9px] uppercase font-black accent-text tracking-widest">Delivery To</p>
            </div>
            <p class="text-[11px] font-semibold text-stone-700 leading-relaxed uppercase">
                <?= htmlspecialchars("{$order['house_no']} {$order['street']}, {$order['barangay']}") ?><br>
                <?= htmlspecialchars($order['municipality']) ?> • <span class="accent-text"><?= $order['label'] ?></span>
            </p>
        </div>

        <div class="flex flex-col items-center text-center">
            <div class="p-2 bg-white border border-stone-200 rounded-lg mb-4 inline-block">
                <img src="<?= $qr_code_url ?>" alt="Store QR Link" class="w-24 h-24 block" crossorigin="anonymous">
            </div>
            <p class="text-[10px] text-stone-400 font-bold uppercase tracking-widest mb-1">Scan to order again</p>
            <p class="text-[9px] text-stone-300 italic mb-6 leading-relaxed">This is an electronically generated receipt.<br>No signature required.</p>
            
            <div class="flex items-center gap-2 opacity-30">
                <div class="w-8 h-[1px] bg-stone-400"></div>
                <i data-lucide="coffee" class="w-3 h-3 text-stone-400"></i>
                <div class="w-8 h-[1px] bg-stone-400"></div>
            </div>
        </div>

    </div>

    <p class="no-print mt-10 text-stone-600 text-[10px] uppercase tracking-[0.5em]">Kape de Isla &copy; 2025</p>

    <script>
        lucide.createIcons();

        function saveReceiptImage() {
            const element = document.getElementById('receipt-content');
            
            // Generate the image
            html2canvas(element, {
                useCORS: true, // Allows capturing the external QR code
                scale: 2, // Higher quality
                backgroundColor: "#FFFCF9"
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'KapeDeIsla_Receipt_<?= $order['id'] ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>