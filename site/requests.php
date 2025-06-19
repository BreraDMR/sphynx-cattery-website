<?php
require_once 'config/auth.php';
require_admin();
require_once 'config/db.php';
include 'includes/header.php';
?>

<h2 class="section-title">Всі заявки клієнтів (CRUD)</h2>
<a href="create_request.php" class="button">+ Додати нову заявку</a>
<a href="logout.php" class="button">Вийти</a>

<?php
$stmt = $pdo->query("SELECT * FROM requests ORDER BY id DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (count($requests) > 0): ?>
    <div class="requests-grid">
        <?php foreach ($requests as $r): ?>
            <div class="card">
                <h3><?= htmlspecialchars($r['name']) ?></h3>
                <p><strong>Email:</strong> <?= htmlspecialchars($r['email']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($r['phone'] ?? '—') ?></p>
                <p><strong>Повідомлення:</strong><br><?= nl2br(htmlspecialchars($r['message'])) ?></p>
                <small>Дата: <?= htmlspecialchars($r['created_at']) ?></small><br><br>

                <a href="edit_request.php?id=<?= (int)$r['id'] ?>" class="button">Редагувати</a>
                <form method="POST" action="delete_request.php" style="display:inline"
                      onsubmit="return confirm('Ви дійсно хочете видалити цю заявку?')">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button type="submit" class="delete-link">Видалити</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Поки немає жодної заявки.</p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>