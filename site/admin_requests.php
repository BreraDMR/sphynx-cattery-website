<?php
require_once __DIR__ . '/config/auth.php';
require_admin();
require_once __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';

$stmt = $pdo->query("SELECT * FROM requests ORDER BY id DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="section-title">Адмін-панель: заявки з бази даних</h2>
<p><a href="logout.php">Вийти</a></p>

<?php if (count($requests) === 0): ?>
    <div class="card">
        <p>Поки що немає жодної заявки в таблиці <strong>requests</strong>.</p>
        <p>Підказка для СРС‑6: імпортуй файл <strong>database.sql</strong> у phpMyAdmin, щоб створити таблицю і додати тестові записи.</p>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ім’я</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Повідомлення</th>
                    <th>Дата</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($r['id'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($r['phone'] ?? '—')) ?></td>
                        <td><?= nl2br(htmlspecialchars((string)($r['message'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

