// assets/js/pos.js — POS Cart Logic

let cart = {}; // { productId: { id, name, price, qty, stock } }

// ─── Barcode Scanner ───────────────────────────────────────────────────────────
async function scanBarcode(barcode) {
    barcode = barcode.trim();
    if (!barcode) return;

    const input = document.getElementById('barcodeInput');

    try {
        const res = await fetch(`/pos-system/pos/barcode_lookup.php?barcode=${encodeURIComponent(barcode)}`);
        const data = await res.json();

        if (data.success) {
            const p = data.product;
            addToCart(p.id, p.name, parseFloat(p.price), p.quantity);
            input.value = '';
            input.focus();
        } else {
            showToast(data.message, 'danger');
            input.select();
        }
    } catch (err) {
        showToast('Barcode lookup failed', 'danger');
    }
}

// Auto-focus barcode input on page load
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) barcodeInput.focus();
});


function addToCart(id, name, price, stock) {
    if (stock <= 0) return;

    if (cart[id]) {
        if (cart[id].qty >= cart[id].stock) {
            showToast(`Max stock reached for ${name}`, 'warning');
            return;
        }
        cart[id].qty++;
    } else {
        cart[id] = { id, name, price, qty: 1, stock };
    }

    renderCart();
    showToast(`${name} added`, 'success');
}

// ─── Render cart table ─────────────────────────────────────────────────────────
function renderCart() {
    const tbody = document.getElementById('cartBody');
    const ids = Object.keys(cart);

    if (ids.length === 0) {
        tbody.innerHTML = `<tr id="emptyCartRow">
            <td colspan="4" class="text-center text-muted py-4">
                <i class="fas fa-cart-arrow-down fa-2x mb-2 d-block"></i>Cart is empty
            </td></tr>`;
        document.getElementById('cartTotal').textContent = 'GH₵ 0.00';
        document.getElementById('totalItems').textContent = '0';
        document.getElementById('cartCount').textContent = '0';
        document.getElementById('checkoutBtn').disabled = true;
        return;
    }

    let html = '';
    let total = 0;
    let totalItems = 0;

    ids.forEach(id => {
        const item = cart[id];
        const subtotal = item.price * item.qty;
        total += subtotal;
        totalItems += item.qty;

        html += `<tr>
            <td class="small fw-semibold">${escHtml(item.name)}</td>
            <td class="text-center">
                <div class="input-group input-group-sm" style="width:90px">
                    <button class="btn btn-outline-secondary btn-sm" onclick="changeQty(${id}, -1)">−</button>
                    <input type="number" class="form-control text-center p-0" value="${item.qty}"
                           min="1" max="${item.stock}"
                           onchange="setQty(${id}, this.value)" style="width:32px">
                    <button class="btn btn-outline-secondary btn-sm" onclick="changeQty(${id}, 1)">+</button>
                </div>
            </td>
            <td class="text-end small">GH₵ ${subtotal.toFixed(2)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${id})">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    document.getElementById('cartTotal').textContent = `GH₵ ${total.toFixed(2)}`;
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('cartCount').textContent = ids.length;
    document.getElementById('checkoutBtn').disabled = false;
}

// ─── Quantity controls ─────────────────────────────────────────────────────────
function changeQty(id, delta) {
    if (!cart[id]) return;
    const newQty = cart[id].qty + delta;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (newQty > cart[id].stock) { showToast('Not enough stock', 'warning'); return; }
    cart[id].qty = newQty;
    renderCart();
}

function setQty(id, val) {
    val = parseInt(val);
    if (!cart[id] || isNaN(val) || val <= 0) { removeFromCart(id); return; }
    if (val > cart[id].stock) { val = cart[id].stock; showToast('Not enough stock', 'warning'); }
    cart[id].qty = val;
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    renderCart();
}

function clearCart() {
    cart = {};
    renderCart();
}

// ─── Product search/filter ─────────────────────────────────────────────────────
function filterProducts(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.product-card-wrap').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

// ─── Checkout modal ────────────────────────────────────────────────────────────
function openCheckout() {
    if (Object.keys(cart).length === 0) return;

    let summaryHtml = `<table class="table table-sm">
        <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>`;
    let total = 0;

    Object.values(cart).forEach(item => {
        const sub = item.price * item.qty;
        total += sub;
        summaryHtml += `<tr>
            <td>${escHtml(item.name)}</td>
            <td class="text-center">${item.qty}</td>
            <td class="text-end">GH₵ ${item.price.toFixed(2)}</td>
            <td class="text-end">GH₵ ${sub.toFixed(2)}</td>
        </tr>`;
    });

    summaryHtml += `</tbody>
        <tfoot>
            <tr class="fw-bold fs-5">
                <td colspan="3" class="text-end">TOTAL:</td>
                <td class="text-end text-success">GH₵ ${total.toFixed(2)}</td>
            </tr>
        </tfoot>
    </table>`;

    document.getElementById('checkoutSummary').innerHTML = summaryHtml;
    document.getElementById('amountTendered').value = '';
    document.getElementById('changeDisplay').textContent = 'GH₵ 0.00';
    document.getElementById('changeDisplay').classList.remove('text-danger');
    document.getElementById('changeDisplay').classList.add('text-success');

    // Reset payment method to cash
    selectPayment('cash');

    new bootstrap.Modal(document.getElementById('checkoutModal')).show();
}

// ─── Payment method selector ───────────────────────────────────────────────────
function selectPayment(method) {
    // Update buttons
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.classList.remove('active', 'btn-success', 'btn-warning', 'btn-primary');
        const m = btn.dataset.method;
        btn.classList.add(m === 'cash' ? 'btn-outline-success' : m === 'momo' ? 'btn-outline-warning' : 'btn-outline-primary');
    });

    const active = document.querySelector(`.payment-btn[data-method="${method}"]`);
    if (active) {
        active.classList.remove(`btn-outline-success`, 'btn-outline-warning', 'btn-outline-primary');
        active.classList.add(method === 'cash' ? 'btn-success' : method === 'momo' ? 'btn-warning' : 'btn-primary', 'active');
    }

    // Show/hide fields
    document.getElementById('cashFields').classList.toggle('d-none', method !== 'cash');
    document.getElementById('momoFields').classList.toggle('d-none', method !== 'momo');
    document.getElementById('cardFields').classList.toggle('d-none', method !== 'card');

    window.selectedPaymentMethod = method;
}


function calcChange() {
    const total = getTotal();
    const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    const change = tendered - total;
    const el = document.getElementById('changeDisplay');
    el.textContent = `GH₵ ${Math.abs(change).toFixed(2)}`;
    if (change < 0) {
        el.classList.replace('text-success', 'text-danger');
        el.textContent = `−GH₵ ${Math.abs(change).toFixed(2)}`;
    } else {
        el.classList.replace('text-danger', 'text-success');
    }
}

function getTotal() {
    return Object.values(cart).reduce((sum, item) => sum + (item.price * item.qty), 0);
}

// ─── Complete sale (AJAX) ──────────────────────────────────────────────────────
async function completeSale() {
    const total = getTotal();
    const method = window.selectedPaymentMethod || 'cash';

    // Validate per payment method
    if (method === 'cash') {
        const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
        if (tendered < total) {
            showToast('Amount tendered is less than total!', 'danger');
            return;
        }
    }

    let reference = '';
    if (method === 'momo') {
        reference = document.getElementById('momoPhone').value.trim();
        if (!reference) { showToast('Please enter MoMo phone number!', 'warning'); return; }
    }
    if (method === 'card') {
        reference = document.getElementById('cardRef').value.trim();
        if (!reference) { showToast('Please enter card reference!', 'warning'); return; }
    }

    const tendered = method === 'cash' ? (parseFloat(document.getElementById('amountTendered').value) || 0) : total;

    const payload = {
        cart: Object.values(cart),
        total: total,
        payment_method: method,
        payment_reference: reference
    };

    try {
        const res = await fetch('/pos-system/pos/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
            showReceipt(data.sale_id, payload.cart, total, tendered, method, reference);
        } else {
            showToast('Error: ' + data.message, 'danger');
        }
    } catch (err) {
        showToast('Network error. Try again.', 'danger');
        console.error(err);
    }
}

// ─── Receipt ───────────────────────────────────────────────────────────────────
function showReceipt(saleId, items, total, tendered, method, reference) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-GH', { dateStyle: 'medium' });
    const timeStr = now.toLocaleTimeString('en-GH', { timeStyle: 'short' });

    let rows = '';
    items.forEach(item => {
        rows += `<tr>
            <td>${escHtml(item.name)}</td>
            <td class="text-center">${item.qty}</td>
            <td class="text-end">GH₵ ${(item.price * item.qty).toFixed(2)}</td>
        </tr>`;
    });

    const change = method === 'cash' ? tendered - total : 0;
    const methodIcons = { cash: '💵', momo: '📱', card: '💳' };
    const methodLabels = { cash: 'Cash', momo: 'Mobile Money', card: 'Card' };

    let paymentRow = `<tr><td colspan="2" class="text-end">Payment:</td>
        <td class="text-end">${methodIcons[method]} ${methodLabels[method]}</td></tr>`;

    if (reference) {
        paymentRow += `<tr><td colspan="2" class="text-end small text-muted">Ref:</td>
            <td class="text-end small text-muted">${escHtml(reference)}</td></tr>`;
    }

    if (method === 'cash') {
        paymentRow += `<tr><td colspan="2" class="text-end">Tendered:</td><td class="text-end">GH₵ ${tendered.toFixed(2)}</td></tr>
        <tr><td colspan="2" class="text-end">Change:</td><td class="text-end">GH₵ ${change.toFixed(2)}</td></tr>`;
    }

    document.getElementById('receiptContent').innerHTML = `
        <div class="text-center mb-3">
            <h4 class="fw-bold">BigVybes Supermarket</h4>
            <div class="small text-muted">Navrongo, Upper East Region, Ghana</div>
            <div class="small text-muted">+233 XX XXX XXXX &bull; bigvybes@email.com</div>
            <div class="small text-muted fst-italic">Quality Products, Great Prices!</div>
            <hr class="my-2">
            <small class="text-muted">Receipt #${saleId} &bull; ${dateStr} ${timeStr}</small>
        </div>
        <table class="table table-sm">
            <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount</th></tr></thead>
            <tbody>${rows}</tbody>
            <tfoot>
                <tr><td colspan="2" class="text-end fw-bold">Total:</td><td class="text-end fw-bold">GH₵ ${total.toFixed(2)}</td></tr>
                ${paymentRow}
            </tfoot>
        </table>
        <p class="text-center text-muted small mt-2">Thank you for shopping at BigVybes Supermarket!</p>`;

    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

function newSale() {
    bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
    cart = {};
    renderCart();
    // Reload page to refresh stock counts
    setTimeout(() => location.reload(), 300);
}

// ─── Utility: HTML escape ──────────────────────────────────────────────────────
function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── Toast notifications ───────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} py-2 px-3 shadow mb-0 small`;
    toast.style.cssText = 'min-width:200px;animation:fadeIn .2s';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation' : 'times'}-circle me-1"></i>${message}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}
