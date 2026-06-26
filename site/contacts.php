<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
$page_title = t('contacts.title');
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1><?= te('contacts.hero.title') ?></h1>
        <p><?= te('contacts.hero.subtitle') ?></p>
    </div>
</section>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('contacts.form.title') ?></h2>

        <form id="contactForm" class="contact-form">
            <label><?= te('contacts.form.name') ?> <span>*</span></label>
            <input type="text" id="name" name="name" required>

            <label><?= te('contacts.form.email') ?> <span>*</span></label>
            <input type="email" id="email" name="email" required>

            <label><?= te('contacts.form.phone') ?> <span>*</span></label>
            <input type="tel" id="phone" name="phone" required>

            <label><?= te('contacts.form.age') ?></label>
            <select id="age" name="age">
                <option value=""><?= te('contacts.form.age.choose') ?></option>
                <option value="2-3"><?= te('contacts.form.age.2_3') ?></option>
                <option value="4-6"><?= te('contacts.form.age.4_6') ?></option>
            </select>

            <label><?= te('contacts.form.color') ?></label>
            <input type="text" id="color" name="color">

            <label><?= te('contacts.form.message') ?></label>
            <textarea id="message" name="message" rows="5"></textarea>

            <label>
                <input type="checkbox" id="agreement" name="agreement" required>
                <?= te('contacts.form.consent') ?>
            </label>

            <button type="submit" class="button"><?= te('contacts.form.submit') ?></button>
        </form>

        <p id="formMessage" class="form-message"></p>
    </div>
</section>

<section class="contacts-section">
    <div class="container">
        <h2 class="section-title"><?= te('contacts.managers.title') ?></h2>
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
