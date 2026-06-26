<?php

declare(strict_types=1);

use App\TreatRepository;

require_once __DIR__ . '/config/bootstrap.php';

$repo = new TreatRepository($pdo);
$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$treat = $slug !== '' ? $repo->findPublishedBySlug($slug) : null;

$page_title = $treat !== null
    ? $treat->name . ' • ' . t('common.brand')
    : t('treat.not_found');

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($treat === null): ?>
    <section>
        <div class="container">
            <h2 class="section-title"><?= te('treat.not_found') ?></h2>
            <p class="text-center"><?= te('treat.not_found.text') ?></p>
            <p class="text-center"><a href="treats.php" class="button"><?= te('treat.back') ?></a></p>
        </div>
    </section>
<?php else: ?>
    <section class="hero">
        <div class="container">
            <h1><?= htmlspecialchars(mb_strtoupper($treat->name)) ?></h1>
            <p><?= te(TREAT_CATEGORY_KEYS[$treat->category] ?? 'treats.title') ?><?php if ($treat->weightG > 0): ?> • <?= $treat->weightG ?> <?= te('treats.weight') ?><?php endif; ?></p>
        </div>
    </section>

    <section>
        <div class="container cat-detail">
            <img src="<?= htmlspecialchars($treat->toArray()['photo']) ?>" alt="<?= htmlspecialchars($treat->name) ?>" class="cat-detail-image">

            <div class="cat-detail-info">
                <p class="price price-highlight"><?= $treat->priceEur ?> €</p>
                <p><?= nl2br(htmlspecialchars($treat->description)) ?></p>

                <?php if (is_logged_in()): ?>
                    <button type="button" class="button add-cart-btn" data-type="treat" data-id="<?= $treat->id ?>"><?= te('common.add_to_cart') ?></button>
                <?php else: ?>
                    <a href="login.php?next=<?= urlencode('treat.php?slug=' . $treat->slug) ?>" class="button"><?= te('common.login_to_buy') ?></a>
                <?php endif; ?>
                <a href="treats.php" class="button"><?= te('treat.back') ?></a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
