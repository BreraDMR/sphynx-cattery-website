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
-- Seed admin (email/password login): admin@sphynx.local / sphynx-admin-2026
-- (the hash below is bcrypt of that demo password). The LIVE deploy overwrites
-- this row with the rotated hash from site.env, so the default password only
-- applies to a fresh local/CI install -- change it before any real exposure.
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
('admin@sphynx.local', '$2y$12$TiufN9/rwq1QbDUDNLH2oeIgioEIDfx1EN9VMkZDUV3y6kmVvmc9y', 'Administrator', 'admin');

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

-- Extra demo seeds (2026-06-26): fill out thin filter categories so none is
-- empty -- more blue/"other" cats and more treats per category. INSERT IGNORE
-- on the UNIQUE slug makes this safe to run more than once.

INSERT IGNORE INTO cats (slug, name, color, age_months, price_eur, description, photo_path, status, created_by) VALUES
('chornyi-sfinks-nuar', 'Чорний Сфінкс Нуар', 'чорний', 4, 1480, 'Оксамитово-чорна шкіра, золотисті очі. Дуже відданий і любить спати поряд з господарем.', 'assets/images/sphynx-black.webp', 'published', 'seed'),
('bilyi-sfinks-snizhok', 'Білий Сфінкс Сніжок', 'білий', 3, 1620, 'Білосніжний пустун із рожевими вушками. Обожнює гратися й миттєво знаходить спільну мову з дітьми.', 'assets/images/sphynx-white.webp', 'published', 'seed'),
('blakytnyi-sfinks-dymok', 'Блакитний Сфінкс Димок', 'блакитний', 4, 1550, 'Рівний димчасто-блакитний окрас, спокійний характер. Чудово підходить для квартири.', 'assets/images/sphynx-blue.webp', 'published', 'seed'),
('blakytnyi-sfinks-azur', 'Блакитний Сфінкс Азур', 'блакитний', 5, 1530, 'Граційний кіт із виразними очима. Привчений до лотка та кігтеточки, любить теплі пледи.', 'assets/images/sphynx-blue.webp', 'published', 'seed'),
('blakytnyi-sfinks-mira', 'Блакитна Сфінкс Міра', 'блакитний', 2, 1600, 'Ласкава дівчинка блакитного окрасу, дуже товариська й цікава до всього нового.', 'assets/images/sphynx-blue.webp', 'published', 'seed'),
('inshyi-sfinks-mokko', 'Шоколадний Сфінкс Мокко', 'інший', 3, 1570, 'Рідкісний шоколадний відтінок шкіри. Грайливий і кмітливий, швидко вчить трюки.', 'assets/images/sphynx-cream.webp', 'published', 'seed'),
('inshyi-sfinks-koryca', 'Сфінкс Кориця', 'інший', 4, 1490, 'Теплий рудувато-коричневий окрас. Лагідна та спокійна, любить сидіти на руках.', 'assets/images/sphynx-cream.webp', 'published', 'seed'),
('inshyi-sfinks-arlekin', 'Сфінкс Арлекін', 'інший', 5, 1650, 'Ефектний двоколірний (арлекін) окрас. Активний і допитливий, справжня окраса дому.', 'assets/images/sphynx-black.webp', 'published', 'seed'),
('inshyi-sfinks-perlyna', 'Сфінкс Перлина', 'інший', 2, 1700, 'Незвичний перламутровий відтінок шкіри з бузковим підтоном. Ніжна й тендітна красуня.', 'assets/images/sphynx-lilac.webp', 'published', 'seed'),
('kremovyi-sfinks-vanil', 'Кремовий Сфінкс Ваніль', 'кремовий', 3, 1440, 'М''який ванільно-кремовий окрас. Дуже домашня й лагідна, любить спати під ковдрою.', 'assets/images/sphynx-cream.webp', 'published', 'seed'),
('kremovyi-sfinks-karamel', 'Кремовий Сфінкс Карамель', 'кремовий', 5, 1410, 'Карамельний відтінок і бурштинові очі. Спокійний характер, добре ладнає з іншими тваринами.', 'assets/images/sphynx-cream.webp', 'published', 'seed'),
('lylovyi-sfinks-buzok', 'Лиловий Сфінкс Бузок', 'лиловий', 4, 1520, 'Ніжний лиловий (бузковий) окрас. Граційний і фотогенічний, любить увагу гостей.', 'assets/images/sphynx-lilac.webp', 'published', 'seed'),
('lylovyi-sfinks-fialka', 'Лилова Сфінкс Фіалка', 'лиловий', 2, 1560, 'Маленька лилова красуня з великими очима. Допитлива пустунка, привчена до лотка.', 'assets/images/sphynx-lilac.webp', 'published', 'seed');

INSERT IGNORE INTO treats (slug, name, category, price_eur, weight_g, description, photo_path, status, created_by) VALUES
('beef-liver-treats', 'Beef Liver Treats', 'snacks', 6, 70, 'Crunchy freeze-dried beef liver bites — a single-ingredient, irresistible high-value reward.', NULL, 'published', 'seed'),
('tuna-flakes', 'Tuna Flakes', 'snacks', 5, 50, 'Light, flaky tuna treats packed with protein; great as a meal topper or a training reward.', NULL, 'published', 'seed'),
('sensitive-skin-food', 'Sensitive Skin Formula', 'food', 22, 2000, 'Limited-ingredient dry food for cats with delicate skin — gentle on digestion, rich in omega oils.', NULL, 'published', 'seed'),
('high-energy-pate', 'High-Energy Pâté', 'food', 16, 800, 'Smooth, calorie-dense pâté that helps a Sphynx keep weight on and stay warm.', NULL, 'published', 'seed'),
('probiotic-powder', 'Probiotic Powder', 'vitamins', 13, 90, 'Daily probiotic supplement that supports gut flora and steady digestion.', NULL, 'published', 'seed'),
('digestive-care-paste', 'Digestive Care Paste', 'vitamins', 10, 75, 'Tasty paste with prebiotics and malt to ease digestion and reduce hairballs.', NULL, 'published', 'seed'),
('catnip-banana', 'Catnip Banana', 'toys', 6, 0, 'Soft catnip-stuffed banana toy — the classic kick-and-bunny-kick favourite.', NULL, 'published', 'seed'),
('led-laser-pointer', 'LED Laser Pointer', 'toys', 9, 0, 'Rechargeable laser pointer for energetic chase sessions; tires out even the liveliest Sphynx.', NULL, 'published', 'seed'),
('microfiber-bath-towel', 'Microfiber Bath Towel', 'care', 13, 200, 'Ultra-absorbent microfiber towel sized for cats — dries a freshly bathed Sphynx in seconds.', NULL, 'published', 'seed'),
('protective-paw-balm', 'Protective Paw Balm', 'care', 8, 30, 'Nourishing balm that protects sensitive paw pads from dry floors and cold surfaces.', NULL, 'published', 'seed');
