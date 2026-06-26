<?php

declare(strict_types=1);

/**
 * Home page. Was a static index.html with a hard-coded Ukrainian header; now
 * a localized PHP page sharing includes/header.php + footer.php so the nav,
 * auth state and language switch are consistent across the whole site.
 */

require_once __DIR__ . '/config/bootstrap.php';
$page_title = t('home.title');
require_once __DIR__ . '/includes/header.php';
?>

<!-- CATALOG (filterable, data from api/cats.php) -->
<section id="catalog" class="section--white">
    <div class="container">
        <h2 class="section-title"><?= te('catalog.title') ?></h2>

        <div class="text-center mb-30">
            <input type="text" id="searchInput" class="search-input" placeholder="<?= te('catalog.search') ?>">

            <div class="mt-15">
                <button class="button filter-btn active" data-color="all"><?= te('color.all') ?></button>
                <?php foreach (CAT_FILTER_COLORS as $__value => $__key): ?>
                    <button class="button filter-btn" data-color="<?= htmlspecialchars($__value) ?>"><?= te($__key) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="catsContainer" class="cats-container-grid"></div>
    </div>
</section>

<section class="hero">
    <div class="container">
        <h1><?= te('home.hero.title') ?></h1>
        <p><?= te('home.hero.subtitle') ?></p>
        <a href="contacts.php" class="button"><?= te('home.hero.cta') ?></a>
    </div>
</section>

<section class="services">
    <div class="container">
        <h2 class="section-title"><?= te('home.services.title') ?></h2>
        <div class="benefits">
            <div class="service-card card">
                <h3><?= te('home.services.1.title') ?></h3>
                <p><?= te('home.services.1.text') ?></p>
            </div>
            <div class="service-card card">
                <h3><?= te('home.services.2.title') ?></h3>
                <p><?= te('home.services.2.text') ?></p>
            </div>
            <div class="service-card card">
                <h3><?= te('home.services.3.title') ?></h3>
                <p><?= te('home.services.3.text') ?></p>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('home.about.title') ?></h2>
        <p class="about-lead"><?= te('home.about.lead') ?></p>
    </div>
</section>

<section class="section--white">
    <div class="container">
        <h2 class="section-title"><?= te('home.benefits.title') ?></h2>
        <div class="benefits">
            <div class="benefit-card card"><?= te('home.benefits.1') ?><br><strong><?= te('home.benefits.1.value') ?></strong></div>
            <div class="benefit-card card"><?= te('home.benefits.2') ?><br><strong><?= te('home.benefits.2.value') ?></strong></div>
            <div class="benefit-card card"><?= te('home.benefits.3') ?><br><strong><?= te('home.benefits.3.value') ?></strong></div>
        </div>
    </div>
</section>

<section class="text-center my-30">
    <div class="container">
        <h3 class="section-title" style="margin-bottom:15px;"><?= te('home.calc.title') ?></h3>
        <select id="deliveryRegion" class="search-input">
            <option value="0"><?= te('home.calc.region.cz') ?></option>
            <option value="80"><?= te('home.calc.region.near') ?></option>
            <option value="150"><?= te('home.calc.region.eu') ?></option>
        </select>
        <button onclick="showDeliveryPrice()" class="button delivery-price-btn"><?= te('home.calc.button') ?></button>
        <p id="deliveryPriceResult" class="price-highlight mt-15"></p>
    </div>
</section>

<section class="section--light">
    <div class="container">
        <h2 class="section-title"><?= te('home.process.title') ?></h2>
        <div class="steps">
            <div class="step"><?= te('home.process.1') ?></div>
            <div class="step"><?= te('home.process.2') ?></div>
            <div class="step"><?= te('home.process.3') ?></div>
            <div class="step"><?= te('home.process.4') ?></div>
        </div>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('home.testimonials.title') ?></h2>
        <div class="testimonials-row">
            <article class="card testimonial-card"><?= te('home.testimonials.1') ?></article>
            <article class="card testimonial-card"><?= te('home.testimonials.2') ?></article>
        </div>
    </div>
</section>

<section class="section--light">
    <div class="container">
        <h2 class="section-title"><?= te('home.reviews.title') ?></h2>
        <div id="reviewsContainer" class="reviews-grid"></div>
        <button id="loadReviewsBtn" class="button load-reviews-btn"><?= te('home.reviews.load') ?></button>
    </div>
</section>

<section class="contacts-section">
    <div class="container">
        <h2 class="section-title"><?= te('home.contacts.title') ?></h2>
        <div class="managers-row">
            <article class="card">
                <img src="assets/images/manager1.webp" alt="<?= te('home.managers.1.name') ?>" width="140" height="140" class="manager-avatar">
                <h3><?= te('home.managers.1.name') ?></h3>
                <p><?= te('home.managers.1.role') ?></p>
            </article>
            <article class="card">
                <img src="assets/images/manager2.webp" alt="<?= te('home.managers.2.name') ?>" width="140" height="140" class="manager-avatar">
                <h3><?= te('home.managers.2.name') ?></h3>
                <p><?= te('home.managers.2.role') ?></p>
            </article>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
