<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Join with user_addresses to get the address details
$query = "SELECT orders.*, 
          ua.house_no, ua.street, ua.barangay, ua.municipality, ua.label
          FROM orders 
          LEFT JOIN user_addresses ua ON orders.address_id = ua.id 
          WHERE orders.user_id = ? 
          ORDER BY orders.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

/**
 * Helper to get the image of the first item in the order string
 */
function getOrderImage($pdo, $itemString) {
    // Assuming itemString is like "1x Cappuccino, 2x Latte"
    // We grab the first name mentioned
    preg_match('/x\s([^,]+)/', $itemString, $matches);
    if (isset($matches[1])) {
        $name = trim($matches[1]);
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $img = $stmt->fetchColumn();
        return $img ? $img : 'src/images/coffee-cup.png';
    }
    return 'src/images/coffee-cup.png';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
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
        body { 
            background-color: #1a0f0a; 
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png'); 
            background-blend-mode: soft-light; 
            color: #d6d3d1; 
        }
        .glass-dark { 
            background: rgba(44, 26, 18, 0.6); 
            backdrop-filter: blur(12px); 
            border: 1px solid rgba(202, 138, 75, 0.1); 
        }
        .status-pill {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 800;
        }
        .status-pending { background: rgba(202, 138, 75, 0.2); color: #CA8A4B; border: 1px solid rgba(202, 138, 75, 0.3); }
        .status-completed { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
    </style>
</head>
<body class="min-h-screen font-sans">
    
    <?php include 'includes/navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-6 pt-32 pb-20">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
            <div>
                <h2 class="font-serif text-5xl text-white italic">Order History</h2>
                <p class="text-[#CA8A4B] text-[10px] tracking-[0.4em] uppercase font-bold mt-2">Your Island Journey</p>
            </div>
            <button onclick="history.length > 1 ? history.back() : window.location.href='index.php'" class="text-stone-500 hover:text-white text-[10px] tracking-widest font-bold uppercase flex items-center gap-2 transition group">
                <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i> Return to Previous
            </button>
        </div>

        <?php if (count($orders) > 0): ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): 
                    $orderImg = getOrderImage($pdo, $order['items']);
                ?>
                    <div class="glass-dark rounded-[2.5rem] overflow-hidden border border-white/5 hover:border-coffee-accent/30 transition-all duration-500 group">
                        <div class="p-6 md:p-8">
                            <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center">
                                
                                <div class="relative w-full lg:w-32 h-32 flex-shrink-0">
                                    <img src="<?= $orderImg ?>" class="w-full h-full object-cover rounded-2xl opacity-80 group-hover:opacity-100 transition duration-500 border border-white/10" alt="Brew Preview">
                                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-t from-coffee-900/60 to-transparent"></div>
                                </div>

                                <div class="flex-grow space-y-4 w-full">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <span class="status-pill <?= $order['status'] == 'Pending' ? 'status-pending' : 'status-completed' ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                        <span class="text-stone-500 text-[10px] uppercase tracking-widest font-bold">
                                            <?= date('M d, Y • h:i A', strtotime($order['created_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <h3 class="text-white font-serif text-2xl italic mb-1 uppercase tracking-tight">
                                            TXN-<?= strtoupper(substr(md5($order['id']), 0, 8)) ?>
                                        </h3>
                                        <p class="text-stone-400 text-xs flex items-center gap-2">
                                            <i data-lucide="map-pin" class="w-3 h-3 text-[#CA8A4B]"></i>
                                            <?php if ($order['house_no']): ?>
                                                <?= htmlspecialchars("{$order['house_no']} {$order['street']}, {$order['barangay']}, {$order['municipality']}") ?>
                                                <span class="text-[9px] bg-white/5 px-2 py-0.5 rounded ml-2 uppercase tracking-tighter text-coffee-accent"><?= $order['label'] ?></span>
                                            <?php else: ?>
                                                <span class="text-red-400/50 italic">Delivery address unavailable</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div class="bg-black/20 rounded-2xl p-4 border border-white/5">
                                        <p class="text-[9px] text-stone-500 uppercase tracking-widest mb-1 font-bold">Your Selection</p>
                                        <p class="text-sm text-stone-200 italic leading-relaxed">
                                            <?= htmlspecialchars($order['items']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-left lg:text-right flex flex-col justify-between h-full w-full lg:w-auto self-stretch">
                                    <div>
                                        <p class="text-[9px] text-stone-500 uppercase tracking-widest font-bold">Total Investment</p>
                                        <p class="text-3xl font-serif text-white italic">₱<?= number_format($order['total_amount'], 2) ?></p>
                                    </div>
                                    <button class="text-coffee-accent hover:text-white text-[9px] uppercase tracking-widest font-bold flex items-center lg:justify-end gap-2 transition mt-8">
                                        View Details <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="h-1 w-full bg-gradient-to-r from-transparent via-coffee-accent/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-dark p-20 rounded-[4rem] text-center border border-white/5">
                <div class="w-20 h-20 bg-stone-900/50 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/10">
                    <i data-lucide="coffee" class="w-8 h-8 text-stone-700"></i>
                </div>
                <h3 class="font-serif text-2xl text-white italic mb-2">No orders found</h3>
                <p class="text-stone-500 text-sm mb-8">You haven't ordered any island blends yet.</p>
                <a href="index.php" class="inline-block bg-[#CA8A4B] text-white px-10 py-4 rounded-full text-[10px] font-bold uppercase tracking-widest hover:bg-[#b07840] transition shadow-lg shadow-coffee-accent/20">
                    Start Brewing
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>