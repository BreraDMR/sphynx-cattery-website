<?php

declare(strict_types=1);

use App\BotNotifier;
use App\CartRepository;
use App\CatRepository;
use App\RequestRepository;
use App\TreatRepository;

require_once __DIR__ . '/config/bootstrap.php';
require_login();

$user = current_user();
$cart = new CartRepository($pdo);
$cats = new CatRepository($pdo);
$treats = new TreatRepository($pdo);

/**
 * Resolve raw cart rows to display lines (product card + line total). Rows
 * whose product no longer exists (deleted from the catalog) are skipped.
 *
 * @return array{lines: array<int, array<string, mixed>>, total: int}
 */
function build_cart_view(CartRepository $cart, CatRepository $cats, TreatRepository $treats, int $userId): array
{
    $lines = [];
    $total = 0;

    foreach ($cart->items($userId) as $row) {
        $product = $row['item_type'] === CartRepository::TYPE_CAT
            ? $cats->find($row['item_id'])
            : $treats->find($row['item_id']);

        if ($product === null) {
            continue;
        }

        $lineSum = $product->priceEur * $row['qty'];
        $total += $lineSum;

        $lines[] = [
            'cart_item_id' => $row['id'],
            'type' => $row['item_type'],
            'name' => $product->name,
            'photo' => $product->toArray()['photo'],
            'price' => $product->priceEur,
            'qty' => $row['qty'],
            'sum' => $lineSum,
        ];
    }

    return ['lines' => $lines, 'total' => $total];
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $flash = t('auth.error.csrf');
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'remove') {
            $cart->remove($user->id, (int) ($_POST['cart_item_id'] ?? 0));
        } elseif ($action === 'update') {
            $cart->updateQty($user->id, (int) ($_POST['cart_item_id'] ?? 0), (int) ($_POST['qty'] ?? 0));
        } elseif ($action === 'checkout') {
            $view = build_cart_view($cart, $cats, $treats, $user->id);
            if ($view['lines'] !== []) {
                $summaryLines = array_map(
                    static fn (array $l): string => "{$l['qty']}× {$l['name']} ({$l['type']}) — {$l['sum']} €",
                    $view['lines']
                );
                $message = "Order from the website cart:\n" . implode("\n", $summaryLines) . "\nTotal: {$view['total']} €";

                $repo = new RequestRepository($pdo);
                $newId = $repo->create($user->name, $user->email, null, null, null, $message, true);

                $newRequest = $repo->find($newId);
                if ($newRequest !== null) {
                    BotNotifier::notifyNewRequest($newRequest);
                }

                $cart->clear($user->id);
                $_SESSION['flash_cart'] = t('cart.checkout.done');
            }
        }

        // Post/Redirect/Get so a refresh doesn't repeat the action.
        header('Location: cart.php');
        exit;
    }
}

if (!empty($_SESSION['flash_cart'])) {
    $flash = (string) $_SESSION['flash_cart'];
    unset($_SESSION['flash_cart']);
}

$view = build_cart_view($cart, $cats, $treats, $user->id);

$page_title = t('cart.title') . ' • ' . t('common.brand');
require_once __DIR__ . '/includes/header.php';
?>

<section>
    <div class="container">
        <h2 class="section-title"><?= te('cart.title') ?></h2>

        <?php if ($flash !== ''): ?>
            <p class="form-message" style="color:green;"><?= htmlspecialchars($flash) ?></p>
        <?php endif; ?>

        <?php if ($view['lines'] === []): ?>
            <p class="text-center"><?= te('cart.empty') ?></p>
            <p class="text-center"><a href="index.php#catalog" class="button"><?= te('cart.empty.cta') ?></a></p>
        <?php else: ?>
            <div class="card table-card">
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th><?= te('cart.item') ?></th>
                            <th><?= te('cart.price') ?></th>
                            <th><?= te('cart.qty') ?></th>
                            <th><?= te('cart.sum') ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($view['lines'] as $line): ?>
                            <tr>
                                <td>
                                    <span class="status-badge"><?= te('cart.type.' . $line['type']) ?></span>
                                    <?= htmlspecialchars($line['name']) ?>
                                </td>
                                <td><?= $line['price'] ?> €</td>
                                <td>
                                    <?php if ($line['type'] === CartRepository::TYPE_TREAT): ?>
                                        <form method="POST" style="display:flex; gap:6px; align-items:center; margin:0; max-width:160px;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_item_id" value="<?= $line['cart_item_id'] ?>">
                                            <input type="number" name="qty" value="<?= $line['qty'] ?>" min="1" max="99" style="width:70px; margin:0;">
                                            <button type="submit" class="button" style="padding:6px 10px;">✓</button>
                                        </form>
                                    <?php else: ?>
                                        <?= $line['qty'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $line['sum'] ?> €</td>
                                <td>
                                    <form method="POST" style="margin:0;" onsubmit="return true;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_item_id" value="<?= $line['cart_item_id'] ?>">
                                        <button type="submit" class="delete-link"><?= te('cart.remove') ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="price-highlight" style="text-align:right; font-size:24px; margin-top:20px;"><?= te('cart.total', ['sum' => $view['total']]) ?></p>
            <p style="text-align:right; opacity:.8;"><?= te('cart.checkout.note') ?></p>

            <form method="POST" style="text-align:right; max-width:none;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="action" value="checkout">
                <button type="submit" class="button delivery-price-btn"><?= te('cart.checkout') ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
