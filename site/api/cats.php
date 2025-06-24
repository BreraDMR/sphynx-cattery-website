<?php

declare(strict_types=1);

/**
 * api/cats.php -- catalog of kittens, backed by the `cats` table.
 *
 * GET    (public)            -- list published cats, or one via ?slug=
 * GET    ?all=1  + API key   -- list every cat regardless of status (bot's /list_cats)
 * POST   + API key           -- create a cat (multipart/form-data, optional `photo` file)
 * DELETE ?id=    + API key   -- remove a cat (bot's /delete_cat)
 *
 * The API key is machine-to-machine auth for the Telegram bot (see
 * sphynx-cats-crm-bot) -- intentionally a static shared secret rather than
 * the session/CSRF scheme used by the admin pages, since there is no
 * browser session here.
 */

use App\CatPhotoUploader;
use App\CatRepository;
use App\CatValidator;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

$repo = new CatRepository($pdo);
$method = $_SERVER['REQUEST_METHOD'];

function require_api_key(): void
{
    $expected = env('BOT_API_KEY');
    $given = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if (!$expected || !hash_equals($expected, $given)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Невірний або відсутній X-API-Key.']);
        exit;
    }
}

if ($method === 'GET') {
    if (isset($_GET['slug'])) {
        $cat = $repo->findPublishedBySlug((string) $_GET['slug']);
        if ($cat === null) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Кошеня не знайдено.']);
            exit;
        }
        echo json_encode(['status' => 'success', 'cat' => $cat->toArray()]);
        exit;
    }

    if (isset($_GET['all'])) {
        require_api_key();
        $cats = array_map(static fn ($cat) => $cat->toArray(), $repo->all());
        echo json_encode(['status' => 'success', 'cats' => $cats]);
        exit;
    }

    $color = isset($_GET['color']) ? (string) $_GET['color'] : null;
    $cats = array_map(static fn ($cat) => $cat->toArray(), $repo->publishedAll($color));
    echo json_encode(['status' => 'success', 'cats' => $cats]);
    exit;
}

if ($method === 'POST') {
    require_api_key();

    $name = trim((string) ($_POST['name'] ?? ''));
    $color = trim((string) ($_POST['color'] ?? ''));
    $ageMonths = (int) ($_POST['age_months'] ?? 0);
    $priceEur = (int) ($_POST['price_eur'] ?? 0);
    $description = trim((string) ($_POST['description'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'published'));
    $createdBy = trim((string) ($_POST['created_by'] ?? '')) ?: null;

    $errors = CatValidator::validate($name, $color, $ageMonths, $priceEur, $description);
    if (!CatValidator::isValidStatus($status)) {
        $errors[] = 'Невірний статус.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    $cat = $repo->create($name, $color, $ageMonths, $priceEur, $description, null, $status, $createdBy);

    if (isset($_FILES['photo'])) {
        try {
            $uploader = new CatPhotoUploader(__DIR__ . '/../assets/images/cats');
            $photoPath = $uploader->store($_FILES['photo'], $cat->slug);
            $repo->updatePhoto($cat->id, $photoPath);
            $cat = $repo->find($cat->id);
        } catch (RuntimeException $e) {
            // Card is already created -- a bad photo shouldn't lose the rest
            // of the submission, so this comes back as a partial-success
            // warning rather than a 4xx that would make the bot retry everything.
            http_response_code(201);
            echo json_encode(['status' => 'partial', 'message' => $e->getMessage(), 'cat' => $cat->toArray()]);
            exit;
        }
    }

    http_response_code(201);
    echo json_encode(['status' => 'success', 'cat' => $cat->toArray()]);
    exit;
}

if ($method === 'DELETE') {
    require_api_key();

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0 || $repo->find($id) === null) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Кошеня не знайдено.']);
        exit;
    }

    $repo->delete($id);
    echo json_encode(['status' => 'success', 'message' => 'Видалено.']);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Метод не підтримується.']);
