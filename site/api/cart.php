<?php

declare(strict_types=1);

/**
 * api/cart.php — JSON cart actions for the signed-in user.
 *
 *   POST {action:'add',    item_type, item_id, qty?}
 *   POST {action:'update', cart_item_id, qty}
 *   POST {action:'remove', cart_item_id}
 *
 * All actions require a logged-in session and a valid CSRF token (sent in the
 * JSON body as `csrf`, taken from window.APP.csrf). Returns the new cart
 * item-count so the header badge can update without a reload.
 */

use App\CartRepository;
use App\CatRepository;
use App\TreatRepository;

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function cart_json(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = current_user();
if ($user === null) {
    cart_json(['status' => 'error', 'message' => t('cart.login_required')], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cart_json(['status' => 'error', 'message' => 'Method not supported.'], 405);
}

$data = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!csrf_verify($data['csrf'] ?? null)) {
    cart_json(['status' => 'error', 'message' => t('auth.error.csrf')], 419);
}

$cart = new CartRepository($pdo);
$action = (string) ($data['action'] ?? '');

switch ($action) {
    case 'add':
        $type = (string) ($data['item_type'] ?? '');
        $itemId = (int) ($data['item_id'] ?? 0);
        $qty = max(1, (int) ($data['qty'] ?? 1));

        if ($type === CartRepository::TYPE_CAT) {
            $item = (new CatRepository($pdo))->find($itemId);
        } elseif ($type === CartRepository::TYPE_TREAT) {
            $item = (new TreatRepository($pdo))->find($itemId);
        } else {
            cart_json(['status' => 'error', 'message' => 'Unknown item type.'], 422);
        }

        if ($item === null || $item->status !== 'published') {
            cart_json(['status' => 'error', 'message' => 'Item not found.'], 404);
        }

        $cart->add($user->id, $type, $itemId, $qty);
        break;

    case 'update':
        $cart->updateQty($user->id, (int) ($data['cart_item_id'] ?? 0), (int) ($data['qty'] ?? 0));
        break;

    case 'remove':
        $cart->remove($user->id, (int) ($data['cart_item_id'] ?? 0));
        break;

    default:
        cart_json(['status' => 'error', 'message' => 'Unknown action.'], 422);
}

cart_json(['status' => 'success', 'count' => $cart->count($user->id)]);
