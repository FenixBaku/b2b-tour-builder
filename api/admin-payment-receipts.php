<?php
/**
 * Admin: user payment receipts management
 *
 * GET  - list of receipts (filter by status)
 * POST - change receipt status (approve / reject)
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';

auth_start_session();
// Payment receipts (Invoices) idarəsi üçün admin və admin assistant icazəlidir
auth_require_admin_or_assistant_api();

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $action = isset($input['action']) ? (string)$input['action'] : '';

    if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid id or action']);
        exit;
    }

    $newStatus = $action === 'approve' ? 1 : 2; // 1 = approved, 2 = rejected

    try {
        $stmt = $pdo->prepare("UPDATE user_payment_receipts SET status = ?, approved_at = (CASE WHEN ? = 1 THEN NOW() ELSE approved_at END) WHERE id = ?");
        $stmt->execute([$newStatus, $newStatus, $id]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// GET
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;

$sql = "
    SELECT r.id, r.user_id, r.order_id, r.amount, r.status, r.file_path, r.file_type, r.created_at, r.approved_at,
           u.email AS user_email, u.name AS user_name,
           o.name AS order_name, o.total_amount AS order_total
    FROM user_payment_receipts r
    INNER JOIN users u ON u.id = r.user_id
    INNER JOIN user_orders o ON o.id = r.order_id
";
$params = [];
$where = [];
if ($status !== null) {
    $where[] = "r.status = ?";
    $params[] = $status;
}
if ($userId !== null && $userId > 0) {
    $where[] = "u.id = ?";
    $params[] = $userId;
}
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where) . " ";
}
$sql .= " ORDER BY r.created_at DESC, r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list = [];
foreach ($rows as $r) {
    $list[] = [
        'id' => (int)$r['id'],
        'user_id' => (int)$r['user_id'],
        'user_name' => $r['user_name'],
        'user_email' => $r['user_email'],
        'order_id' => (int)$r['order_id'],
        'order_name' => $r['order_name'],
        'order_total' => $r['order_total'] !== null ? (float)$r['order_total'] : null,
        'amount' => isset($r['amount']) ? (float)$r['amount'] : 0.0,
        'status' => isset($r['status']) ? (int)$r['status'] : 0,
        'file_path' => $r['file_path'],
        'file_type' => $r['file_type'],
        'created_at' => $r['created_at'],
        'approved_at' => $r['approved_at'],
    ];
}

echo json_encode(['ok' => true, 'receipts' => $list], JSON_UNESCAPED_UNICODE);

