<?php
/**
 * Admin: settle company/user debts (full or partial).
 *
 * POST JSON:
 * {
 *   "user_id": 123,
 *   "mode": "full" | "partial",
 *   "amount": 100.0 // only for partial
 * }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';

auth_start_session();
// Debts settle əməliyyatları üçün admin və admin assistant icazəlidir
auth_require_admin_or_assistant_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
}

$userId  = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$mode    = isset($input['mode']) ? (string)$input['mode'] : '';
$amount  = isset($input['amount']) ? (float)$input['amount'] : 0.0;
$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid user_id']);
    exit;
}

if (!in_array($mode, ['full', 'partial'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid mode']);
    exit;
}

if ($mode === 'partial' && $amount <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Amount must be greater than zero']);
    exit;
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error']);
    exit;
}

// Ensure user exists
$stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}

// Fetch confirmed orders for this user
$stmt = $pdo->prepare("
    SELECT id, COALESCE(total_amount, 0) AS total_amount
    FROM user_orders
    WHERE user_id = ? AND status = 1
    ORDER BY created_at ASC, id ASC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$orders) {
    echo json_encode(['ok' => false, 'error' => 'User has no confirmed orders']);
    exit;
}

// Already paid amounts per order
$stmt = $pdo->prepare("
    SELECT order_id, SUM(amount) AS paid_amount
    FROM user_payment_receipts
    WHERE user_id = ? AND status = 1
    GROUP BY order_id
");
$stmt->execute([$userId]);
$paidRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$paidMap = [];
foreach ($paidRows as $r) {
    $paidMap[(int)$r['order_id']] = (float)($r['paid_amount'] ?? 0.0);
}

// Compute remaining per order and overall
$remainingPerOrder = [];
$remainingTotal = 0.0;
foreach ($orders as $o) {
    $oid = (int)$o['id'];
    $total = (float)$o['total_amount'];
    $alreadyPaid = $paidMap[$oid] ?? 0.0;
    $rem = $total - $alreadyPaid;
    if ($rem < 0) {
        $rem = 0.0;
    }
    $remainingPerOrder[$oid] = $rem;
    $remainingTotal += $rem;
}

// If a specific order_id is provided, restrict remainingTotal to that order only
if ($orderId > 0) {
    $remainingTotal = $remainingPerOrder[$orderId] ?? 0.0;
}

if ($remainingTotal <= 0) {
    echo json_encode(['ok' => false, 'error' => 'User has no remaining debt for selected scope']);
    exit;
}

if ($mode === 'partial' && $amount > $remainingTotal + 0.00001) {
    echo json_encode(['ok' => false, 'error' => 'Amount exceeds remaining debt']);
    exit;
}

try {
    $pdo->beginTransaction();

    $toAllocate = ($mode === 'full') ? $remainingTotal : $amount;

    // Əgər konkret bir sifariş üçün (order_id) "Mark paid" gəlirsə,
    // əvvəlcə həmin sifarişə aid pending qəbzləri (status = 0) APPROVED (status = 1) kimi işarələyək.
    if ($orderId > 0 && $toAllocate > 0) {
        $stmt = $pdo->prepare("
            SELECT id, amount
            FROM user_payment_receipts
            WHERE user_id = ? AND order_id = ? AND status = 0
            ORDER BY created_at ASC, id ASC
        ");
        $stmt->execute([$userId, $orderId]);
        $pendingReceipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingReceipts as $pr) {
            if ($toAllocate <= 0) {
                break;
            }
            $rid = (int)$pr['id'];
            $rAmount = isset($pr['amount']) ? (float)$pr['amount'] : 0.0;
            if ($rAmount <= 0) {
                continue;
            }

            // Bu qəbzi tam şəkildə approve edirik.
            $stmt = $pdo->prepare("
                UPDATE user_payment_receipts
                SET status = 1, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$rid]);

            $toAllocate -= $rAmount;
        }
    }

    // Hələ də toAllocate > 0 qalıbsa (ya pending qəbz yox idi, ya da kifayət etmədi),
    // qalan məbləğ üçün əvvəlki kimi manual approved qəbzlər yaradırıq.
    foreach ($orders as $o) {
        if ($toAllocate <= 0) {
            break;
        }
        $oid = (int)$o['id'];
        // If orderId is specified, skip other orders
        if ($orderId > 0 && $oid !== $orderId) {
            continue;
        }
        $rem = $remainingPerOrder[$oid] ?? 0.0;
        if ($rem <= 0) {
            continue;
        }
        $pay = $toAllocate < $rem ? $toAllocate : $rem;
        if ($pay <= 0) {
            continue;
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_payment_receipts (user_id, order_id, amount, file_path, file_type, status)
            VALUES (?, ?, ?, '', 'manual', 1)
        ");
        $stmt->execute([$userId, $oid, $pay]);

        $toAllocate -= $pay;
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

