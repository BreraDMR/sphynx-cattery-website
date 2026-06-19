<?php
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

$id = $_POST['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: requests.php");
exit;
