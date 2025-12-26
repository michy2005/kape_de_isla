<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

/** * AUTO-ARCHIVE LOGIC
 * Automatically marks 'Delivered' orders as 'Archived' if older than 24 hours.
 */
$pdo->query("UPDATE orders SET status = 'Archived' 
             WHERE status = 'Delivered' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Initial stats for first load
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('Delivered', 'Archived')")->fetchColumn() ?: 0;
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pending', 'Brewing')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Kape de Isla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap');

        body {
            background-color: #1a0f0a;
            background-image: url('https://www.transparenttextures.com/patterns/wood-pattern.png');
            background-blend-mode: soft-light;
        }

        .font-serif { font-family: 'Playfair Display', serif; }

        .glass-dark {
            background: rgba(44, 26, 18, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(202, 138, 75, 0.1);
        }

        .stat-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(202, 138, 75, 0.3);
        }

        .badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .badge-Pending { background: rgba(120, 113, 108, 0.1); color: #a8a29e; border: 1px solid rgba(120, 113, 108, 0.2); }
        .badge-Brewing { background: rgba(202, 138, 75, 0.1); color: #CA8A4B; border: 1px solid rgba(202, 138, 75, 0.3); }
        .badge-Out { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
        .badge-Delivered { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }

        .btn-success { background-color: #22c55e !important; transform: scale(1.1); }
        
        .clickable-row { cursor: pointer; }
        
        .modal-animate {
            animation: modalIn 0.3s ease-out;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .btn-gold {
            background: linear-gradient(135deg, #CA8A4B 0%, #8b5e34 100%);
            box-shadow: 0 4px 15px rgba(202, 138, 75, 0.2);
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            filter: brightness(1.2);
            transform: translateY(-1px);
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #CA8A4B; border-radius: 10px; }
    </style>
</head>

<body class="text-stone-200 min-h-screen p-6 md:p-12 font-sans">
    <audio id="orderDing" src="../src/sounds/ding.mp3" preload="auto"></audio>

    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
            <div class="flex items-center">
                <div class="p-3 bg-[#CA8A4B]/10 rounded-2xl mr-4 border border-[#CA8A4B]/20">
                    <i data-lucide="layout-dashboard" class="w-8 h-8 text-[#CA8A4B]"></i>
                </div>
                <div>
                    <h1 class="font-serif text-3xl text-white italic">Executive Overview</h1>
                    <p id="liveClock" class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold">Loading Time...</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="products.php" class="glass-dark px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition flex items-center gap-2">
                    <i data-lucide="coffee" class="w-4 h-4"></i> Manage Menu
                </a>
                <a href="archives.php" class="glass-dark px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition flex items-center gap-2">
                    <i data-lucide="archive" class="w-4 h-4"></i> Sales History
                </a>
                <a href="manage_riders.php" class="glass-dark px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#CA8A4B]/20 transition flex items-center gap-2 border border-[#CA8A4B]/30">
                    <i data-lucide="bike" class="w-4 h-4 text-[#CA8A4B]"></i> Manage Riders
                </a>
                <a href="logout.php" class="bg-red-900/10 text-red-400 px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest border border-red-900/20 hover:bg-red-900/20 transition">
                    Logout 
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-12">
            <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                    <i data-lucide="banknote" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                    <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">Total Revenue</p>
                    <h3 class="text-4xl font-serif text-white" id="stat-revenue">₱<?= number_format($total_revenue, 2) ?></h3>
                </div>
                <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                    <i data-lucide="shopping-cart" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                    <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">Orders Today</p>
                    <h3 class="text-4xl font-serif text-white" id="stat-today"><?= $today_orders ?></h3>
                </div>
                <div class="glass-dark p-8 rounded-[2.5rem] stat-card relative overflow-hidden group">
                    <i data-lucide="flame" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/5 rotate-12 group-hover:text-[#CA8A4B]/10 transition-colors"></i>
                    <p class="text-[10px] tracking-[0.3em] text-stone-500 uppercase font-bold mb-2">In the Kitchen</p>
                    <h3 class="text-4xl font-serif text-white" id="stat-pending"><?= $pending_count ?></h3>
                </div>
            </div>

            <div id="lowStockAlert" class="hidden glass-dark rounded-[2.5rem] border border-red-900/30 overflow-hidden flex flex-col">
                <div class="bg-red-500/10 px-6 py-4 border-b border-red-900/20 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-circle" class="text-red-500 w-4 h-4"></i>
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-white">Critical Inventory</h3>
                    </div>
                    <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                </div>
                <div id="lowStockList" class="p-4 space-y-2 overflow-y-auto max-h-[160px]">
                    </div>
            </div>
        </div>

        <div class="glass-dark rounded-[3rem] overflow-hidden border border-white/5">
            <div class="p-8 border-b border-white/5 flex flex-col lg:flex-row justify-between items-center bg-black/10 gap-4">
                <div class="flex items-center gap-4">
                    <h4 class="font-serif text-xl text-white italic">Live Orders</h4>
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#CA8A4B] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#CA8A4B]"></span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <p class="text-[9px] text-stone-500 uppercase font-bold tracking-widest">Status:</p>
                        <select id="statusFilter" onchange="fetchOrders()" class="bg-[#2c1a12] border border-[#CA8A4B]/30 rounded-xl text-[10px] px-4 py-2 text-[#CA8A4B] uppercase font-bold outline-none cursor-pointer">
                            <option value="All">All Transactions</option>
                            <option value="Pending">Pending Only</option>
                            <option value="Brewing">Brewing Only</option>
                            <option value="Out for Delivery">In Transit</option>
                            <option value="Delivered">Completed</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="text-[9px] text-stone-500 uppercase font-bold tracking-widest">Sort:</p>
                        <select id="sortFilter" onchange="fetchOrders()" class="bg-[#2c1a12] border border-[#CA8A4B]/30 rounded-xl text-[10px] px-4 py-2 text-[#CA8A4B] uppercase font-bold outline-none cursor-pointer">
                            <option value="DESC">Latest First</option>
                            <option value="ASC">Oldest First</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] tracking-[0.2em] text-[#CA8A4B] uppercase bg-black/40">
                            <th class="p-8">Customer & ID</th>
                            <th class="p-8">Ordered Items</th>
                            <th class="p-8">Logistics</th>
                            <th class="p-8 text-center">Amount</th>
                            <th class="p-8">Status</th>
                            <th class="p-8 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody" class="divide-y divide-white/5"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="orderModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="glass-dark w-full max-w-lg rounded-[2.5rem] border-white/10 modal-animate overflow-hidden shadow-2xl">
            <div class="p-8 border-b border-white/5 flex justify-between items-start">
                <div>
                    <h2 class="font-serif text-3xl text-white italic mb-1" id="m-customer">Customer Name</h2>
                    <p class="text-[10px] tracking-widest text-[#CA8A4B] font-bold uppercase" id="m-txn">TXN-000</p>
                </div>
                <button onclick="closeOrderModal()" class="text-stone-500 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <p class="text-[10px] text-stone-500 uppercase font-bold tracking-widest mb-3">Order Summary</p>
                    <div id="m-items" class="space-y-3 bg-black/20 rounded-2xl p-4 border border-white/5"></div>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] text-stone-500 uppercase font-bold tracking-widest mb-2">Delivery Address</p>
                        <p id="m-address" class="text-xs text-stone-300 leading-relaxed"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-stone-500 uppercase font-bold tracking-widest mb-2">Customer Note</p>
                        <p id="m-note" class="text-xs text-[#CA8A4B] italic"></p>
                    </div>
                </div>
                <div class="pt-6 border-t border-white/5 flex justify-between items-end">
                    <div>
                        <p class="text-[10px] text-stone-500 uppercase font-bold tracking-widest mb-1">Total Bill</p>
                        <p id="m-total" class="font-serif text-3xl text-white italic"></p>
                    </div>
                    <div id="m-status-badge"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="riderModal" class="fixed inset-0 bg-black/90 backdrop-blur-md z-[110] hidden items-center justify-center p-4">
        <div class="glass-dark w-full max-w-md rounded-[2.5rem] border-[#CA8A4B]/20 modal-animate overflow-hidden">
            <div class="p-6 border-b border-white/5 flex justify-between items-center">
                <h3 class="font-serif text-xl text-white italic">Assign Delivery Rider</h3>
                <button onclick="closeRiderModal()" class="text-stone-500"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div id="riderListContent" class="p-6 space-y-3 max-h-[400px] overflow-y-auto">
                </div>
        </div>
    </div>

    <script>
        let lastOrderCount = <?= $today_orders ?>;
        let ordersData = [];
        let activeOrderId = null;

        async function fetchOrders() {
            const filter = document.getElementById('statusFilter').value;
            const sort = document.getElementById('sortFilter').value;

            try {
                // IMPORTANT: Your api_fetch_orders.php must now return rider_name in the JSON
                const response = await fetch(`api/api_fetch_orders.php?status=${filter}&sort=${sort}`);
                const data = await response.json();
                ordersData = data.orders;

                if (data.today_count > lastOrderCount) {
                    document.getElementById('orderDing').play().catch(e => console.log("Audio blocked"));
                    lastOrderCount = data.today_count;
                }

                document.getElementById('stat-revenue').innerText = "₱" + parseFloat(data.revenue).toLocaleString(undefined, { minimumFractionDigits: 2 });
                document.getElementById('stat-today').innerText = data.today_count;
                document.getElementById('stat-pending').innerText = data.pending_count;

                const tbody = document.getElementById('ordersTableBody');
                tbody.innerHTML = data.orders.map(o => `
                    <tr class="hover:bg-white/[0.04] transition-colors group clickable-row" onclick="openOrderModal(${o.id}, event)">
                        <td class="p-8">
                            <p class="text-white font-bold tracking-wide group-hover:text-[#CA8A4B] transition-colors">${o.customer_name}</p>
                            <p class="text-stone-500 text-[9px] mt-1 uppercase tracking-tighter">TXN-#${o.id} • ${o.time}</p>
                        </td>
                        <td class="p-8">
                            <div class="max-w-[200px] space-y-1">${o.items_html}</div>
                        </td>
                        <td class="p-8">
                            <div class="flex flex-col gap-2">
                                ${o.rider_name ? `
                                    <div class="flex items-center gap-2 bg-[#CA8A4B]/5 p-2 rounded-xl border border-[#CA8A4B]/20">
                                        <i data-lucide="truck" class="w-3 h-3 text-[#CA8A4B]"></i>
                                        <span class="text-[10px] font-bold text-stone-300 uppercase">${o.rider_name}</span>
                                    </div>
                                ` : (o.status === 'Brewing' || o.status === 'Pending' ? `
                                    <button onclick="openRiderModal(${o.id}, event)" class="btn-gold px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                                        <i data-lucide="user-plus" class="w-3 h-3"></i> Assign Rider
                                    </button>
                                ` : `<span class="text-[9px] text-stone-600 font-bold uppercase italic">N/A</span>`)}
                                
                                ${o.address ? `
                                    <div class="flex items-start gap-2 max-w-[150px]">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-stone-600 mt-0.5 shrink-0"></i>
                                        <p class="text-stone-400 text-[10px] leading-tight line-clamp-1">${o.address}</p>
                                    </div>
                                ` : `<p class="text-red-900/50 text-[9px] font-black uppercase">Pickup</p>`}
                            </div>
                        </td>
                        <td class="p-8 text-center">
                            <span class="text-white font-serif text-lg italic">₱${parseFloat(o.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                        </td>
                        <td class="p-8">
                            <span class="badge badge-${o.status.split(' ')[0]}">${o.status}</span>
                        </td>
                        <td class="p-8 text-right" onclick="event.stopPropagation()">
                            <div class="flex justify-end gap-2">
                                <select id="status-${o.id}" class="bg-black/60 border border-white/10 rounded-xl text-[10px] px-3 py-2 text-white outline-none font-bold tracking-tighter">
                                    <option value="Pending" ${o.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Brewing" ${o.status === 'Brewing' ? 'selected' : ''}>Brewing</option>
                                    <option value="Out for Delivery" ${o.status === 'Out for Delivery' ? 'selected' : ''}>Out</option>
                                    <option value="Delivered" ${o.status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                                </select>
                                <button id="btn-${o.id}" onclick="updateStatus(${o.id})" class="bg-[#CA8A4B] p-2.5 rounded-xl text-white hover:bg-white hover:text-[#CA8A4B] transition-all active:scale-95">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                lucide.createIcons();
            } catch (err) { console.error("Sync Error:", err); }
        }

        async function openRiderModal(orderId, event) {
            event.stopPropagation();
            activeOrderId = orderId;
            const modal = document.getElementById('riderModal');
            const content = document.getElementById('riderListContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            content.innerHTML = '<p class="text-center text-stone-500 py-10 animate-pulse">Checking available riders...</p>';

            try {
                const response = await fetch('api/api_fetch_available_riders.php');
                const riders = await response.json();

                if (riders.length === 0) {
                    content.innerHTML = '<div class="text-center p-8 bg-red-500/5 rounded-3xl border border-red-500/10"><p class="text-red-400 text-xs font-bold uppercase">No Riders Available</p></div>';
                } else {
                    content.innerHTML = riders.map(r => `
                        <div onclick="assignRiderAction(${r.id})" class="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5 hover:border-[#CA8A4B]/50 hover:bg-[#CA8A4B]/5 cursor-pointer transition-all group">
                            <div>
                                <p class="text-sm font-bold text-white">${r.first_name} ${r.last_name}</p>
                                <p class="text-[10px] text-stone-500 uppercase">${r.vehicle_details}</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-stone-700 group-hover:text-[#CA8A4B]"></i>
                        </div>
                    `).join('');
                }
                lucide.createIcons();
            } catch (err) { content.innerHTML = '<p class="text-red-500 text-xs">Error loading riders.</p>'; }
        }

        function closeRiderModal() {
            document.getElementById('riderModal').classList.add('hidden');
            document.getElementById('riderModal').classList.remove('flex');
        }

        async function assignRiderAction(riderId) {
            const formData = new URLSearchParams();
            formData.append('order_id', activeOrderId);
            formData.append('rider_id', riderId);

            const response = await fetch('api/api_assign_rider.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                closeRiderModal();
                fetchOrders();
            }
        }

        function openOrderModal(orderId, event) {
            const order = ordersData.find(o => o.id == orderId);
            if (!order) return;

            document.getElementById('m-customer').innerText = order.customer_name;
            document.getElementById('m-txn').innerText = `TXN-ORD-${order.id}`;
            document.getElementById('m-items').innerHTML = order.items_html;
            document.getElementById('m-address').innerText = order.address || "No delivery address provided.";
            document.getElementById('m-note').innerText = order.note ? `"${order.note}"` : "No instructions.";
            document.getElementById('m-total').innerText = "₱" + parseFloat(order.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 });
            document.getElementById('m-status-badge').innerHTML = `<span class="badge badge-${order.status.split(' ')[0]}">${order.status}</span>`;

            const modal = document.getElementById('orderModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            lucide.createIcons();
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function updateStatus(orderId) {
            const btn = document.getElementById(`btn-${orderId}`);
            const newStatus = document.getElementById(`status-${orderId}`).value;
            const formData = new URLSearchParams();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            const response = await fetch('api/api_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            if (response.ok) {
                btn.classList.add('btn-success');
                btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i>';
                lucide.createIcons();
                setTimeout(() => { fetchOrders(); }, 600);
            }
        }

        function checkStockLive() {
            fetch('./api/check_low_stock.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('lowStockAlert');
                    const list = document.getElementById('lowStockList');
                    
                    if (data.length > 0) {
                        container.classList.remove('hidden');
                        list.innerHTML = data.map(item => `
                            <div class="flex justify-between items-center bg-white/5 p-3 rounded-2xl border border-white/5 group hover:bg-red-500/5 transition-colors">
                                <span class="text-[11px] font-bold text-stone-300 uppercase tracking-tight">${item.name}</span>
                                <div class="flex flex-col items-end">
                                    <span class="text-red-500 text-[10px] font-black">${item.stock}</span>
                                    <span class="text-[7px] text-stone-600 uppercase font-bold">In Stock</span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.classList.add('hidden');
                    }
                    lucide.createIcons();
                });
        }

        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').textContent = now.toLocaleDateString('en-US', options);
        }

        window.onclick = (e) => { 
            if (e.target.id === 'orderModal') closeOrderModal(); 
            if (e.target.id === 'riderModal') closeRiderModal();
        };
        document.addEventListener('keydown', (e) => { if (e.key === "Escape") { closeOrderModal(); closeRiderModal(); } });

        setInterval(updateClock, 1000);
        setInterval(fetchOrders, 5000);
        setInterval(checkStockLive, 5000);
        
        fetchOrders();
        checkStockLive();
        updateClock();
    </script>
</body>
</html>