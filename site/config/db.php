<?php

declare(strict_types=1);

/**
 * config/db.php — підключення до бази даних через PDO (СРС 6)
 *
 * Параметри підключення тепер беруться зі змінних середовища (.env), а не
 * лежать прямо в коді -- скопіюй .env.example у .env і за потреби зміни
 * значення там. Якщо .env немає (наприклад, файл щойно склонували), нижче
 * є ті самі дефолти, що були тут раніше.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/env.php';

load_env(__DIR__ . '/../.env');

$host     = env('DB_HOST', 'localhost');
$dbname   = env('DB_NAME', 'sphynx_prague');
$username = env('DB_USER', 'root');
$password = env('DB_PASSWORD', 'root');

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Для навчального проєкту — просте повідомлення. На проді краще логувати деталі.
    die('❌ Помилка підключення до БД. Перевір параметри у .env (див. .env.example).');
}
