<?php

declare(strict_types=1);

use App\UserRepository;

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/oauth.php';

// Where to go after a successful login (defaults to the account page). Only
// same-site relative paths are honoured, to avoid an open-redirect.
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'account.php');
if (preg_match('#^https?://#i', $next) || str_starts_with($next, '//')) {
    $next = 'account.php';
}

if (is_logged_in()) {
    header('Location: ' . $next);
    exit;
}

$error = '';

// Surface a one-shot error from a failed OAuth round-trip (set in oauth.php).
if (!empty($_SESSION['flash_error'])) {
    $error = (string) $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = t('auth.error.csrf');
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $repo = new UserRepository($pdo);
        $user = $repo->findByEmail($email);

        if ($user !== null && $user->passwordHash !== null && password_verify($password, $user->passwordHash)) {
            login_user($user);
            header('Location: ' . $next);
            exit;
        }

        $error = t('auth.login.error');
    }
}

$page_title = t('auth.login.title') . ' • ' . t('common.brand');
require_once __DIR__ . '/includes/header.php';
?>

<section>
    <div class="container" style="max-width:460px;">
        <h2 class="section-title"><?= te('auth.login.title') ?></h2>

        <?php if ($error !== ''): ?>
            <p class="error-text"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="auth-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <label><?= te('auth.login.email') ?></label>
                <input type="email" name="email" required autofocus>

                <label><?= te('auth.login.password') ?></label>
                <input type="password" name="password" required>

                <button type="submit" class="button" style="width:100%;"><?= te('auth.login.submit') ?></button>
            </form>

            <?php $providers = oauth_providers(); ?>
            <?php if ($providers !== []): ?>
                <div class="auth-divider"><span><?= te('auth.login.or') ?></span></div>
                <div class="oauth-buttons">
                    <?php foreach ($providers as $__name => $__cfg): ?>
                        <a href="oauth.php?provider=<?= urlencode($__name) ?>&amp;action=start&amp;next=<?= urlencode($next) ?>"
                           class="button oauth-btn oauth-<?= htmlspecialchars($__name) ?>">
                            <?= te('auth.login.social', ['provider' => $__cfg['label']]) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="auth-switch">
                <?= te('auth.login.no_account') ?>
                <a href="register.php?next=<?= urlencode($next) ?>"><?= te('nav.register') ?></a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
