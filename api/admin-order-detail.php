<?php
/**
 * Admin: order details (order_data / itinerary) – admin only.
 * Also returns optional debts summary when ?debts=1 is provided.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';

auth_start_session();
// Order detalları və Debts overview üçün admin və admin assistant icazəlidir
auth_require_admin_or_assistant_api();

// Debts overview mode
if (isset($_GET['debts']) && (int)$_GET['debts'] === 1) {
    $pdo = dbConnect();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Database error']);
        exit;
    }

    // 1) All users (companies) list
    $stmt = $pdo->query("
        SELECT id, email, name, company
        FROM users
        ORDER BY id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Confirmed orders totals per user
    $stmt = $pdo->query("
        SELECT user_id,
               SUM(CASE WHEN status = 1 THEN COALESCE(total_amount, 0) ELSE 0 END) AS total_debt
        FROM user_orders
        GROUP BY user_id
    ");
    $orderSums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orderMap = [];
    foreach ($orderSums as $row) {
        $uid = (int)$row['user_id'];
        $orderMap[$uid] = (float)($row['total_debt'] ?? 0.0);
    }

    // 3) Receipts (pending/paid) per user (via orders)
    $stmt = $pdo->query("
        SELECT o.user_id,
               SUM(CASE WHEN r.status = 0 THEN r.amount ELSE 0 END) AS pending_amount,
               SUM(CASE WHEN r.status = 1 THEN r.amount ELSE 0 END) AS paid_amount
        FROM user_payment_receipts r
        INNER JOIN user_orders o ON o.id = r.order_id
        GROUP BY o.user_id
    ");
    $receiptSums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $receiptMap = [];
    foreach ($receiptSums as $row) {
        $uid = (int)$row['user_id'];
        $receiptMap[$uid] = [
            'pending' => isset($row['pending_amount']) ? (float)$row['pending_amount'] : 0.0,
            'paid'    => isset($row['paid_amount']) ? (float)$row['paid_amount'] : 0.0,
        ];
    }

    $debts = [];
    $totalOutstanding = 0.0;
    $pendingTotal = 0.0;
    $paidTotal = 0.0;

    foreach ($users as $u) {
        $uid = (int)$u['id'];
        $userDebt = $orderMap[$uid] ?? 0.0;
        $receiptAgg = $receiptMap[$uid] ?? ['pending' => 0.0, 'paid' => 0.0];

        $paid = $receiptAgg['paid'];
        $outstanding = $userDebt - $paid;
        if ($outstanding < 0) {
            $outstanding = 0.0;
        }

        $totalOutstanding += $outstanding;
        $pendingTotal += $receiptAgg['pending'];
        $paidTotal += $paid;

        $debts[] = [
            'user_id'        => $uid,
            'user_name'      => $u['name'],
            'user_email'     => $u['email'],
            'user_company'   => $u['company'],
            // total_amount burada outstanding (qalan borc) kimi istifadə olunur
            'total_amount'   => $outstanding,
            'pending_amount' => $receiptAgg['pending'],
            'paid_amount'    => $paid,
        ];
    }

    echo json_encode([
        'ok' => true,
        'summary' => [
            // ümumi qalan borc (total - paid)
            'total_debt' => $totalOutstanding,
            'pending'    => $pendingTotal,
            'paid'       => $paidTotal,
        ],
        'debts' => $debts,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Single order detail mode
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID is required']);
    exit;
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.name, o.total_amount, o.order_data, o.created_at,
           u.email AS user_email, u.name AS user_name
    FROM user_orders o
    INNER JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Order not found']);
    exit;
}

$orderData = null;
if (!empty($row['order_data'])) {
    $orderData = json_decode($row['order_data'], true);
}
$orderData = is_array($orderData) ? $orderData : null;

echo json_encode([
    'ok' => true,
    'order' => [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'total_amount' => $row['total_amount'] !== null ? (float)$row['total_amount'] : null,
        'created_at' => $row['created_at'],
        'user_name' => $row['user_name'],
        'user_email' => $row['user_email'],
        'order_data' => $orderData,
    ]
], JSON_UNESCAPED_UNICODE);
