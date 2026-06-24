<?php

declare(strict_types=1);

/**
 * api/requests.php -- read-only, X-API-Key-gated view of the `requests`
 * table for sphynx-cats-crm-bot's /requests command. The public contact
 * form (api.php) already pushes new requests to the bot via
 * BotNotifier; this endpoint is the pull side -- lets an admin re-check
 * "what's pending" from Telegram without opening admin_requests.php, and
 * is what the bot would fall back to if a push notification got lost
 * (bot offline, network blip).
 */

use App\RequestRepository;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Метод не підтримується.']);
    exit;
}

$expected = env('BOT_API_KEY');
$given = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!$expected || !hash_equals($expected, $given)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Невірний або відсутній X-API-Key.']);
    exit;
}

$repo = new RequestRepository($pdo);
$limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 10;
$status = isset($_GET['status']) ? (string) $_GET['status'] : null;

$requests = array_map(
    static fn ($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'email' => $r->email,
        'phone' => $r->phone,
        'age' => $r->age,
        'color' => $r->color,
        'message' => $r->message,
        'status' => $r->status,
        'created_at' => $r->createdAt,
    ],
    $repo->all($status, $limit, 0)
);

echo json_encode(['status' => 'success', 'requests' => $requests]);
