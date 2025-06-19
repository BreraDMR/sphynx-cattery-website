<?php
/**
 * config/db.php — підключення до бази даних через PDO (СРС 6)
 *
 * Параметри підключення:
 * - $host:     адреса сервера БД (для MAMP локально зазвичай localhost)
 * - $dbname:   назва бази даних (заміни на свою, напр. 'sphynx_prague')
 * - $username: користувач MySQL (для MAMP часто root)
 * - $password: пароль MySQL (для MAMP часто root)
 */

$host     = 'localhost';
$dbname   = 'sphynx_prague'; // <-- заміни на свою назву БД за потреби
$username = 'root';
$password = 'root';

// DSN: тип драйвера + host + dbname + кодування
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Для навчального проєкту — просте повідомлення. На проді краще логувати деталі.
    die("❌ Помилка підключення до БД. Перевір параметри у config/db.php");
}
?>