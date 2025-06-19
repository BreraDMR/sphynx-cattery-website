<?php
require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';
include 'includes/header.php';

// Cast to int: the original echoed $_GET['id'] straight into the page
// (see line below, fixed) and relied on MySQL's loose string-to-int
// comparison to find a row, which is a reflected-XSS vector for any id
// that has a valid numeric prefix (see docs/report.md Editor's notes).
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) die('ID не передано');

$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) die('Запис не знайдено');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = "Вкажіть ПІБ";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некоректний email";

    if (empty($errors)) {
        $sql = "UPDATE requests SET name=?, email=?, phone=?, message=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $message, $id]);

        header("Location: requests.php");
        exit;
    }
}
?>

<h2 class="section-title">Редагувати заявку #<?= $id ?></h2>

<?php foreach ($errors as $err): ?>
    <p class="error-text"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
    <label>ПІБ <span>*</span></label>
    <input type="text" name="name" value="<?= htmlspecialchars($request['name']) ?>" required>

    <label>Email <span>*</span></label>
    <input type="email" name="email" value="<?= htmlspecialchars($request['email']) ?>" required>

    <label>Телефон</label>
    <input type="tel" name="phone" value="<?= htmlspecialchars($request['phone'] ?? '') ?>">

    <label>Повідомлення</label>
    <textarea name="message" rows="5" required><?= htmlspecialchars($request['message']) ?></textarea>

    <button type="submit" class="button">Оновити заявку</button>
</form>

<?php include 'includes/footer.php'; ?>