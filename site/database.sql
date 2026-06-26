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

-- ============================================================================
-- Users -- site accounts (added alongside i18n + cart + OAuth). A user can
-- have a bcrypt password (email/password sign-up) and/or an OAuth identity
-- (oauth_provider + oauth_id). role='admin' replaces the old single hard-coded
-- admin login: the admin pages and the bot's /requests view are gated by it.
--
-- Seed admin (email/password login): admin@sphynx.local. The password_hash
-- below is the original coursework demo hash -- the LIVE deploy overwrites it
-- with the rotated hash from site.env (see the WS6 migration), so do not rely
-- on this default beyond a fresh local/CI install.
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    name VARCHAR(100) NOT NULL,
    oauth_provider VARCHAR(20) NULL,
    oauth_id VARCHAR(100) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (email, password_hash, name, role) VALUES
('admin@sphynx.local', '$2b$10$/aHMxiyAvkalENkqc2.5xOQJTdj6ua/Mb2qJ8TIbp6RUb9yJzqQhi', 'Administrator', 'admin');

-- ============================================================================
-- Treats ("вкусняшки") -- second catalog, a sibling of `cats`. Same shape:
-- public read via api/treats.php (GET), bot-only writes (POST/DELETE with
-- X-API-Key). `category` is a canonical key (see TreatValidator::CATEGORIES),
-- labels are translated in the UI. 10 demo items below.
-- ============================================================================

CREATE TABLE IF NOT EXISTS treats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(30) NOT NULL,
    price_eur INT UNSIGNED NOT NULL,
    weight_g INT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    created_by VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO treats (slug, name, category, price_eur, weight_g, description, photo_path, status, created_by) VALUES
('chicken-jerky-bites', 'Chicken Jerky Bites', 'snacks', 6, 80, 'Soft air-dried chicken bites — a high-protein reward Sphynxes love, with no grain or artificial colours.', NULL, 'published', 'seed'),
('salmon-cream-tubes', 'Salmon Cream Tubes', 'snacks', 5, 60, 'Lickable salmon cream tubes, rich in omega-3 for healthy skin — perfect for a hairless cat.', NULL, 'published', 'seed'),
('grain-free-kitten-food', 'Grain-Free Kitten Food', 'food', 19, 2000, 'Complete grain-free dry food for kittens, with extra calories to support the fast metabolism of a Sphynx.', NULL, 'published', 'seed'),
('adult-sphynx-food', 'Adult Sphynx Formula', 'food', 24, 3000, 'Balanced adult formula tuned for the higher energy needs of hairless breeds, with taurine and biotin.', NULL, 'published', 'seed'),
('immune-multivitamin', 'Immune Multivitamin', 'vitamins', 12, 100, 'Daily multivitamin paste supporting immunity and coat — sorry, skin! — health. Veterinarian recommended.', NULL, 'published', 'seed'),
('omega-skin-drops', 'Omega Skin Drops', 'vitamins', 14, 50, 'Omega-3/6 oil drops for soft, healthy Sphynx skin; just add a few drops to the daily meal.', NULL, 'published', 'seed'),
('feather-teaser-wand', 'Feather Teaser Wand', 'toys', 8, 0, 'Interactive feather wand for active play — keeps a curious Sphynx busy and bonded with you.', NULL, 'published', 'seed'),
('plush-mouse-trio', 'Plush Mouse Trio', 'toys', 7, 0, 'Set of three catnip-filled plush mice, just the right size for batting around the apartment.', NULL, 'published', 'seed'),
('gentle-bath-shampoo', 'Gentle Bath Shampoo', 'care', 11, 250, 'Mild hypoallergenic shampoo for the regular baths hairless cats need to keep their skin clean.', NULL, 'published', 'seed'),
('soft-ear-wipes', 'Soft Ear Wipes', 'care', 9, 120, 'Pack of gentle ear-cleaning wipes — Sphynxes produce more ear wax than coated breeds and need regular care.', NULL, 'published', 'seed');

-- ============================================================================
-- Cart items -- one row per product (cat or treat) in a logged-in user's
-- cart. Cats are unique (qty always 1); treats can be bought in multiples.
-- ============================================================================

CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(10) NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cart_item (user_id, item_type, item_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
