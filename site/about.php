<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
$page_title = t('about.title');
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1><?= te('about.hero.title') ?></h1>
        <p><?= te('about.hero.subtitle') ?></p>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('about.history.title') ?></h2>
        <article class="card">
            <h3><?= te('about.history.h') ?></h3>
            <p><?= te('about.history.text') ?></p>
        </article>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('about.mission.title') ?></h2>
        <article class="card">
            <h3><?= te('about.mission.h') ?></h3>
            <p><?= te('about.mission.text') ?></p>
        </article>
    </div>
</section>

<aside class="aside">
    <div class="container">
        <h3><?= te('about.facts.title') ?></h3>
        <ul>
            <li><?= te('about.facts.1') ?></li>
            <li><?= te('about.facts.2') ?></li>
            <li><?= te('about.facts.3') ?></li>
            <li><?= te('about.facts.4') ?></li>
        </ul>
    </div>
</aside>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
