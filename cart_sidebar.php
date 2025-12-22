<div id="cartSidebar" class="fixed inset-y-0 right-0 z-[120] w-full max-w-md bg-coffee-900 shadow-2xl transform translate-x-full transition-transform duration-500 ease-in-out border-l border-white/10">
    <div class="h-full flex flex-col glass-dark backdrop-blur-3xl">
        <div class="p-8 border-b border-white/5 flex justify-between items-center">
            <div>
                <h3 class="font-serif text-2xl text-white italic">Basket</h3>
                <p class="text-[9px] text-[#CA8A4B] tracking-[0.3em] uppercase font-bold mt-1">Ready to Brew</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="clearCart()" class="text-[9px] text-stone-500 hover:text-red-400 uppercase tracking-widest font-bold transition">
                    Clear
                </button>
                <button onclick="toggleCart()" class="p-2 rounded-full hover:bg-white/5 text-stone-400 hover:text-white transition">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <div id="sidebarContent" class="flex-1 overflow-y-auto p-8 space-y-6">
            </div>

        <div class="p-8 border-t border-white/5 bg-black/40">
            <div class="flex justify-between items-center mb-6">
                <span class="text-stone-400 text-[10px] uppercase tracking-[0.2em]">Estimated Total</span>
                <span id="sidebarTotal" class="text-2xl font-serif text-white italic">₱0.00</span>
            </div>
            <a href="cart.php" class="block w-full py-5 bg-[#CA8A4B] text-white rounded-2xl font-bold text-[10px] tracking-[0.3em] uppercase text-center hover:bg-[#b07840] transition shadow-lg shadow-[#CA8A4B]/20">
                Proceed to Checkout
            </a>
        </div>
    </div>
</div>

<div id="cartOverlay" onclick="toggleCart()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[115] hidden opacity-0 transition-all duration-500"></div>

<script>
async function updateSidebar() {
    try {
        const res = await fetch('get_cart_json.php');
        const data = await res.json();
        
        const container = document.getElementById('sidebarContent');
        const totalEl = document.getElementById('sidebarTotal');
        
        // Update the Navbar Badge using the function in navbar.php
        if (typeof refreshNavCount === "function") {
            refreshNavCount(data.count); // data.count is unique items from your JSON
        }

        totalEl.innerText = `₱${data.total.toLocaleString(undefined, {minimumFractionDigits: 2})}`;

        if (data.items.length === 0) {
            container.innerHTML = `
                <div class="text-center py-20">
                    <div class="w-16 h-16 bg-stone-900/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                        <i data-lucide="coffee" class="w-6 h-6 text-stone-700"></i>
                    </div>
                    <p class="text-stone-500 italic text-sm font-serif">Your basket is empty</p>
                </div>`;
        } else {
            container.innerHTML = data.items.map(item => `
                <div class="group relative bg-white/5 rounded-2xl p-5 border border-white/5 transition-all hover:border-[#CA8A4B]/30">
                    <div id="side-view-${item.id}" class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-stone-800 rounded-lg flex items-center justify-center text-[#CA8A4B]">
                                <i data-lucide="coffee" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-white text-sm font-bold">${item.name}</h4>
                                <p class="text-[9px] text-stone-500 uppercase tracking-widest">${item.temp} • Qty: ${item.qty}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="toggleSideEdit(${item.id})" class="text-stone-600 hover:text-[#CA8A4B] transition-colors">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <button onclick="quickDelete(${item.id})" class="text-stone-600 hover:text-red-400 transition-colors">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <div id="side-edit-${item.id}" class="hidden">
                        <div class="flex items-center justify-between gap-3 pt-2">
                            <div class="flex items-center gap-2">
                                <select id="temp-${item.id}" class="bg-black text-white text-[10px] p-1.5 rounded-lg border border-white/10 uppercase font-bold outline-none">
                                    <option value="Iced" ${item.temp === 'Iced' ? 'selected' : ''}>Iced</option>
                                    <option value="Hot" ${item.temp === 'Hot' ? 'selected' : ''}>Hot</option>
                                </select>
                                <input type="number" id="qty-${item.id}" value="${item.qty}" min="1" max="99" 
                                       class="w-12 bg-black text-white text-center text-xs p-1.5 rounded-lg border border-white/10 font-bold outline-none">
                            </div>
                            <div class="flex gap-2">
                                <button onclick="toggleSideEdit(${item.id})" class="p-2 text-stone-500 hover:text-white transition">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                                <button onclick="quickUpdate(${item.id})" class="bg-[#CA8A4B] p-2 rounded-lg text-white hover:bg-[#b07840] transition">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        lucide.createIcons();
    } catch (err) {
        console.error("Cart Update Error:", err);
    }
}

// Logic for Clearing the whole basket
/**
 * Clears the whole basket with a SweetAlert2 confirmation and custom GIF
 */
async function clearCart() {
    Swal.fire({
        imageUrl: 'src/images/Caf-marrom.gif', 
        imageWidth: 205,
        imageHeight: 200,
        imageAlt: 'Emptying Basket',
        title: 'Empty Basket?',
        text: "Are you sure you want to remove all items from your brew list?",
        showCancelButton: true,
        confirmButtonColor: '#CA8A4B',
        cancelButtonColor: '#1a0f0a',
        confirmButtonText: 'Yes, clear it!',
        cancelButtonText: 'No, keep them',
        background: '#1a0f0a',
        color: '#ffffff',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
        },
        customClass: {
            popup: 'rounded-[2rem] border border-white/10 glass-dark shadow-2xl',
            // Negative margin-bottom pulls the title UP towards the image
            image: 'mt-2 -mb-8', 
            // Negative margin-top on title reduces the gap even further
            title: '-mt-4 font-serif italic text-3xl' 
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            await fetch('cart.php?clear=1', { 
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            });
            
            updateSidebar();

            Swal.fire({
                title: 'Cleared!',
                text: 'Your basket is now empty.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#1a0f0a',
                color: '#ffffff',
                customClass: {
                    popup: 'rounded-3xl border border-white/5'
                }
            });
        }
    });
}
function toggleSideEdit(id) {
    const view = document.getElementById(`side-view-${id}`);
    const edit = document.getElementById(`side-edit-${id}`);
    view.classList.toggle('hidden');
    edit.classList.toggle('hidden');
}

async function quickUpdate(id) {
    const qty = document.getElementById(`qty-${id}`).value;
    const temp = document.getElementById(`temp-${id}`).value;
    const formData = new FormData();
    formData.append('update_cart', '1');
    formData.append('cart_id', id);
    formData.append('quantity', qty);
    formData.append('temp', temp);

    await fetch('cart.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    updateSidebar();
}

async function quickDelete(id) {
    await fetch(`cart.php?remove=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    updateSidebar();
}

function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');
    const isOpen = !sidebar.classList.contains('translate-x-full');

    if (isOpen) {
        // Closing the sidebar
        sidebar.classList.add('translate-x-full');
        overlay.classList.add('hidden');
        overlay.classList.remove('opacity-100');
        
        // Final sync of the navbar badge when closing
        if (typeof updateSidebar === "function") updateSidebar(); 
    } else {
        // Opening the sidebar
        updateSidebar(); 
        sidebar.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => overlay.classList.add('opacity-100'), 10);
    }
}
</script>