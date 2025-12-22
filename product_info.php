<style>
@keyframes beanRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(720deg); }
}

.flying-bean {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    color: #CA8A4B;
    filter: drop-shadow(0 0 8px rgba(202, 138, 75, 0.8));
    transition: all 1.2s cubic-bezier(0.19, 1, 0.22, 1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.flying-bean i {
    animation: beanRotate 1.2s ease-in-out infinite;
}

/* Dynamic styling for Radio buttons */
.peer:checked + .mode-iced { border-color: #3b82f6; background-color: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.peer:checked + .mode-hot { border-color: #ef4444; background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; }
</style>

<div id="toast" class="fixed top-12 left-1/2 -translate-x-1/2 z-[110] pointer-events-none transition-all duration-700 ease-out opacity-0 -translate-y-4 scale-95">
    <div class="glass-dark border border-coffee-accent/40 px-8 py-4 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] flex items-center gap-4">
        <div class="w-8 h-8 bg-coffee-accent rounded-full flex items-center justify-center shadow-lg shadow-coffee-accent/20">
            <i data-lucide="check" class="w-4 h-4 text-white"></i>
        </div>
        <div>
            <p class="text-white text-[10px] font-bold tracking-[0.3em] uppercase">Selection Added</p>
            <p class="text-stone-400 text-[9px] uppercase tracking-widest mt-0.5">Brewing your island experience</p>
        </div>
    </div>
</div>

<div id="coffeeModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/90 backdrop-blur-md animate-fade-in">
    <div class="glass-dark max-w-3xl w-full rounded-[2.5rem] overflow-hidden relative border border-coffee-accent/30 shadow-2xl">
        
        <button onclick="closeModal()" class="absolute top-6 right-6 text-stone-400 hover:text-white transition-colors z-20">
            <i data-lucide="x" class="w-8 h-8"></i>
        </button>

        <div class="flex flex-col md:flex-row">
            <div class="md:w-1/2 h-72 md:h-auto bg-stone-900 overflow-hidden relative">
                <img id="modalImg" src="" class="w-full h-full object-cover opacity-70 transition-all duration-500" alt="Coffee">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent to-coffee-900/50"></div>
            </div>

            <div class="md:w-1/2 p-8 md:p-10 flex flex-col justify-center">
                <span id="modalCategory" class="text-coffee-accent text-[10px] tracking-[0.4em] uppercase mb-2 font-bold block"></span>
                <h4 id="modalTitle" class="font-serif text-4xl text-white mb-4 italic leading-tight"></h4>
                <p id="modalDesc" class="text-stone-400 text-sm mb-6 leading-relaxed"></p>
                
                <form id="addToCartForm" class="space-y-6">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="name" id="formName">
                    <input type="hidden" name="price" id="formPrice">

                    <div class="grid grid-cols-2 gap-4">
                        <div id="modeLabel">
                            <label class="text-[10px] tracking-widest uppercase text-stone-500 font-bold mb-3 block">Mode</label>
                            <div class="flex gap-2">
                                <label id="optionIced" class="flex-1 cursor-pointer group">
                                    <input type="radio" name="temp" value="Iced" class="hidden peer" onchange="updateModalImage('Iced')">
                                    <div class="mode-iced text-center py-3 rounded-xl border border-white/10 bg-white/5 transition-all">
                                        <span class="text-[10px] font-bold uppercase">🧊 Iced</span>
                                    </div>
                                </label>
                                <label id="optionHot" class="flex-1 cursor-pointer group">
                                    <input type="radio" name="temp" value="Hot" class="hidden peer" onchange="updateModalImage('Hot')">
                                    <div class="mode-hot text-center py-3 rounded-xl border border-white/10 bg-white/5 transition-all">
                                        <span class="text-[10px] font-bold uppercase">☕ Hot</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] tracking-widest uppercase text-stone-500 font-bold mb-3 block">Quantity</label>
                            <div class="flex items-center bg-black/20 border border-white/10 rounded-xl overflow-hidden h-[46px]">
                                <button type="button" onclick="stepQty(-1)" class="w-12 h-full text-white hover:bg-white/10 transition">-</button>
                                <input type="number" name="quantity" id="formQty" value="1" min="1" class="w-full bg-transparent text-center text-sm font-bold text-white outline-none" readonly>
                                <button type="button" onclick="stepQty(1)" class="w-12 h-full text-white hover:bg-white/10 transition">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-white/10">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-stone-500 uppercase">Subtotal</span>
                            <span id="modalPrice" class="text-2xl font-serif text-coffee-accent italic"></span>
                        </div>
                        <button type="submit" id="modalSubmitBtn" class="bg-coffee-accent text-white px-8 py-4 rounded-xl text-[10px] font-bold tracking-[0.2em] hover:bg-[#b07840] transition-all uppercase shadow-lg shadow-coffee-accent/20">
                             Confirm Add
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function updateModalImage(mode) {
        const imgDisplay = document.getElementById('modalImg');
        imgDisplay.style.opacity = '0';
        setTimeout(() => {
            if (mode === 'Iced') {
                imgDisplay.src = currentIcedImg;
            } else {
                imgDisplay.src = currentHotImg;
            }
            imgDisplay.style.opacity = '0.7';
        }, 200);
    }

    function stepQty(n) {
        const input = document.getElementById('formQty');
        let val = parseInt(input.value) + n;
        if (val < 1) val = 1;
        input.value = val;
    }

    function toggleCart() {
        const sidebar = document.getElementById('cartSidebar');
        const overlay = document.getElementById('cartOverlay');
        const isOpen = !sidebar.classList.contains('translate-x-full');
        if (isOpen) {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden', 'opacity-0');
        } else {
            if (typeof updateSidebar === "function") updateSidebar();
            else refreshSidebar();
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.add('opacity-100'), 10);
        }
    }

    function refreshSidebar() {
        fetch('get_cart_json.php').then(res => res.json()).then(data => {
            const container = document.getElementById('sidebarContent');
            const totalDisplay = document.getElementById('sidebarTotal');
            const cartBadge = document.getElementById('cartCount');
            if(cartBadge) cartBadge.innerText = data.count;
            if(totalDisplay) totalDisplay.innerText = '₱' + data.total.toLocaleString(undefined, {minimumFractionDigits: 2});
            if(data.items.length === 0) {
                container.innerHTML = `<div class="text-center py-20 opacity-20"><i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-4"></i><p class="text-[10px] tracking-widest uppercase">Empty</p></div>`;
            } else {
                container.innerHTML = data.items.map(item => `
                    <div class="group relative bg-white/5 rounded-2xl p-4 border border-white/5 mb-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-stone-800 rounded-lg flex items-center justify-center text-[#CA8A4B]">
                                    <i data-lucide="coffee" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-white text-sm font-bold">${item.name}</h4>
                                    <p class="text-[9px] text-stone-500 uppercase tracking-widest">${item.temp} • Qty: ${item.qty}</p>
                                </div>
                            </div>
                            <p class="text-[#CA8A4B] font-bold text-xs">₱${(item.price * item.qty).toLocaleString()}</p>
                        </div>
                    </div>
                `).join('');
            }
            lucide.createIcons();
        });
    }

    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('modalSubmitBtn');
        const originalText = btn.innerHTML;
        const badge = document.getElementById('cartCount');
        
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>`;
        lucide.createIcons();

        const rect = btn.getBoundingClientRect();
        const flyingBean = document.createElement('div');
        flyingBean.className = 'flying-bean';
        flyingBean.innerHTML = `<i data-lucide="coffee" class="w-8 h-8"></i>`; 
        flyingBean.style.left = rect.left + (rect.width / 2) - 16 + 'px';
        flyingBean.style.top = rect.top + 'px';
        document.body.appendChild(flyingBean);
        lucide.createIcons();

        const cartIcon = document.querySelector('[data-lucide="shopping-bag"]').getBoundingClientRect();
        const formData = new FormData(this);
        formData.append('add_to_cart', 'true');

        fetch('cart.php', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(() => {
            setTimeout(() => {
                flyingBean.style.left = (cartIcon.left) + 'px';
                flyingBean.style.top = (cartIcon.top) + 'px';
                flyingBean.style.transform = 'scale(0.4)';
                flyingBean.style.opacity = '0.7';
            }, 100);

            fetch('get_cart_json.php').then(res => res.json()).then(data => {
                showToast();
                closeModal();
                if(badge) {
                    badge.innerText = data.count;
                    setTimeout(() => {
                        badge.classList.add('scale-150', 'bg-white', 'text-black');
                        if(document.body.contains(flyingBean)) document.body.removeChild(flyingBean);
                        setTimeout(() => badge.classList.remove('scale-150', 'bg-white', 'text-black'), 400);
                    }, 1100); 
                }
                if (typeof updateSidebar === "function") updateSidebar();
            });
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            lucide.createIcons();
        });
    });

    function showToast() {
        const toast = document.getElementById('toast');
        toast.classList.remove('opacity-0', '-translate-y-4', 'scale-95');
        toast.classList.add('opacity-100', 'translate-y-0', 'scale-100');
        setTimeout(() => {
            toast.classList.add('opacity-0', '-translate-y-4', 'scale-95');
            toast.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        }, 2500);
    }
</script>