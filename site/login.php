<?php
require_once 'config/auth.php';
require_once 'config/admin_credentials.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: admin_requests.php');
        exit;
    }

    $error = 'Невірний логін або пароль.';
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід для адміністратора • Лисі Котики Прага</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="container" style="max-width:420px; margin-top:60px;">
    <h2 class="section-title">Вхід для адміністратора</h2>

    <?php if ($error !== ''): ?>
        <p class="error-text"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Логін</label>
        <input type="text" name="username" required autofocus>

        <label>Пароль</label>
        <input type="password" name="password" required>

        <button type="submit" class="button">Увійти</button>
    </form>
</main>
</body>
</html>
