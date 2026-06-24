<?php

declare(strict_types=1);

use App\RequestRepository;
use App\RequestStatus;
use App\RequestValidator;

require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';

$repo = new RequestRepository($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$id) {
    die('ID не передано');
}

$request = $repo->find($id);
if ($request === null) {
    die('Запис не знайдено');
}

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
    $status  = $_POST['status'] ?? $request->status;

    $errors = RequestValidator::validate($name, $email, $message, true, requireConsent: false);
    if (!RequestStatus::isValid($status)) {
        $errors[] = 'Невірний статус.';
    }

    if (empty($errors)) {
        $repo->update($id, $name, $email, $phone ?: null, $message);
        $repo->updateStatus($id, $status);

        header('Location: requests.php');
        exit;
    }
}

// header.php prints HTML, so it has to come AFTER the block above -- a
// successful update redirects via header('Location: ...'), which fails
// with "headers already sent" if header.php has already flushed output.
include 'includes/header.php';
?>

<h2 class="section-title">Редагувати заявку #<?= $id ?></h2>

<?php foreach ($errors as $err): ?>
    <p class="error-text"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label>ПІБ <span>*</span></label>
    <input type="text" name="name" value="<?= htmlspecialchars($request->name) ?>" required>

    <label>Email <span>*</span></label>
    <input type="email" name="email" value="<?= htmlspecialchars($request->email) ?>" required>

    <label>Телефон</label>
    <input type="tel" name="phone" value="<?= htmlspecialchars($request->phone ?? '') ?>">

    <label>Повідомлення</label>
    <textarea name="message" rows="5" required><?= htmlspecialchars($request->message) ?></textarea>

    <label>Статус</label>
    <select name="status">
        <?php foreach (RequestStatus::all() as $status): ?>
            <option value="<?= htmlspecialchars($status) ?>" <?= $status === $request->status ? 'selected' : '' ?>>
                <?= htmlspecialchars(RequestStatus::ukrainianLabel($status)) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="button">Оновити заявку</button>
</form>

<?php include 'includes/footer.php'; ?>
