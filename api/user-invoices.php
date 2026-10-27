<?php
/**
 * İstifadəçi ödəniş qəbzləri (invoice) API
 * GET  - istifadəçinin bütün qəbzlərini siyahı şəklində qaytarır
 * POST - sifariş üçün PDF/JPEG/PNG qəbzi yükləyir
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

// Fayl yolunu API-nin yerləşdiyi qovluğa görə nisbətən saxlayacağıq:
// /bb/uploads/receipts/...
$uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'receipts';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expecting multipart form-data
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    if ($orderId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Order ID is required']);
        exit;
    }

    // Check if order belongs to user and is confirmed (status = 1)
    $stmt = $pdo->prepare("SELECT id, total_amount, status FROM user_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        echo json_encode(['ok' => false, 'error' => 'Order not found']);
        exit;
    }

    $orderStatus = isset($order['status']) ? (int)$order['status'] : 0;
    if ($orderStatus !== 1) {
        echo json_encode(['ok' => false, 'error' => 'Receipts can only be uploaded for confirmed orders']);
        exit;
    }

    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'File upload error']);
        exit;
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid file']);
        exit;
    }

    // File size (maximum ~10 MB)
    $maxSize = 10 * 1024 * 1024;
    if (!isset($file['size']) || $file['size'] > $maxSize) {
        echo json_encode(['ok' => false, 'error' => 'File is too large (max. 10 MB)']);
        exit;
    }

    $origName = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowedExt, true)) {
        echo json_encode(['ok' => false, 'error' => 'Only PDF, JPG, JPEG and PNG files are allowed']);
        exit;
    }

    $fileType = $ext === 'pdf' ? 'pdf' : 'image';

    try {
        $random = bin2hex(random_bytes(4));
    } catch (Exception $e) {
        $random = (string)mt_rand(1000, 9999);
    }

    $baseName = 'r_' . $userId . '_' . $orderId . '_' . time() . '_' . $random . '.' . $ext;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $baseName;

    if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['ok' => false, 'error' => 'File could not be saved']);
        exit;
    }

    // Store relative path in the database
    $relativePath = 'uploads/receipts/' . $baseName;

    $amount = null;
    if (isset($_POST['amount']) && $_POST['amount'] !== '' && is_numeric($_POST['amount'])) {
        $amount = (float)$_POST['amount'];
    } else {
        $amount = $order['total_amount'] !== null ? (float)$order['total_amount'] : null;
    }
    if ($amount === null) {
        $amount = 0.0;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_payment_receipts (user_id, order_id, amount, file_path, file_type, status)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $orderId, $amount, $relativePath, $fileType]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'file' => $relativePath]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// GET - istifadəçinin bütün qəbzləri
$stmt = $pdo->prepare("
    SELECT r.id, r.order_id, r.amount, r.status, r.file_path, r.file_type, r.created_at, r.approved_at,
           o.name AS order_name, o.total_amount AS order_total
    FROM user_payment_receipts r
    INNER JOIN user_orders o ON o.id = r.order_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC, r.id DESC
");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list = [];
foreach ($rows as $r) {
    $list[] = [
        'id' => (int)$r['id'],
        'order_id' => (int)$r['order_id'],
        'order_name' => $r['order_name'],
        'order_total' => $r['order_total'] !== null ? (float)$r['order_total'] : null,
        'amount' => isset($r['amount']) ? (float)$r['amount'] : 0.0,
        'status' => isset($r['status']) ? (int)$r['status'] : 0, // 0 = pending, 1 = approved, 2 = rejected
        'file_path' => $r['file_path'],
        'file_type' => $r['file_type'],
        'created_at' => $r['created_at'],
        'approved_at' => $r['approved_at'],
    ];
}

echo json_encode(['ok' => true, 'receipts' => $list], JSON_UNESCAPED_UNICODE);

