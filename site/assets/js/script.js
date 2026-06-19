// =============================================
// Повний script.js — ЛР7 + ЛР8 + ЛР9 + СРС4 + ЛР10
// =============================================

console.log('%c🚀 Скрипт завантажено успішно', 'color:#ff9800; font-size:16px;');

// Для кнопки з inline onclick в index.html (щоб не було помилки в консолі)
window.showGreeting = function() {
    const heroP = document.querySelector('.hero p');
    if (!heroP) return;
    const hour = new Date().getHours();
    let greeting = "";
    if (hour < 12) greeting = "🌞 Доброго ранку! Шукаєте лисого друга?";
    else if (hour < 18) greeting = "☀️ Доброго дня! Найкращі сфінкси чекають на вас";
    else greeting = "🌙 Доброго вечора! Кошенята готові до переїзду";

    heroP.innerHTML = greeting + '<br><strong>Ціна кошеняти з доставкою від 1350 €</strong>';
};

// Все виконуємо ТІЛЬКИ після повного завантаження сторінки
window.addEventListener('load', () => {

    // ==================== ЛР7 — Привітання ====================
    const heroP = document.querySelector('.hero p');
    if (heroP) {
        let greeting = "";
        const hour = new Date().getHours();
        if (hour < 12) greeting = "🌞 Доброго ранку! Шукаєте лисого друга?";
        else if (hour < 18) greeting = "☀️ Доброго дня! Найкращі сфінкси чекають на вас";
        else greeting = "🌙 Доброго вечора! Кошенята готові до переїзду";
        heroP.innerHTML = greeting + '<br><strong>Ціна кошеняти з доставкою від 1350 €</strong>';
    }

    // ==================== ЛР8 — Мобільне меню ====================
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

    // ==================== ЛР10 — Форма (PHP) ====================
    const form = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (form && formMessage) {
        console.log('✅ Форма contactForm знайдена — ЛР10 активовано');

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            console.log('🚀 Форма відправлена');

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim() || 'Заявка на сфінкса';

            // phone/age/color/consent used to be collected by the form but
            // never actually sent -- api.php now expects (and stores) them.
            const phoneEl = document.getElementById('phone');
            const ageEl = document.getElementById('age');
            const colorEl = document.getElementById('color');
            const agreementEl = document.getElementById('agreement');

            const phone = phoneEl ? phoneEl.value.trim() : '';
            const age = ageEl ? ageEl.value.trim() : '';
            const color = colorEl ? colorEl.value.trim() : '';
            const consent = agreementEl ? agreementEl.checked : false;

            formMessage.innerHTML = '⏳ Надсилаємо заявку...';
            formMessage.style.color = '#ff9800';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, phone, age, color, message, consent, time: new Date().toISOString() })
                });

                const data = await response.json();
                console.log('Відповідь від сервера:', data);

                if (data.status === 'success') {
                    formMessage.innerHTML = `✅ ${data.message}`;
                    formMessage.style.color = 'green';
                    form.reset();
                } else {
                    formMessage.innerHTML = `❌ ${data.message}`;
                    formMessage.style.color = 'red';
                }
            } catch (err) {
                console.error('Помилка fetch:', err);
                formMessage.innerHTML = '❌ Не вдалося підключитися до сервера (api.php)';
                formMessage.style.color = 'red';
            }
        });
    } else {
        console.log('Форма contactForm не знайдена на цій сторінці — це нормально для index.html та about.html');
    }

    // ==================== СРС 4 — Фільтрація кошенят ====================
    // Раніше тут був лише цей коментар-заглушка -- кнопки фільтра й пошук
    // в index.html нічого не робили. Тепер дані тягнуться з
    // assets/data/cats.json і рендеряться/фільтруються нижче.
    const catsContainer = document.getElementById('catsContainer');
    if (catsContainer) {
        let allCats = [];
        let activeColor = 'all';

        function renderCats(cats) {
            if (cats.length === 0) {
                catsContainer.innerHTML = '<p>Кошенят за цим запитом не знайдено.</p>';
                return;
            }
            catsContainer.innerHTML = cats.map(cat => `
                <article class="cat-card">
                    <img src="${cat.image}" alt="${cat.name}" class="cat-image">
                    <h3>${cat.name.toUpperCase()}</h3>
                    <p>${cat.age} • Чиста порода</p>
                    <p class="price price-highlight">${cat.price} €</p>
                    <a href="#" class="button">ДІЗНАТИСЯ БІЛЬШЕ</a>
                </article>
            `).join('');
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

        fetch('assets/data/cats.json')
            .then(res => res.json())
            .then(cats => {
                allCats = cats;
                renderCats(allCats);
            })
            .catch(err => {
                console.error('Не вдалося завантажити cats.json:', err);
                catsContainer.innerHTML = '<p>Не вдалося завантажити список кошенят.</p>';
            });

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeColor = btn.dataset.color;
                applyFilters();
            });
        });
    }

    // ==================== ЛР9 — Відгуки клієнтів (Fetch API) ====================
    const reviewsContainer = document.getElementById('reviewsContainer');
    const loadReviewsBtn = document.getElementById('loadReviewsBtn');
    if (reviewsContainer && loadReviewsBtn) {
        loadReviewsBtn.addEventListener('click', async () => {
            loadReviewsBtn.disabled = true;
            loadReviewsBtn.textContent = 'Завантаження...';
            try {
                const res = await fetch('assets/data/reviews.json');
                const reviews = await res.json();
                reviewsContainer.innerHTML = reviews.map(r => `
                    <article class="card testimonial-card">«${r.text}» — ${r.author}, ${r.city}</article>
                `).join('');
                loadReviewsBtn.remove();
            } catch (err) {
                console.error('Не вдалося завантажити reviews.json:', err);
                reviewsContainer.innerHTML = '<p>Не вдалося завантажити відгуки.</p>';
                loadReviewsBtn.disabled = false;
                loadReviewsBtn.textContent = 'Завантажити відгуки';
            }
        });
    }

});