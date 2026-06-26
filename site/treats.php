<?php

declare(strict_types=1);

/**
 * Treats catalog ("вкусняшки") -- a sibling of the kitten catalog on the home
 * page. Cards are loaded client-side from api/treats.php and added by the
 * admin through the Telegram bot, exactly like cats.
 */

require_once __DIR__ . '/config/bootstrap.php';
$page_title = t('treats.title') . ' • ' . t('common.brand');
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1><?= te('treats.title') ?></h1>
        <p><?= te('treats.subtitle') ?></p>
    </div>
</section>

<section id="treats" class="section--white">
    <div class="container">
        <div class="text-center mb-30">
            <input type="text" id="treatSearchInput" class="search-input" placeholder="<?= te('treats.search') ?>">

            <div class="mt-15 filter-row">
                <button class="button treat-filter-btn active" data-category="all"><?= te('treats.cat.all') ?></button>
                <?php foreach (TREAT_CATEGORY_KEYS as $__cat => $__key): ?>
                    <button class="button treat-filter-btn" data-category="<?= htmlspecialchars($__cat) ?>"><?= te($__key) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="treatsContainer" class="cats-container-grid"></div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
