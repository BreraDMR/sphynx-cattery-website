// =============================================
// Sphynx Prague — client script
// Localized via window.APP (set in includes/footer.php): catalog + treats
// rendering, the delivery calculator, reviews, the contact form and the cart
// "add" buttons all read their strings from APP.t so the UI follows the
// active language.
// =============================================

console.log('%c🚀 Script loaded', 'color:#ff9800; font-size:16px;');

const APP = window.APP || { locale: 'en', loggedIn: false, csrf: '', t: {}, treatCategories: {} };
const T = APP.t || {};

function tr(key, fallback) {
    return (T && T[key]) ? T[key] : (fallback || key);
}

// ── Hero greeting (time-of-day) ──────────────────────────────────────────
function renderHeroGreeting() {
    const heroP = document.querySelector('.hero p');
    if (!heroP) return;
    const hour = new Date().getHours();
    let greeting;
    if (hour < 12) greeting = tr('greeting_morning');
    else if (hour < 18) greeting = tr('greeting_day');
    else greeting = tr('greeting_evening');
    heroP.innerHTML = greeting + '<br><strong>' + tr('greeting_price') + '</strong>';
}

// ── Delivery price calculator ────────────────────────────────────────────
window.showDeliveryPrice = function () {
    const select = document.getElementById('deliveryRegion');
    const resultEl = document.getElementById('deliveryPriceResult');
    if (!select || !resultEl) return;

    const basePrice = window.__cheapestCatPrice || 1350;
    const fee = parseInt(select.value, 10) || 0;
    const regionLabel = select.options[select.selectedIndex].text;

    resultEl.textContent = tr('calc_result')
        .replace('{region}', regionLabel)
        .replace('{base}', basePrice)
        .replace('{fee}', fee)
        .replace('{total}', basePrice + fee);
};

// ── Cart helpers ─────────────────────────────────────────────────────────
function updateCartBadge(count) {
    const link = document.querySelector('.cart-link');
    if (!link) return;
    let badge = link.querySelector('.cart-badge');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            link.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

async function addToCart(btn) {
    const itemType = btn.dataset.type;
    const itemId = btn.dataset.id;
    if (!itemType || !itemId) return;

    const original = btn.textContent;
    btn.disabled = true;
    try {
        const res = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', item_type: itemType, item_id: parseInt(itemId, 10), qty: 1, csrf: APP.csrf })
        });
        const data = await res.json();
        if (data.status === 'success') {
            updateCartBadge(data.count);
            btn.textContent = '✓ ' + tr('added');
            setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 1500);
        } else {
            btn.textContent = original;
            btn.disabled = false;
        }
    } catch (err) {
        console.error('cart add failed', err);
        btn.textContent = original;
        btn.disabled = false;
    }
}

// ── Card templates ───────────────────────────────────────────────────────
function buyControl(type, id) {
    if (APP.loggedIn) {
        return `<button class="button add-cart-btn" data-type="${type}" data-id="${id}">${tr('add_to_cart')}</button>`;
    }
    return `<a href="login.php" class="button">${tr('login_to_buy')}</a>`;
}

function catCardHtml(cat) {
    return `
        <article class="cat-card">
            <img src="${cat.photo}" alt="${cat.name}" class="cat-image">
            <h3>${cat.name.toUpperCase()}</h3>
            <p>${cat.age_months} ${tr('months')} • ${tr('purebred')}</p>
            <p class="price price-highlight">${cat.price_eur} €</p>
            <a href="cat.php?slug=${encodeURIComponent(cat.slug)}" class="button">${tr('read_more')}</a>
            ${buyControl('cat', cat.id)}
        </article>`;
}

function treatCardHtml(treat) {
    const cat = (APP.treatCategories && APP.treatCategories[treat.category]) || treat.category;
    const weight = treat.weight_g > 0 ? ` • ${treat.weight_g} ${tr('weight')}` : '';
    return `
        <article class="cat-card">
            <img src="${treat.photo}" alt="${treat.name}" class="cat-image">
            <h3>${treat.name}</h3>
            <p>${cat}${weight}</p>
            <p class="price price-highlight">${treat.price_eur} €</p>
            <a href="treat.php?slug=${encodeURIComponent(treat.slug)}" class="button">${tr('treats_read_more')}</a>
            ${buyControl('treat', treat.id)}
        </article>`;
}

// ── Boot ─────────────────────────────────────────────────────────────────
window.addEventListener('load', () => {
    // The time-of-day greeting replaces the hero subtitle -- only do that on
    // the home page (which has the catalog), not on contacts/about/delivery,
    // where the hero subtitle is real page copy.
    if (document.getElementById('catsContainer')) {
        renderHeroGreeting();
    }

    // Mobile menu
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.querySelector('.nav');
    if (mobileBtn && navMenu) {
        mobileBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const isOpen = navMenu.classList.contains('active');
            mobileBtn.textContent = isOpen ? '✕' : '☰';
            mobileBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    // Add-to-cart (event delegation; works for server- and JS-rendered buttons)
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-cart-btn');
        if (btn) {
            e.preventDefault();
            addToCart(btn);
        }
    });

    // Contact form (api.php)
    const form = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');
    if (form && formMessage) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim() || 'Sphynx request';
            const phone = document.getElementById('phone')?.value.trim() || '';
            const age = document.getElementById('age')?.value.trim() || '';
            const color = document.getElementById('color')?.value.trim() || '';
            const consent = document.getElementById('agreement')?.checked || false;

            formMessage.innerHTML = tr('form_sending');
            formMessage.style.color = '#ff9800';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, phone, age, color, message, consent, time: new Date().toISOString() })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    formMessage.innerHTML = `✅ ${data.message}`;
                    formMessage.style.color = 'green';
                    form.reset();
                } else {
                    formMessage.innerHTML = `❌ ${data.message}`;
                    formMessage.style.color = 'red';
                }
            } catch (err) {
                console.error('fetch error:', err);
                formMessage.innerHTML = tr('form_error_conn');
                formMessage.style.color = 'red';
            }
        });
    }

    // Cats catalog (index.php)
    const catsContainer = document.getElementById('catsContainer');
    if (catsContainer) {
        let allCats = [];
        let activeColor = 'all';

        function renderCats(cats) {
            if (cats.length === 0) {
                catsContainer.innerHTML = `<p>${tr('catalog_empty')}</p>`;
                return;
            }
            catsContainer.innerHTML = cats.map(catCardHtml).join('');
        }

        function applyFilters() {
            const query = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
            const filtered = allCats.filter(cat => {
                const colorMatches = activeColor === 'all' || cat.color === activeColor;
                const queryMatches = !query
                    || cat.name.toLowerCase().includes(query)
                    || cat.color.toLowerCase().includes(query);
                return colorMatches && queryMatches;
            });
            renderCats(filtered);
        }

        fetch('api/cats.php')
            .then(res => res.json())
            .then(data => {
                allCats = data.cats || [];
                renderCats(allCats);
                if (allCats.length > 0) {
                    window.__cheapestCatPrice = Math.min(...allCats.map(c => c.price_eur));
                }
            })
            .catch(err => {
                console.error('cats load failed:', err);
                catsContainer.innerHTML = `<p>${tr('catalog_error')}</p>`;
            });

        document.getElementById('searchInput')?.addEventListener('input', applyFilters);
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeColor = btn.dataset.color;
                applyFilters();
            });
        });
    }

    // Treats catalog (treats.php)
    const treatsContainer = document.getElementById('treatsContainer');
    if (treatsContainer) {
        let allTreats = [];
        let activeCategory = 'all';

        function renderTreats(treats) {
            if (treats.length === 0) {
                treatsContainer.innerHTML = `<p>${tr('treats_empty')}</p>`;
                return;
            }
            treatsContainer.innerHTML = treats.map(treatCardHtml).join('');
        }

        function applyTreatFilters() {
            const query = (document.getElementById('treatSearchInput')?.value || '').trim().toLowerCase();
            const filtered = allTreats.filter(t => {
                const catMatches = activeCategory === 'all' || t.category === activeCategory;
                const queryMatches = !query || t.name.toLowerCase().includes(query);
                return catMatches && queryMatches;
            });
            renderTreats(filtered);
        }

        fetch('api/treats.php')
            .then(res => res.json())
            .then(data => {
                allTreats = data.treats || [];
                renderTreats(allTreats);
            })
            .catch(err => {
                console.error('treats load failed:', err);
                treatsContainer.innerHTML = `<p>${tr('treats_error')}</p>`;
            });

        document.getElementById('treatSearchInput')?.addEventListener('input', applyTreatFilters);
        document.querySelectorAll('.treat-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.treat-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeCategory = btn.dataset.category;
                applyTreatFilters();
            });
        });
    }

    // Prefill contact form with a kitten name (cat.php "ask about this kitten")
    const messageField = document.getElementById('message');
    if (messageField) {
        const catName = new URLSearchParams(window.location.search).get('cat');
        if (catName && !messageField.value) {
            messageField.value = `Interested in "${catName}". `;
        }
    }

    // Reviews (Fetch API)
    const reviewsContainer = document.getElementById('reviewsContainer');
    const loadReviewsBtn = document.getElementById('loadReviewsBtn');
    if (reviewsContainer && loadReviewsBtn) {
        loadReviewsBtn.addEventListener('click', async () => {
            loadReviewsBtn.disabled = true;
            try {
                const res = await fetch('assets/data/reviews.json');
                const reviews = await res.json();
                reviewsContainer.innerHTML = reviews.map(r => `
                    <article class="card testimonial-card">«${r.text}» — ${r.author}, ${r.city}</article>
                `).join('');
                loadReviewsBtn.remove();
            } catch (err) {
                console.error('reviews load failed:', err);
                reviewsContainer.innerHTML = `<p>${tr('catalog_error')}</p>`;
                loadReviewsBtn.disabled = false;
            }
        });
    }
});
