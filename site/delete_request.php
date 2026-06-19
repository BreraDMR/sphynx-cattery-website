<?php

declare(strict_types=1);

use App\RequestRepository;

require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';

// Deletion must be a POST with a valid CSRF token -- the original version
// deleted on a plain GET link, which meant any third-party page could
// trigger it just by loading an <img> tag (see docs/report.md Editor's notes).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    die('Невірний запит.');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : null;

if ($id) {
    (new RequestRepository($pdo))->delete($id);
}

header('Location: requests.php');
exit;
