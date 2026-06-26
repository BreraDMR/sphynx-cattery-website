<?php

declare(strict_types=1);

use App\CartRepository;

require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Shared site header: localized, auth-aware navigation. The "Requests" admin
 * link only appears for an administrator; the cart + account links only for a
 * signed-in user. $page_title may be set by the including page before the
 * include; otherwise the brand is used.
 */

$__user = current_user();
$__cartCount = 0;
if ($__user !== null && isset($GLOBALS['pdo'])) {
    $__cartCount = (new CartRepository($GLOBALS['pdo']))->count($__user->id);
}

$__title = $page_title ?? t('common.brand');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($__title) ?></title>
    <link rel="icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>

<div class="top-bar"><?= te('common.topbar') ?></div>

<header class="header">
    <div class="container">
        <a href="index.php" class="logo-link">
            <img src="assets/images/logo.svg" alt="<?= te('common.brand') ?>" class="logo">
        </a>
        <button id="mobileMenuBtn" class="mobile-menu-btn" aria-label="<?= te('nav.menu_open') ?>" aria-expanded="false">☰</button>

        <nav class="nav">
            <a href="index.php"><?= te('nav.home') ?></a>
            <a href="index.php#catalog"><?= te('nav.catalog') ?></a>
            <a href="treats.php"><?= te('nav.treats') ?></a>
            <a href="about.php"><?= te('nav.about') ?></a>
            <a href="delivery.php"><?= te('nav.delivery') ?></a>
            <a href="contacts.php"><?= te('nav.contacts') ?></a>
            <?php if (is_admin()): ?>
                <a href="admin_requests.php" class="nav-admin"><?= te('nav.requests') ?></a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <div class="lang-switch">
                <?php foreach (SUPPORTED_LOCALES as $__loc): ?>
                    <a href="<?= htmlspecialchars(lang_switch_url($__loc)) ?>"
                       class="lang-link <?= $__loc === current_locale() ? 'active' : '' ?>"><?= htmlspecialchars(locale_label($__loc)) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($__user !== null): ?>
                <a href="cart.php" class="cart-link" title="<?= te('nav.cart') ?>">🛒<?php if ($__cartCount > 0): ?><span class="cart-badge"><?= $__cartCount ?></span><?php endif; ?></a>
                <a href="account.php" class="account-link"><?= htmlspecialchars($__user->name) ?></a>
                <a href="logout.php" class="auth-link"><?= te('nav.logout') ?></a>
            <?php else: ?>
                <a href="login.php" class="auth-link"><?= te('nav.login') ?></a>
                <a href="register.php" class="button auth-register"><?= te('nav.register') ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">
