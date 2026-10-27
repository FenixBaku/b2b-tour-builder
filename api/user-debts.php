<?php
/**
 * İstifadəçi borcları (Debts) API
 * Təsdiqlənmiş sifarişləri və onlara görə ödənişləri (qəbzlər) xülasə formasında qaytarır.
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';

auth_start_session();
if (!auth_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authentication required']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authentication required']);
    exit;
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database error']);
    exit;
}

// 1) Təsdiqlənmiş sifarişlər (status = 1) – bunlar borc hesab olunur
$stmt = $pdo->prepare("
    SELECT id, name, total_amount, status, created_at
    FROM user_orders
    WHERE user_id = ? AND status = 1
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2) Bu istifadəçiyə aid bütün qəbzlərin (invoice) xülasəsi
$stmt2 = $pdo->prepare("
    SELECT order_id,
           SUM(CASE WHEN status = 0 THEN amount ELSE 0 END) AS pending_amount,
           SUM(CASE WHEN status = 1 THEN amount ELSE 0 END) AS paid_amount
    FROM user_payment_receipts
    WHERE user_id = ?
    GROUP BY order_id
");
$stmt2->execute([$userId]);
$receiptRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$receiptMap = [];
foreach ($receiptRows as $r) {
    $oid = (int)$r['order_id'];
    $receiptMap[$oid] = [
        'pending' => isset($r['pending_amount']) ? (float)$r['pending_amount'] : 0.0,
        'paid' => isset($r['paid_amount']) ? (float)$r['paid_amount'] : 0.0,
    ];
}

$debts = [];
// İstifadəçi üçün real qalan borc (confirmed orders - approved payments)
$totalDebt = 0.0;
$pendingTotal = 0.0;
$paidTotal = 0.0;

foreach ($orders as $o) {
    $id = (int)$o['id'];
    $orderTotal = $o['total_amount'] !== null ? (float)$o['total_amount'] : 0.0;

    $agg = $receiptMap[$id] ?? ['pending' => 0.0, 'paid' => 0.0];
    $pending = $agg['pending'];
    $paid = $agg['paid'];

    // Həmin sifariş üçün qalan borc = ümumi məbləğ - təsdiqlənmiş ödənişlər
    $outstanding = $orderTotal - $paid;
    if ($outstanding < 0) {
        $outstanding = 0.0;
    }

    $totalDebt += $outstanding;
    $pendingTotal += $pending;
    $paidTotal += $paid;

    $debts[] = [
        'order_id' => $id,
        'name' => $o['name'],
        // Frontend-də "Debt" kimi göstərilən rəqəm artıq qalan borcdur
        'total_amount' => $outstanding,
        'created_at' => $o['created_at'],
        'pending_amount' => $pending,
        'paid_amount' => $paid,
        // İstəyiniz olarsa, tam sifariş məbləğini ayrıca göstərmək üçün saxlayırıq
        'original_total_amount' => $orderTotal,
    ];
}

echo json_encode([
    'ok' => true,
    'summary' => [
        // Ümumi qalan borc (confirmed orders - approved receipts)
        'total_debt' => $totalDebt,
        // Müştərinin yüklədiyi, amma admin tərəfindən hələ təsdiqlənməmiş qəbzlərin məbləği
        'pending_payments' => $pendingTotal,
        // Admin tərəfindən təsdiqlənmiş ödənişlərin ümumi məbləği
        'paid_total' => $paidTotal,
    ],
    'debts' => $debts,
], JSON_UNESCAPED_UNICODE);

