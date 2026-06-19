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
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO requests (name, email, phone, age, color, message, consent, created_at) VALUES
('Анна Новак', 'anna.novak@example.com', '+420777111222', '2-3', 'білий', 'Хочу консультацію щодо сфінкса, цікавить доставка до Праги.', 1, '2026-04-01 10:15:00'),
('Петро Свобода', 'peter.svoboda@example.com', '+420777333444', '4-6', 'чорний', 'Підкажіть, чи є кошенята 2–3 місяці? Хочу забронювати.', 1, '2026-04-05 18:40:00'),
('Ольга Коваль', 'olha.koval@example.com', NULL, NULL, 'білий', 'Цікавить білий сфінкс, прошу зв’язатися зі мною на email.', 1, '2026-04-10 09:05:00');
