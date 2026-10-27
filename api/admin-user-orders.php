<?php
/**
 * Admin: per-user orders & payments overview
 *
 * GET /api/admin-user-orders.php?user_id=123
 *   -> confirmed orders (status = 1) for this user
 *   -> sums of approved and pending receipts for each order
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';

auth_start_session();
// Seçilmiş istifadəçinin sifarişləri və ödənişlərini görmək üçün admin və admin assistant icazəlidir
auth_require_admin_or_assistant_api();

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'user_id is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1) Confirmed orders for this user
    $stmt = $pdo->prepare("
        SELECT id, name, COALESCE(total_amount, 0) AS total_amount, status, created_at
        FROM user_orders
        WHERE user_id = ? AND status = 1
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orders) {
        echo json_encode(['ok' => true, 'orders' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $orderIds = array_column($orders, 'id');

    // 2) Receipts (pending/paid) per order for this user
    $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $params = $orderIds;
    array_unshift($params, $userId); // first param is user_id

    $stmt = $pdo->prepare("
        SELECT order_id,
               SUM(CASE WHEN status = 0 THEN amount ELSE 0 END) AS pending_amount,
               SUM(CASE WHEN status = 1 THEN amount ELSE 0 END) AS paid_amount
        FROM user_payment_receipts
        WHERE user_id = ? AND order_id IN ($inPlaceholders)
        GROUP BY order_id
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $payMap = [];
    foreach ($rows as $r) {
        $oid = (int)$r['order_id'];
        $payMap[$oid] = [
            'pending' => isset($r['pending_amount']) ? (float)$r['pending_amount'] : 0.0,
            'paid'    => isset($r['paid_amount']) ? (float)$r['paid_amount'] : 0.0,
        ];
    }

    $result = [];
    foreach ($orders as $o) {
        $oid = (int)$o['id'];
        $total = (float)$o['total_amount'];
        $agg = $payMap[$oid] ?? ['pending' => 0.0, 'paid' => 0.0];
        $paid = $agg['paid'];
        $pending = $agg['pending'];
        $remaining = $total - $paid;
        if ($remaining < 0) {
            $remaining = 0.0;
        }
        $result[] = [
            'order_id'        => $oid,
            'name'            => $o['name'],
            'total_amount'    => $total,
            'created_at'      => $o['created_at'],
            'paid_amount'     => $paid,
            'pending_amount'  => $pending,
            'remaining_amount'=> $remaining,
        ];
    }

    echo json_encode(['ok' => true, 'orders' => $result], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

