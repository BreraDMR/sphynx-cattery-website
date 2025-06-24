<?php

declare(strict_types=1);

use App\RequestRepository;
use App\RequestValidator;

require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';

$repo = new RequestRepository($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Невірний запит.');
    }

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // requireConsent: false -- this is the admin manually logging a request
    // on a customer's behalf, not the public form, so there's no consent
    // checkbox here.
    $errors = RequestValidator::validate($name, $email, $message, true, requireConsent: false);

    if (empty($errors)) {
        $id = $repo->create($name, $email, $phone ?: null, null, null, $message, true);

        header('Location: requests.php');
        exit;
    }
}

// header.php prints HTML, so it has to come AFTER the block above -- a
// successful create redirects via header('Location: ...'), which fails
// with "headers already sent" if header.php has already flushed output.
include 'includes/header.php';
?>

<h2 class="section-title">Додати нову заявку</h2>

<?php foreach ($errors as $err): ?>
    <p class="error-text"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label>ПІБ <span>*</span></label>
    <input type="text" name="name" required>

    <label>Email <span>*</span></label>
    <input type="email" name="email" required>

    <label>Телефон</label>
    <input type="tel" name="phone">

    <label>Повідомлення</label>
    <textarea name="message" rows="5" required></textarea>

    <button type="submit" class="button">Надіслати заявку</button>
</form>

<?php include 'includes/footer.php'; ?>
