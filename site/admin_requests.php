<?php

declare(strict_types=1);

use App\RequestRepository;
use App\RequestStatus;

require_once __DIR__ . '/config/auth.php';
require_admin();
require_once __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';

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
?>

<h2 class="section-title">Адмін-панель: заявки з бази даних</h2>
<p><a href="logout.php">Вийти</a></p>

<div class="status-filters">
    <a href="<?= htmlspecialchars(status_query(null, 1)) ?>" class="button <?= $statusFilter === null ? 'active' : '' ?>">Всі (<?= $repo->count() ?>)</a>
    <?php foreach (RequestStatus::all() as $status): ?>
        <a href="<?= htmlspecialchars(status_query($status, 1)) ?>" class="button <?= $statusFilter === $status ? 'active' : '' ?>">
            <?= htmlspecialchars(RequestStatus::ukrainianLabel($status)) ?> (<?= $repo->count($status) ?>)
        </a>
    <?php endforeach; ?>
</div>

<?php if ($total === 0): ?>
    <div class="card">
        <p>Поки що немає жодної заявки в таблиці <strong>requests</strong><?= $statusFilter !== null ? ' зі статусом «' . htmlspecialchars(RequestStatus::ukrainianLabel($statusFilter)) . '»' : '' ?>.</p>
        <p>Підказка для СРС‑6: імпортуй файл <strong>database.sql</strong> у phpMyAdmin, щоб створити таблицю і додати тестові записи.</p>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ім'я</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Повідомлення</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
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
                        <td><span class="status-badge status-<?= htmlspecialchars($r->status) ?>"><?= htmlspecialchars(RequestStatus::ukrainianLabel($r->status)) ?></span></td>
                        <td><?= htmlspecialchars($r->createdAt) ?></td>
                        <td><a href="edit_request.php?id=<?= $r->id ?>" class="button">Редагувати</a></td>
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
