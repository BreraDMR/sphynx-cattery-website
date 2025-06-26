<?php

declare(strict_types=1);

use App\RequestRepository;

require_once __DIR__ . '/config/bootstrap.php';
require_admin();

$repo = new RequestRepository($pdo);
$requests = $repo->all();

$page_title = t('requests.crud.title');
include __DIR__ . '/includes/header.php';
?>

<h2 class="section-title"><?= te('requests.crud.title') ?></h2>
<a href="create_request.php" class="button"><?= te('requests.add') ?></a>
<a href="logout.php" class="button"><?= te('nav.logout') ?></a>

<?php if (count($requests) > 0): ?>
    <div class="requests-grid">
        <?php foreach ($requests as $r): ?>
            <div class="card">
                <h3><?= htmlspecialchars($r->name) ?></h3>
                <p><strong><?= te('contacts.form.email') ?>:</strong> <?= htmlspecialchars($r->email) ?></p>
                <p><strong><?= te('contacts.form.phone') ?>:</strong> <?= htmlspecialchars($r->phone ?? '—') ?></p>
                <p><strong><?= te('contacts.form.message') ?>:</strong><br><?= nl2br(htmlspecialchars($r->message)) ?></p>
                <p><span class="status-badge status-<?= htmlspecialchars($r->status) ?>"><?= htmlspecialchars(request_status_label($r->status)) ?></span></p>
                <small><?= htmlspecialchars($r->createdAt) ?></small><br><br>

                <a href="edit_request.php?id=<?= $r->id ?>" class="button">✎</a>
                <form method="POST" action="delete_request.php" style="display:inline"
                      onsubmit="return confirm('?')">
                    <input type="hidden" name="id" value="<?= $r->id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="delete-link"><?= te('cart.remove') ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p><?= te('requests.empty') ?></p>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
