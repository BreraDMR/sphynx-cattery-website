<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_login();

$user = current_user();
$page_title = t('auth.account.title') . ' • ' . t('common.brand');
require_once __DIR__ . '/includes/header.php';
?>

<section>
    <div class="container" style="max-width:560px;">
        <h2 class="section-title"><?= te('auth.account.title') ?></h2>

        <div class="card" style="text-align:left;">
            <p><strong><?= te('auth.account.name') ?>:</strong> <?= htmlspecialchars($user->name) ?></p>
            <p><strong><?= te('auth.account.email') ?>:</strong> <?= htmlspecialchars($user->email) ?></p>
            <p><strong><?= te('auth.account.role') ?>:</strong>
                <?= $user->isAdmin() ? te('auth.account.role.admin') : te('auth.account.role.user') ?></p>
            <?php if ($user->oauthProvider !== null): ?>
                <p><strong>OAuth:</strong> <?= htmlspecialchars(ucfirst($user->oauthProvider)) ?></p>
            <?php endif; ?>

            <p style="margin-top:20px;">
                <a href="cart.php" class="button"><?= te('auth.account.cart_link') ?></a>
                <?php if ($user->isAdmin()): ?>
                    <a href="admin_requests.php" class="button"><?= te('auth.account.requests_link') ?></a>
                <?php endif; ?>
                <a href="logout.php" class="button"><?= te('nav.logout') ?></a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
