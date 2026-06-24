<?php

declare(strict_types=1);

use App\CatRepository;

require_once __DIR__ . '/config/db.php';

$repo = new CatRepository($pdo);
$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$cat = $slug !== '' ? $repo->findPublishedBySlug($slug) : null;

include __DIR__ . '/includes/header.php';
?>

<?php if ($cat === null): ?>
    <section>
        <div class="container">
            <h2 class="section-title">Кошеня не знайдено</h2>
            <p class="text-center">Можливо, його вже забронювали або посилання застаріле.</p>
            <p class="text-center"><a href="index.html#catalog" class="button">До каталогу</a></p>
        </div>
    </section>
<?php else: ?>
    <section class="hero">
        <div class="container">
            <h1><?= htmlspecialchars(mb_strtoupper($cat->name)) ?></h1>
            <p><?= htmlspecialchars(ucfirst($cat->color)) ?> сфінкс • <?= htmlspecialchars($cat->ageLabel()) ?></p>
        </div>
    </section>

    <section>
        <div class="container cat-detail">
            <img src="<?= htmlspecialchars($cat->toArray()['photo']) ?>" alt="<?= htmlspecialchars($cat->name) ?>" class="cat-detail-image">

            <div class="cat-detail-info">
                <p class="price price-highlight"><?= $cat->priceEur ?> €</p>
                <p><?= nl2br(htmlspecialchars($cat->description)) ?></p>
                <a href="contacts.html?cat=<?= urlencode($cat->name) ?>" class="button">Зв'язатися щодо цього кошеня</a>
                <a href="index.html#catalog" class="button">← До каталогу</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
