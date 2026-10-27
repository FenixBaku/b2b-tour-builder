<?php
/**
 * İstifadəçi sifarişləri API – yadda saxla, siyahı
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../lib/order_pricing.php';
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = isset($input['name']) ? mb_substr(trim((string)$input['name']), 0, 255) : null;
    $orderData = isset($input['order_data']) ? $input['order_data'] : null;
    if (is_string($orderData)) $orderData = json_decode($orderData, true);
    $clientTotal = isset($input['total_amount']) && is_numeric($input['total_amount']) ? (float)$input['total_amount'] : null;

    if (!is_array($orderData)) {
        echo json_encode(['ok' => false, 'error' => '\'order_data\' is required']);
        exit;
    }

    // Qiymət serverdə hesablanır: brauzerin göndərdiyi cəm borc/faktura üçün istifadə olunmur
    $pricing = order_compute_total($pdo, $orderData);
    $serverTotal = $pricing['total'];
    $mismatch = $clientTotal !== null && abs($clientTotal - $serverTotal) > 0.01;

    if (($input['action'] ?? '') === 'quote') {
        echo json_encode(['ok' => true, 'total_amount' => $serverTotal, 'per_person' => $pricing['perPerson'], 'lines' => $pricing['lines'], 'price_mismatch' => $mismatch], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $orderData['pricing'] = [
        'server_total' => $serverTotal,
        'client_total' => $clientTotal,
        'lines' => $pricing['lines'],
        'calculated_at' => date('c'),
    ];
    $json = json_encode($orderData, JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $pdo->prepare("INSERT INTO user_orders (user_id, name, order_data, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $json, $serverTotal]);
        if ($mismatch) {
            error_log("Order price mismatch: user {$userId}, client {$clientTotal}, server {$serverTotal}");
        }
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'total_amount' => $serverTotal, 'price_mismatch' => $mismatch]);
    } catch (Exception $e) {
        error_log('user-orders insert failed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => 'Order could not be saved']);
    }
    exit;
}

// GET - tək sifariş (?id=123) və ya bütün siyahı
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId > 0) {
    $stmt = $pdo->prepare("SELECT id, name, order_data, total_amount, status, created_at FROM user_orders WHERE user_id = ? AND id = ?");
    $stmt->execute([$userId, $orderId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Order not found']);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'order' => [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'total_amount' => $r['total_amount'] !== null ? (float)$r['total_amount'] : null,
            'status' => isset($r['status']) ? (int)$r['status'] : 0,
            'created_at' => $r['created_at'],
            'order_data' => json_decode($r['order_data'], true),
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, order_data, total_amount, status, created_at FROM user_orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list = [];
foreach ($rows as $r) {
    $list[] = [
        'id' => (int)$r['id'],
        'name' => $r['name'],
        'total_amount' => $r['total_amount'] !== null ? (float)$r['total_amount'] : null,
        'status' => isset($r['status']) ? (int)$r['status'] : 0,
        'created_at' => $r['created_at'],
        'order_data' => json_decode($r['order_data'], true),
    ];
}

echo json_encode(['ok' => true, 'orders' => $list], JSON_UNESCAPED_UNICODE);
