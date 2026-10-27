<?php
/**
 * Tour Builder məlumat API
 * GET: bütün konfiqurasiya veritabanından (vehicles transfers cədvəlindən, qiymətlər transfers-dan)
 * POST: admin əlavə et / sil
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';
require_once __DIR__ . '/tour_config_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_require_admin_api();
} else {
    auth_require_login_api();
}

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

$tableMap = [
    'countries' => ['table' => 'tour_countries', 'column' => 'name'],
    'visa' => ['table' => 'tour_visa_types', 'column' => 'name'],
    'vehicles' => ['table' => 'tour_vehicles', 'column' => 'name'],
    'water' => ['table' => 'tour_water_packages', 'column' => 'name'],
    'guides' => ['table' => 'tour_guides', 'column' => 'name'],
    'hotels' => ['table' => 'tour_hotels', 'column' => 'name'],
    'tours' => ['table' => 'tour_activities', 'column' => 'name'],
    'entrances' => ['table' => 'tour_entrances', 'column' => 'name'],
    'transfer-a' => ['table' => 'tour_transfer_point_a', 'column' => 'name'],
    'transfer-b' => ['table' => 'tour_transfer_point_b', 'column' => 'name'],
    'transfer-service' => ['table' => 'tour_transfer_services', 'column' => 'name'],
    // Extra services for whole trip (new table)
    'extraServices' => ['table' => 'tour_extra_services', 'column' => 'name'],
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $checkin = isset($_GET['checkin']) ? trim($_GET['checkin']) : null;
    $data = getTourConfig($pdo, $checkin);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';
$key = $input['key'] ?? '';
$value = isset($input['value']) ? trim((string)$input['value']) : '';

if (!in_array($action, ['add', 'remove', 'update'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    exit;
}

$oldValue = isset($input['oldValue']) ? trim((string)$input['oldValue']) : '';

$isMeal = (strpos($key, 'meals-') === 0);
$mealType = $isMeal ? substr($key, 6) : null;
if ($isMeal && !in_array($mealType, ['breakfast', 'lunch', 'dinner'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid meal type']);
    exit;
}
$isExtraService = ($key === 'extraServices');
$isGuide = ($key === 'guides');

if ($action === 'add') {
    if ($value === '') {
        echo json_encode(['ok' => false, 'error' => 'Value required']);
        exit;
    }
    if ($isMeal) {
        $city = city_code($input['city'] ?? '') ?: CITY_DEFAULT_CODE;
        if (isset($input['city']) && trim((string)$input['city']) !== '' && city_code($input['city']) === '') {
            echo json_encode(['ok' => false, 'error' => 'Naməlum şəhər: ' . $input['city']]);
            exit;
        }
        $price = isset($input['price']) ? (is_numeric($input['price']) ? (float) $input['price'] : null) : null;
        try {
            $stmt = $pdo->prepare("INSERT INTO tour_meal_options (city, meal_type, name, price, sort_order) SELECT ?, ?, ?, ?, COALESCE(MAX(sort_order),0)+1 FROM tour_meal_options WHERE city = ? AND meal_type = ?");
            $stmt->execute([$city, $mealType, $value, $price, $city, $mealType]);
        } catch (Exception $e) {
            $stmt = $pdo->prepare("INSERT INTO tour_meal_options (city, meal_type, name, sort_order) SELECT ?, ?, ?, COALESCE(MAX(sort_order),0)+1 FROM tour_meal_options WHERE city = ? AND meal_type = ?");
            $stmt->execute([$city, $mealType, $value, $city, $mealType]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($isExtraService) {
        $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
        $stmt = $pdo->prepare("INSERT INTO tour_extra_services (name, price, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order),0)+1 FROM tour_extra_services");
        $stmt->execute([$value, $price]);
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($isGuide) {
        $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
        try {
            $stmt = $pdo->prepare("INSERT INTO tour_guides (name, price, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order),0)+1 FROM tour_guides");
            $stmt->execute([$value, $price]);
        } catch (Exception $e) {
            // Köhnə strukturlu cədvəl üçün (price sütunu yoxdursa)
            $stmt = $pdo->prepare("INSERT IGNORE INTO tour_guides (name, sort_order) SELECT ?, COALESCE(MAX(sort_order),0)+1 FROM tour_guides");
            $stmt->execute([$value]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if (!isset($tableMap[$key])) {
        echo json_encode(['ok' => false, 'error' => 'Unknown key']);
        exit;
    }
    $t = $tableMap[$key];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `{$t['table']}` (`{$t['column']}`, sort_order) SELECT ?, COALESCE(MAX(sort_order),0)+1 FROM `{$t['table']}`");
    $stmt->execute([$value]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'update') {
    if ($value === '' || $oldValue === '') {
        echo json_encode(['ok' => false, 'error' => 'oldValue and value (newValue) required']);
        exit;
    }
    if ($isMeal) {
        $oldCity = city_code($input['oldCity'] ?? '') ?: CITY_DEFAULT_CODE;
        $city = isset($input['city']) && trim((string)$input['city']) !== '' ? city_code($input['city']) : $oldCity;
        if ($city === '') {
            echo json_encode(['ok' => false, 'error' => 'Naməlum şəhər: ' . $input['city']]);
            exit;
        }
        $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
        try {
            $stmt = $pdo->prepare("UPDATE tour_meal_options SET name = ?, city = ?, price = ? WHERE city IN (" . city_sql_placeholders($oldCity) . ") AND meal_type = ? AND name = ?");
            $stmt->execute(array_merge([$value, $city, $price], city_db_variants($oldCity), [$mealType, $oldValue]));
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit;
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($isExtraService) {
        $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
        $stmt = $pdo->prepare("UPDATE tour_extra_services SET name = ?, price = ? WHERE name = ?");
        $stmt->execute([$value, $price, $oldValue]);
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($isGuide) {
        $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
        try {
            $stmt = $pdo->prepare("UPDATE tour_guides SET name = ?, price = ? WHERE name = ?");
            $stmt->execute([$value, $price, $oldValue]);
        } catch (Exception $e) {
            // Əgər price sütunu yoxdursa, yalnız adı yenilə
            $stmt = $pdo->prepare("UPDATE tour_guides SET name = ? WHERE name = ?");
            $stmt->execute([$value, $oldValue]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
    if (!isset($tableMap[$key])) {
        echo json_encode(['ok' => false, 'error' => 'Unknown key']);
        exit;
    }
    $t = $tableMap[$key];
    try {
        $stmt = $pdo->prepare("UPDATE `{$t['table']}` SET `{$t['column']}` = ? WHERE `{$t['column']}` = ?");
        $stmt->execute([$value, $oldValue]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($isMeal) {
    $city = city_code($input['city'] ?? '');
    if ($city !== '') {
        $stmt = $pdo->prepare("DELETE FROM tour_meal_options WHERE city IN (" . city_sql_placeholders($city) . ") AND meal_type = ? AND name = ?");
        $stmt->execute(array_merge(city_db_variants($city), [$mealType, $value]));
    } else {
        $stmt = $pdo->prepare("DELETE FROM tour_meal_options WHERE meal_type = ? AND name = ? LIMIT 1");
        $stmt->execute([$mealType, $value]);
    }
    echo json_encode(['ok' => true]);
    exit;
}
if (!isset($tableMap[$key])) {
    echo json_encode(['ok' => false, 'error' => 'Unknown key']);
    exit;
}
$t = $tableMap[$key];
$stmt = $pdo->prepare("DELETE FROM `{$t['table']}` WHERE `{$t['column']}` = ?");
$stmt->execute([$value]);
echo json_encode(['ok' => true]);
