<?php
require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';
include 'includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = "Вкажіть ПІБ";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некоректний email";
    if (strlen($message) < 10) $errors[] = "Повідомлення повинно бути не менше 10 символів";

    if (empty($errors)) {
        $sql = "INSERT INTO requests (name, email, phone, message) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $message]);

        header("Location: requests.php");
        exit;
    }
}
?>

<h2 class="section-title">Додати нову заявку</h2>

<?php foreach ($errors as $err): ?>
    <p class="error-text"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
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