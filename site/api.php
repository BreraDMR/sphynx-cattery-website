<?php

declare(strict_types=1);

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

use App\BotNotifier;
use App\RequestRepository;
use App\RequestValidator;

header('Content-Type: application/json');

require_once 'config/db.php';

session_start();

$data = json_decode((string) file_get_contents('php://input'), true) ?? [];

// Validate raw input first, escape only afterwards -- the original ran
// htmlspecialchars() before the length checks, so e.g. a name containing
// "<" would get inflated by entity-encoding before strlen() ever saw it.
$nameRaw  = trim((string) ($data['name'] ?? ''));
$emailRaw = trim((string) ($data['email'] ?? ''));
$phoneRaw = trim((string) ($data['phone'] ?? ''));
$ageRaw   = trim((string) ($data['age'] ?? ''));
$colorRaw = trim((string) ($data['color'] ?? ''));
$msgRaw   = trim((string) ($data['message'] ?? ''));
$consent  = !empty($data['consent']);

$errors = RequestValidator::validate($nameRaw, $emailRaw, $msgRaw, $consent);

if (empty($errors)) {
    $repo = new RequestRepository($pdo);
    $newId = $repo->create($nameRaw, $emailRaw, $phoneRaw ?: null, $ageRaw ?: null, $colorRaw ?: null, $msgRaw, $consent);

    // Push to sphynx-cats-crm-bot so the owner/admins see it in Telegram,
    // not just in admin_requests.php. Best-effort -- see BotNotifier.
    $newRequest = $repo->find($newId);
    if ($newRequest !== null) {
        BotNotifier::notifyNewRequest($newRequest);
    }

    // Збереження в файл (Завдання 4, СРС4) -- escape тільки тут, для логу,
    // а не для збереження/валідації.
    $name = htmlspecialchars($nameRaw);
    $line = date('Y-m-d H:i:s') . ' | ' . $name . ' | ' . htmlspecialchars($emailRaw) . ' | ' . htmlspecialchars($msgRaw) . PHP_EOL;
    file_put_contents('data/data.txt', $line, FILE_APPEND);

    // Збереження в сесію (Завдання 5, СРС5)
    $_SESSION['user_name'] = $name;

    $resp = [
        'status'  => 'success',
        'message' => 'Дякуємо, ' . $name . '! Ваша заявка успішно отримана.',
    ];
} else {
    $resp = [
        'status'  => 'error',
        'message' => 'Помилки: ' . htmlspecialchars(implode(' ', $errors)),
    ];
}

echo json_encode($resp);
