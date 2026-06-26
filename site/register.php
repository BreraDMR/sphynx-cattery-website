<?php

declare(strict_types=1);

use App\UserRepository;
use App\UserValidator;

require_once __DIR__ . '/config/bootstrap.php';

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'account.php');
if (preg_match('#^https?://#i', $next) || str_starts_with($next, '//')) {
    $next = 'account.php';
}

if (is_logged_in()) {
    header('Location: ' . $next);
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'auth.error.csrf';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $old = ['name' => $name, 'email' => $email];

        $errors = UserValidator::validateRegistration($email, $name, $password, $passwordConfirm);

        $repo = new UserRepository($pdo);
        if ($errors === [] && $repo->emailExists($email)) {
            $errors[] = 'auth.error.email_taken';
        }

        if ($errors === []) {
            $user = $repo->create($email, password_hash($password, PASSWORD_BCRYPT), $name);
            login_user($user);
            header('Location: ' . $next);
            exit;
        }
    }
}

$page_title = t('auth.register.title') . ' • ' . t('common.brand');
require_once __DIR__ . '/includes/header.php';
?>

<section>
    <div class="container" style="max-width:460px;">
        <h2 class="section-title"><?= te('auth.register.title') ?></h2>

        <?php foreach ($errors as $__err): ?>
            <p class="error-text"><?= te($__err) ?></p>
        <?php endforeach; ?>

        <div class="auth-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <label><?= te('auth.register.name') ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" required autofocus>

                <label><?= te('auth.register.email') ?></label>
                <input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>

                <label><?= te('auth.register.password') ?></label>
                <input type="password" name="password" required>

                <label><?= te('auth.register.password_confirm') ?></label>
                <input type="password" name="password_confirm" required>

                <button type="submit" class="button" style="width:100%;"><?= te('auth.register.submit') ?></button>
            </form>

            <p class="auth-switch">
                <?= te('auth.register.have_account') ?>
                <a href="login.php?next=<?= urlencode($next) ?>"><?= te('nav.login') ?></a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
