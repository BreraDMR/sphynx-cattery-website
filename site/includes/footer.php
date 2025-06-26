</main>

<footer>
    <div class="container">
        <p><?= te('common.footer.company') ?></p>
        <p><?= te('common.footer.contacts') ?></p>
        <p><?= te('common.footer.hours') ?></p>
    </div>
</footer>

<?php
/**
 * Hand the active locale, CSRF token, auth flag and the handful of UI strings
 * that the client-side script (assets/js/script.js) renders to the browser.
 * Keeps translation in PHP (one source of truth) while letting the fetch-based
 * catalog/cart code stay localized.
 */
$__js = [
    'locale' => current_locale(),
    'loggedIn' => is_logged_in(),
    'csrf' => csrf_token(),
    't' => [
        'read_more' => t('catalog.read_more'),
        'purebred' => t('catalog.purebred'),
        'months' => t('catalog.months'),
        'catalog_empty' => t('catalog.empty'),
        'catalog_error' => t('catalog.load_error'),
        'treats_read_more' => t('treats.read_more'),
        'treats_empty' => t('treats.empty'),
        'treats_error' => t('treats.load_error'),
        'weight' => t('treats.weight'),
        'add_to_cart' => t('common.add_to_cart'),
        'login_to_buy' => t('common.login_to_buy'),
        'added' => t('cart.added'),
        'form_sending' => t('form.sending'),
        'form_error_conn' => t('form.error_conn'),
        'reviews_loading' => t('home.reviews.load'),
        'reviews_error' => t('catalog.load_error'),
        'greeting_morning' => t('home.hero.greeting.morning'),
        'greeting_day' => t('home.hero.greeting.day'),
        'greeting_evening' => t('home.hero.greeting.evening'),
        'greeting_price' => t('home.hero.greeting.price'),
        'calc_result' => t('home.calc.result'),
    ],
    'treatCategories' => [
        'snacks' => t('treats.cat.snacks'),
        'food' => t('treats.cat.food'),
        'vitamins' => t('treats.cat.vitamins'),
        'toys' => t('treats.cat.toys'),
        'care' => t('treats.cat.care'),
    ],
];
?>
<script>
window.APP = <?= json_encode($__js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>

</body>
</html>
