<?php

declare(strict_types=1);

use App\RequestRepository;
use App\RequestStatus;

require_once __DIR__ . '/config/bootstrap.php';
require_admin();

$repo = new RequestRepository($pdo);

$statusFilter = $_GET['status'] ?? null;
if ($statusFilter !== null && !RequestStatus::isValid($statusFilter)) {
    $statusFilter = null;
}

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = $repo->count($statusFilter);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$requests = $repo->all($statusFilter, $perPage, ($page - 1) * $perPage);

function status_query(?string $status, int $page): string
{
    $params = ['page' => $page];
    if ($status !== null) {
        $params['status'] = $status;
    }
    return '?' . http_build_query($params);
}

$page_title = t('requests.admin.title');
include __DIR__ . '/includes/header.php';
?>

<h2 class="section-title"><?= te('requests.admin.title') ?></h2>
<p><a href="logout.php"><?= te('nav.logout') ?></a></p>

<div class="status-filters">
    <a href="<?= htmlspecialchars(status_query(null, 1)) ?>" class="button <?= $statusFilter === null ? 'active' : '' ?>"><?= te('requests.all') ?> (<?= $repo->count() ?>)</a>
    <?php foreach (RequestStatus::all() as $status): ?>
        <a href="<?= htmlspecialchars(status_query($status, 1)) ?>" class="button <?= $statusFilter === $status ? 'active' : '' ?>">
            <?= htmlspecialchars(request_status_label($status)) ?> (<?= $repo->count($status) ?>)
        </a>
    <?php endforeach; ?>
</div>

<?php if ($total === 0): ?>
    <div class="card">
        <p><?= te('requests.empty') ?></p>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th><?= te('contacts.form.name') ?></th>
                    <th><?= te('contacts.form.email') ?></th>
                    <th><?= te('contacts.form.phone') ?></th>
                    <th><?= te('contacts.form.message') ?></th>
                    <th><?= te('auth.account.role') ?></th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r->id ?></td>
                        <td><?= htmlspecialchars($r->name) ?></td>
                        <td><?= htmlspecialchars($r->email) ?></td>
                        <td><?= htmlspecialchars($r->phone ?? '—') ?></td>
                        <td><?= nl2br(htmlspecialchars($r->message)) ?></td>
                        <td><span class="status-badge status-<?= htmlspecialchars($r->status) ?>"><?= htmlspecialchars(request_status_label($r->status)) ?></span></td>
                        <td><?= htmlspecialchars($r->createdAt) ?></td>
                        <td><a href="edit_request.php?id=<?= $r->id ?>" class="button">✎</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= htmlspecialchars(status_query($statusFilter, $p)) ?>" class="button <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
