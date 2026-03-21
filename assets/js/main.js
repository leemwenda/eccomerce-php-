// ========================================
// MAISON DECOR - Main JavaScript
// ========================================

// ===== DARK MODE TOGGLE =====
function initTheme() {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next    = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (!icon) return;
    icon.className = theme === 'dark'
        ? 'fa-solid fa-sun'
        : 'fa-solid fa-moon';
}

// ===== CURRENCY CONVERTER =====
const rates = {
    USD: 1,    KES: 1,  EUR: 0.92, GBP: 0.79,
    TZS: 2500, UGX: 3700, ZAR: 18.5, NGN: 1550,
    GHS: 12,   CAD: 1.36, AUD: 1.53, INR: 83,
    AED: 3.67, CNY: 7.24
};
const symbols = {
    USD: '$',    KES: 'KES ', EUR: 'â‚¬',   GBP: 'Â£',
    TZS: 'TZS ', UGX: 'UGX ', ZAR: 'R',  NGN: 'â‚¦',
    GHS: 'GHâ‚µ',  CAD: 'CA$', AUD: 'AU$', INR: 'â‚¹',
    AED: 'AED ', CNY: 'Â¥'
};

function initCurrency() {
    const saved = localStorage.getItem('currency') || 'KES';
    const select = document.getElementById('currencySelect');
    if (select) select.value = saved;
    if (saved !== 'USD') convertPrices(saved);
}

function convertPrices(currency) {
    localStorage.setItem('currency', currency);
    const rate   = rates[currency]   || 1;
    const symbol = symbols[currency] || '$';
    const isWhole = ['KES','TZS','UGX','NGN','UGX'].includes(currency);

    document.querySelectorAll('[data-price]').forEach(el => {
        const base      = parseFloat(el.getAttribute('data-price'));
        const converted = base * rate;
        const formatted = isWhole
            ? Math.round(converted).toLocaleString()
            : converted.toFixed(2);
        el.textContent = symbol + formatted;
    });
}

// ===== ADD TO CART =====
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;

    const productId = btn.getAttribute('data-add-to-cart');
    const qtyInput  = document.getElementById('quantity');
    const quantity  = qtyInput ? parseInt(qtyInput.value) : 1;

    const originalText = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

    // Detect correct path based on current page
    let apiPath = 'php/api/cart-add.php';
    if (window.location.pathname.includes('/pages/')) {
        apiPath = '../php/api/cart-add.php';
    }

    fetch(apiPath, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Item added to cart!', 'success');
            updateCartBadge(data.cart_count);
        } else {
            showToast(data.message || 'Could not add item', 'error');
        }
    })
    .catch(() => showToast('Something went wrong. Try again.', 'error'))
    .finally(() => {
        btn.disabled  = false;
        btn.innerHTML = originalText;
    });
});

// ===== CART BADGE =====
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent   = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
}

// ===== CART QUANTITY UPDATE =====
function updateCartItem(cartId, quantity) {
    let apiPath = '../php/api/cart-update.php';
    fetch(apiPath, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cart_id: cartId, quantity: quantity })
    }).then(() => location.reload());
}

// ===== CART REMOVE =====
function removeCartItem(cartId) {
    let apiPath = '../php/api/cart-remove.php';
    fetch(apiPath, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ cart_id: cartId })
    }).then(() => location.reload());
}

// ===== TOAST NOTIFICATION =====
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    const icon = type === 'success'
        ? '<i class="fa-solid fa-circle-check"></i>'
        : '<i class="fa-solid fa-circle-xmark"></i>';

    toast.innerHTML   = icon + ' ' + message;
    toast.className   = 'toast show' + (type === 'error' ? ' error' : '');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => {
        toast.className = 'toast';
    }, 3500);
}

// ===== QUANTITY SELECTOR =====
function changeQty(delta, max) {
    const input = document.getElementById('quantity');
    if (!input) return;
    const newVal = parseInt(input.value) + delta;
    if (newVal >= 1 && newVal <= max) input.value = newVal;
}

// ===== SCROLL ANIMATIONS =====
function initScrollAnimations() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
}

// ===== IMAGE PREVIEW =====
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src          = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewImageUrl(url, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    preview.src           = url;
    preview.style.display = url ? 'block' : 'none';
}

// ===== TAB SWITCHER =====
function switchTab(tabId, groupClass) {
    document.querySelectorAll('.' + groupClass + '-content').forEach(el => {
        el.style.display = 'none';
    });
    document.querySelectorAll('.' + groupClass + '-tab').forEach(el => {
        el.classList.remove('active');
    });
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';
    event.target.classList.add('active');
}

// ===== INIT ON PAGE LOAD =====
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initCurrency();
    initScrollAnimations();
});

