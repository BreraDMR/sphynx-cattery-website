<?php
/**
 * api.php — public contact form endpoint (СРС 4/5/6).
 *
 * Originally this only validated input and appended a line to
 * data/data.txt -- meaning anything submitted through the public contact
 * form never reached the requests table the admin panel reads from. It now
 * writes to both: the requests table (so submissions actually show up in
 * admin_requests.php / requests.php) and a local log file (kept for the
 * original СРС4 "save to file" exercise). See docs/report.md Editor's notes.
 */

header("Content-Type: application/json");

require_once 'config/db.php';

session_start();

$data = json_decode(file_get_contents("php://input"), true);

// Validate raw input first, escape only afterwards -- the original ran
// htmlspecialchars() before the length checks, so e.g. a name containing
// "<" would get inflated by entity-encoding before strlen() ever saw it.
$nameRaw  = trim((string)($data['name'] ?? ''));
$emailRaw = trim((string)($data['email'] ?? ''));
$phoneRaw = trim((string)($data['phone'] ?? ''));
$ageRaw   = trim((string)($data['age'] ?? ''));
$colorRaw = trim((string)($data['color'] ?? ''));
$msgRaw   = trim((string)($data['message'] ?? ''));
$consent  = !empty($data['consent']);

$error = "";

if (strlen($nameRaw) <= 2) {
    $error .= " Ім'я повинно бути більше двох символів. ";
}
if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
    $error .= " Email заповнено некоректно. ";
}
if ($msgRaw !== '' && strlen($msgRaw) <= 10) {
    $error .= " Повідомлення занадто коротке. ";
}
if (!$consent) {
    $error .= " Потрібна згода на обробку персональних даних. ";
}

if ($error === "") {
    // Збереження в БД (та саму таблицю, яку показує admin_requests.php)
    $stmt = $pdo->prepare(
        "INSERT INTO requests (name, email, phone, age, color, message, consent) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nameRaw, $emailRaw, $phoneRaw ?: null, $ageRaw ?: null, $colorRaw ?: null, $msgRaw, 1]);

    // Збереження в файл (Завдання 4, СРС4) -- escape тільки тут, для логу,
    // а не для збереження/валідації.
    $name = htmlspecialchars($nameRaw);
    $line = date('Y-m-d H:i:s') . " | " . $name . " | " . htmlspecialchars($emailRaw) . " | " . htmlspecialchars($msgRaw) . PHP_EOL;
    file_put_contents("data/data.txt", $line, FILE_APPEND);

    // Збереження в сесію (Завдання 5, СРС5)
    $_SESSION['user_name'] = $name;

    $resp = [
        "status"  => "success",
        "message" => "Дякуємо, " . $name . "! Ваша заявка успішно отримана."
    ];
} else {
    $resp = [
        "status"  => "error",
        "message" => "Помилки: " . htmlspecialchars($error)
    ];
}

echo json_encode($resp);
