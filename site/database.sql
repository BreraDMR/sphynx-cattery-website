-- ============================================================================
-- СРС 6 (Бази даних та SQL) — навчальний приклад
-- Проєкт: "Лисі Котики Прага"
--
-- Schema + seed data only. The original file also had example UPDATE/DELETE
-- demo queries appended at the end, which meant importing it as instructed
-- ("імпортуй файл database.sql ... щоб додати тестові записи") silently
-- deleted one of the three rows it had just inserted. Demo queries now live
-- separately in demo-queries.sql -- see docs/report.md Editor's notes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    age VARCHAR(20) NULL,
    color VARCHAR(50) NULL,
    message TEXT NOT NULL,
    consent TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO requests (name, email, phone, age, color, message, consent, status, created_at) VALUES
('Анна Новак', 'anna.novak@example.com', '+420777111222', '2-3', 'білий', 'Хочу консультацію щодо сфінкса, цікавить доставка до Праги.', 1, 'new', '2026-04-01 10:15:00'),
('Петро Свобода', 'peter.svoboda@example.com', '+420777333444', '4-6', 'чорний', 'Підкажіть, чи є кошенята 2–3 місяці? Хочу забронювати.', 1, 'in_progress', '2026-04-05 18:40:00'),
('Ольга Коваль', 'olha.koval@example.com', NULL, NULL, 'білий', 'Цікавить білий сфінкс, прошу зв’язатися зі мною на email.', 1, 'closed', '2026-04-10 09:05:00');

-- ============================================================================
-- Каталог кошенят -- раніше жив лише у статичному assets/data/cats.json
-- (без CRUD, без БД). Тепер це таблиця, що читається/пишеться через
-- CatRepository: публічно -- api/cats.php (GET), приватно (POST/PATCH/DELETE
-- з X-API-Key) -- цим користується Telegram-бот, яким адміністратор додає
-- нових кошенят. status='draft' дозволяє зберегти картку, що ще не
-- з'явиться в публічному каталозі, поки її не підтвердять.
-- ============================================================================

CREATE TABLE IF NOT EXISTS cats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(50) NOT NULL,
    age_months INT UNSIGNED NOT NULL,
    price_eur INT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    created_by VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cats (slug, name, color, age_months, price_eur, description, photo_path, status, created_by) VALUES
('chornyi-sfinks-maks', 'Чорний Сфінкс Макс', 'чорний', 3, 1450, 'Грайливий і дуже контактний кошеня, звик до рук з перших днів. Щеплення за віком, ветпаспорт додається.', 'assets/images/sphynx-black.webp', 'published', 'seed'),
('chornyi-sfinks-tom', 'Чорний Сфінкс Том', 'чорний', 5, 1390, 'Спокійний і ласкавий, добре ладнає з іншими тваринами. Чистопородний, документи в наявності.', 'assets/images/sphynx-black.webp', 'published', 'seed'),
('bilyi-sfinks-luna', 'Білий Сфінкс Луна', 'білий', 4, 1680, 'Рідкісний білий окрас, блакитні очі. Дуже цікава та товариська, любить бути в центрі уваги.', 'assets/images/sphynx-white.webp', 'published', 'seed'),
('bilyi-sfinks-bella', 'Білий Сфінкс Белла', 'білий', 2, 1590, 'Маленька білосніжна пустунка. Привчена до лотка, активно цікавиться іграшками.', 'assets/images/sphynx-white.webp', 'published', 'seed'),
('blakytnyi-sfinks-leo', 'Блакитний Сфінкс Лео', 'блакитний', 2, 1590, 'Представник чемпіонської лінії, насичений блакитний окрас шкіри. Контактний, не боїться нових людей.', 'assets/images/sphynx-blue.webp', 'published', 'seed'),
('kremovyi-sfinks-simmi', 'Кремовий Сфінкс Сіммі', 'кремовий', 6, 1420, 'Тепло-кремовий відтінок шкіри, дуже спокійний і вже привчений до інших котів у домі.', 'assets/images/sphynx-cream.webp', 'published', 'seed'),
('lylovyi-sfinks-grei', 'Лиловий Сфінкс Грей', 'лиловий', 3, 1510, 'Рідкісний лиловий (сизий) окрас із яскраво-зеленими очима. Граційний і дуже фотогенічний.', 'assets/images/sphynx-lilac.webp', 'published', 'seed');
