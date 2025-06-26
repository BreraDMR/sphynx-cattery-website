<?php

declare(strict_types=1);

/**
 * api/treats.php -- catalog of treats ("вкусняшки"), backed by the `treats`
 * table. A sibling of api/cats.php with the same contract:
 *
 * GET    (public)            -- list published treats, or one via ?slug=
 * GET    ?all=1  + API key   -- list every treat regardless of status (bot)
 * POST   + API key           -- create a treat (multipart/form-data, optional `photo`)
 * DELETE ?id=    + API key   -- remove a treat (bot's /delete_treat)
 *
 * The X-API-Key is the machine-to-machine secret shared with the Telegram bot.
 */

use App\CatPhotoUploader;
use App\TreatRepository;
use App\TreatValidator;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$repo = new TreatRepository($pdo);
$method = $_SERVER['REQUEST_METHOD'];

function require_api_key(): void
{
    $expected = env('BOT_API_KEY');
    $given = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if (!$expected || !hash_equals($expected, $given)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing X-API-Key.']);
        exit;
    }
}

if ($method === 'GET') {
    if (isset($_GET['slug'])) {
        $treat = $repo->findPublishedBySlug((string) $_GET['slug']);
        if ($treat === null) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Treat not found.']);
            exit;
        }
        echo json_encode(['status' => 'success', 'treat' => $treat->toArray()]);
        exit;
    }

    if (isset($_GET['all'])) {
        require_api_key();
        $treats = array_map(static fn ($t) => $t->toArray(), $repo->all());
        echo json_encode(['status' => 'success', 'treats' => $treats]);
        exit;
    }

    $category = isset($_GET['category']) ? (string) $_GET['category'] : null;
    $treats = array_map(static fn ($t) => $t->toArray(), $repo->publishedAll($category));
    echo json_encode(['status' => 'success', 'treats' => $treats]);
    exit;
}

if ($method === 'POST') {
    require_api_key();

    $name = trim((string) ($_POST['name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $priceEur = (int) ($_POST['price_eur'] ?? 0);
    $weightG = (int) ($_POST['weight_g'] ?? 0);
    $description = trim((string) ($_POST['description'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'published'));
    $createdBy = trim((string) ($_POST['created_by'] ?? '')) ?: null;

    $errors = TreatValidator::validate($name, $category, $priceEur, $weightG, $description);
    if (!TreatValidator::isValidStatus($status)) {
        $errors[] = 'Invalid status.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    $treat = $repo->create($name, $category, $priceEur, $weightG, $description, null, $status, $createdBy);

    if (isset($_FILES['photo'])) {
        try {
            $uploader = new CatPhotoUploader(__DIR__ . '/../assets/images/treats', 'assets/images/treats/');
            $photoPath = $uploader->store($_FILES['photo'], $treat->slug);
            $repo->updatePhoto($treat->id, $photoPath);
            $treat = $repo->find($treat->id);
        } catch (RuntimeException $e) {
            http_response_code(201);
            echo json_encode(['status' => 'partial', 'message' => $e->getMessage(), 'treat' => $treat->toArray()]);
            exit;
        }
    }

    http_response_code(201);
    echo json_encode(['status' => 'success', 'treat' => $treat->toArray()]);
    exit;
}

if ($method === 'DELETE') {
    require_api_key();

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0 || $repo->find($id) === null) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Treat not found.']);
        exit;
    }

    $repo->delete($id);
    echo json_encode(['status' => 'success', 'message' => 'Deleted.']);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not supported.']);
