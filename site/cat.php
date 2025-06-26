<?php

declare(strict_types=1);

use App\CatRepository;

require_once __DIR__ . '/config/bootstrap.php';

$repo = new CatRepository($pdo);
$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$cat = $slug !== '' ? $repo->findPublishedBySlug($slug) : null;

$page_title = $cat !== null
    ? $cat->name . ' • ' . t('common.brand')
    : t('cat.not_found');

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($cat === null): ?>
    <section>
        <div class="container">
            <h2 class="section-title"><?= te('cat.not_found') ?></h2>
            <p class="text-center"><?= te('cat.not_found.text') ?></p>
            <p class="text-center"><a href="index.php#catalog" class="button"><?= te('cat.back') ?></a></p>
        </div>
    </section>
<?php else: ?>
    <?php $ageLabel = $cat->ageMonths . ' ' . t('catalog.months'); ?>
    <section class="hero">
        <div class="container">
            <h1><?= htmlspecialchars(mb_strtoupper($cat->name)) ?></h1>
            <p><?= te('cat.detail.subtitle', ['color' => cat_color_label($cat->color), 'age' => $ageLabel]) ?></p>
        </div>
    </section>

    <section>
        <div class="container cat-detail">
            <img src="<?= htmlspecialchars($cat->toArray()['photo']) ?>" alt="<?= htmlspecialchars($cat->name) ?>" class="cat-detail-image">

            <div class="cat-detail-info">
                <p class="price price-highlight"><?= $cat->priceEur ?> €</p>
                <p><?= nl2br(htmlspecialchars($cat->description)) ?></p>

                <?php if (is_logged_in()): ?>
                    <button type="button" class="button add-cart-btn" data-type="cat" data-id="<?= $cat->id ?>"><?= te('common.add_to_cart') ?></button>
                <?php else: ?>
                    <a href="login.php?next=<?= urlencode('cat.php?slug=' . $cat->slug) ?>" class="button"><?= te('common.login_to_buy') ?></a>
                <?php endif; ?>
                <a href="contacts.php?cat=<?= urlencode($cat->name) ?>" class="button"><?= te('cat.contact') ?></a>
                <a href="index.php#catalog" class="button"><?= te('cat.back') ?></a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
