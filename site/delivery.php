<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
$page_title = t('delivery.title');
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1><?= te('delivery.hero.title') ?></h1>
        <p><?= te('delivery.hero.subtitle') ?></p>
    </div>
</section>

<section class="section--light">
    <div class="container">
        <h2 class="section-title"><?= te('delivery.process.title') ?></h2>
        <div class="steps">
            <div class="step"><?= te('delivery.process.1') ?></div>
            <div class="step"><?= te('delivery.process.2') ?></div>
            <div class="step"><?= te('delivery.process.3') ?></div>
            <div class="step"><?= te('delivery.process.4') ?></div>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('delivery.regions.title') ?></h2>
        <article class="card">
            <h3><?= te('delivery.regions.1.h') ?></h3>
            <p><?= te('delivery.regions.1.text') ?></p>
        </article>
    </div>
</section>

<section>
    <div class="container">
        <article class="card">
            <h3><?= te('delivery.regions.2.h') ?></h3>
            <p><?= te('delivery.regions.2.text') ?></p>
        </article>
    </div>
</section>

<aside class="aside">
    <div class="container">
        <h3><?= te('delivery.included.title') ?></h3>
        <ul>
            <li><?= te('delivery.included.1') ?></li>
            <li><?= te('delivery.included.2') ?></li>
            <li><?= te('delivery.included.3') ?></li>
            <li><?= te('delivery.included.4') ?></li>
            <li><?= te('delivery.included.5') ?></li>
        </ul>
    </div>
</aside>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
