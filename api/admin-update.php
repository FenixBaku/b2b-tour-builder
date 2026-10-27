<?php
/**
 * Admin Panel – cədvəl redaktə API
 * hotel_listing_full, tour_azerbaijan_activities_2026, transfers
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';
require_once __DIR__ . '/../lib/cities.php';

auth_require_admin_api();

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$entity = $input['entity'] ?? '';
$action = $input['action'] ?? '';

if (!in_array($action, ['update', 'add', 'remove'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    exit;
}

// Otel + otaq növü əlavə et (hotel_listing_full)
if ($entity === 'hotel' && $action === 'add') {
    $hotelName = isset($input['hotelName']) ? trim((string)$input['hotelName']) : '';
    $roomType = isset($input['roomType']) ? trim((string)$input['roomType']) : '';
    $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
    if ($hotelName === '' || $roomType === '') {
        echo json_encode(['ok' => false, 'error' => 'hotelName and roomType required']);
        exit;
    }
    if ($price === null || $price < 0) {
        echo json_encode(['ok' => false, 'error' => 'Valid price required']);
        exit;
    }
    try {
        $region = city_code($input['region'] ?? '') ?: CITY_DEFAULT_CODE;
        $stmt = $pdo->prepare("INSERT INTO hotel_listing_full (sheet_name, region, row_num, hotel_name, room_type, season_type, price) VALUES ('manual', ?, 999, ?, ?, 'peak', ?)");
        $stmt->execute([$region, $hotelName, $roomType, $price]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Otel + otaq növü sil (hotel_listing_full)
if ($entity === 'hotel' && $action === 'remove') {
    $oldHotel = isset($input['oldHotel']) ? trim((string)$input['oldHotel']) : '';
    $oldRoomType = isset($input['oldRoomType']) ? trim((string)$input['oldRoomType']) : '';
    if ($oldHotel === '') {
        echo json_encode(['ok' => false, 'error' => 'oldHotel required']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM hotel_listing_full WHERE hotel_name = ? AND room_type = ?");
        $stmt->execute([$oldHotel, $oldRoomType]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Otel + otaq növü + qiymət redaktə (hotel_listing_full)
if ($entity === 'hotel') {
    $oldHotel = isset($input['oldHotel']) ? trim((string)$input['oldHotel']) : (isset($input['oldName']) ? trim((string)$input['oldName']) : '');
    $oldRoomType = isset($input['oldRoomType']) ? trim((string)$input['oldRoomType']) : '';
    $newHotel = isset($input['newHotel']) ? trim((string)$input['newHotel']) : (isset($input['newName']) ? trim((string)$input['newName']) : '');
    $newRoomType = isset($input['newRoomType']) ? trim((string)$input['newRoomType']) : $oldRoomType;
    $newPrice = isset($input['newPrice']) && is_numeric($input['newPrice']) ? (float)$input['newPrice'] : (isset($input['newMinPrice']) && is_numeric($input['newMinPrice']) ? (float)$input['newMinPrice'] : null);
    if ($oldHotel === '' || $newHotel === '') {
        echo json_encode(['ok' => false, 'error' => 'oldHotel and newHotel required']);
        exit;
    }
    try {
        $pdo->beginTransaction();
        if ($oldRoomType !== '') {
            $updates = ['hotel_name = ?', 'room_type = ?'];
            $vals = [$newHotel, $newRoomType];
            if ($newPrice !== null && $newPrice >= 0) {
                $updates[] = 'price = ?';
                $vals[] = $newPrice;
            }
            $vals[] = $oldHotel;
            $vals[] = $oldRoomType;
            $stmt = $pdo->prepare("UPDATE hotel_listing_full SET " . implode(', ', $updates) . " WHERE hotel_name = ? AND room_type = ?");
            $stmt->execute($vals);
        } else {
            $stmt = $pdo->prepare("UPDATE hotel_listing_full SET hotel_name = ? WHERE hotel_name = ?");
            $stmt->execute([$newHotel, $oldHotel]);
            if ($newPrice !== null && $newPrice >= 0) {
                $sub = $pdo->prepare("SELECT id FROM hotel_listing_full WHERE hotel_name = ? AND price IS NOT NULL AND price > 0 ORDER BY price ASC LIMIT 1");
                $sub->execute([$newHotel]);
                $row = $sub->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $up = $pdo->prepare("UPDATE hotel_listing_full SET price = ? WHERE id = ?");
                    $up->execute([$newPrice, $row['id']]);
                }
            }
        }
        $pdo->commit();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Aktivitet əlavə et (tour_azerbaijan_activities_2026)
if ($entity === 'activity' && $action === 'add') {
    $name = isset($input['name']) ? trim((string)$input['name']) : '';
    $service = isset($input['service']) ? trim((string)$input['service']) : '';
    $city = city_code($input['city'] ?? '') ?: CITY_DEFAULT_CODE;
    $price = isset($input['price']) && is_numeric($input['price']) ? (float)$input['price'] : null;
    if ($name === '' || $service === '') {
        echo json_encode(['ok' => false, 'error' => 'name and service required']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO tour_azerbaijan_activities_2026 (name, service, city, adult_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $service, $city, $price]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Aktivitet sil (tour_azerbaijan_activities_2026)
if ($entity === 'activity' && $action === 'remove') {
    $oldValue = isset($input['oldValue']) ? (string)$input['oldValue'] : '';
    if ($oldValue === '') {
        echo json_encode(['ok' => false, 'error' => 'oldValue required']);
        exit;
    }
    $parts = explode('|', $oldValue, 2);
    $name = $parts[0] ?? '';
    $service = $parts[1] ?? '';
    if ($name === '' && $service === '') {
        echo json_encode(['ok' => false, 'error' => 'Invalid oldValue']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM tour_azerbaijan_activities_2026 WHERE name = ? AND service = ?");
        $stmt->execute([$name, $service]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Aktivitet redaktə (tour_azerbaijan_activities_2026)
if ($entity === 'activity') {
    $oldValue = isset($input['oldValue']) ? (string)$input['oldValue'] : '';
    $newLabel = isset($input['newLabel']) ? trim((string)$input['newLabel']) : '';
    $newCity = city_code($input['newCity'] ?? '') ?: CITY_DEFAULT_CODE;
    $newPrice = isset($input['newPrice']) ? (is_numeric($input['newPrice']) ? (float)$input['newPrice'] : null) : null;
    if ($oldValue === '' || $newLabel === '') {
        echo json_encode(['ok' => false, 'error' => 'oldValue and newLabel required']);
        exit;
    }
    $parts = explode('|', $oldValue, 2);
    $oldName = $parts[0] ?? '';
    $oldService = $parts[1] ?? '';
    $labelParts = preg_split('/\s*-\s*/', $newLabel, 2);
    $newName = trim($labelParts[0] ?? '');
    $newService = trim($labelParts[1] ?? $newName);
    if ($newName === '') $newName = $newService ?: $oldName;
    if ($newService === '') $newService = $newName ?: $oldService;
    try {
        $stmt = $pdo->prepare("UPDATE tour_azerbaijan_activities_2026 SET name = ?, service = ?, city = ?, adult_price = ? WHERE name = ? AND service = ?");
        $stmt->execute([$newName, $newService, $newCity ?: 'baku', $newPrice, $oldName, $oldService]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Transfer duty-ləri (transfers cədvəli) — hər əməliyyat duty kodu ilə; A|B|Service unikal deyil.

/** Admin formundan gələn qiymətlər: rəqəm → qiymət, boş/null → NULL (qiyməti silmək üçün). */
function admin_transfer_price_values(array $colMap, array $prices) {
    $out = [];
    foreach (array_keys($colMap) as $col) {
        if (!array_key_exists($col, $prices)) continue;
        $v = $prices[$col];
        if ($v === null || $v === '') {
            $out[$col] = null;
        } elseif (is_numeric($v) && (float)$v >= 0) {
            $out[$col] = (float)$v;
        }
    }
    return $out;
}

function admin_transfer_duty_input(array $input, $key = 'dutyCode') {
    $v = $input[$key] ?? null;
    if ($v === null || $v === '') return 0;
    return (is_numeric($v) && (int)$v == $v && (int)$v > 0) ? (int)$v : -1;
}

if ($entity === 'transfer-route' && $action === 'remove') {
    $dutyCode = admin_transfer_duty_input($input);
    if ($dutyCode <= 0) {
        echo json_encode(['ok' => false, 'error' => 'dutyCode required']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM transfers WHERE duty_code = ?");
        $stmt->execute([$dutyCode]);
        echo json_encode(['ok' => true, 'deleted' => $stmt->rowCount()]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($entity === 'transfer-route' && $action === 'add') {
    $a = isset($input['a']) ? trim((string)$input['a']) : '';
    $b = isset($input['b']) ? trim((string)$input['b']) : '';
    $s = isset($input['service']) ? trim((string)$input['service']) : '';
    $schedule = isset($input['schedule']) ? trim((string)$input['schedule']) : '';
    $dutyCode = admin_transfer_duty_input($input);
    if ($a === '' || $b === '' || $s === '') {
        echo json_encode(['ok' => false, 'error' => 'a, b, service required']);
        exit;
    }
    if ($dutyCode < 0) {
        echo json_encode(['ok' => false, 'error' => 'Duty kodu müsbət tam ədəd olmalıdır']);
        exit;
    }
    require_once __DIR__ . '/tour_config_functions.php';
    $colMap = getTransferVehicleColumnsFromTable($pdo);
    $prices = admin_transfer_price_values($colMap, isset($input['prices']) && is_array($input['prices']) ? $input['prices'] : []);
    try {
        if ($dutyCode === 0) {
            $dutyCode = (int)$pdo->query("SELECT COALESCE(MAX(duty_code), 0) + 1 FROM transfers")->fetchColumn();
        } else {
            $exists = $pdo->prepare("SELECT 1 FROM transfers WHERE duty_code = ?");
            $exists->execute([$dutyCode]);
            if ($exists->fetchColumn()) {
                echo json_encode(['ok' => false, 'error' => 'Duty kodu ' . $dutyCode . ' artıq mövcuddur']);
                exit;
            }
        }
        $cols = ['duty_code', 'a', 'b', 'service', 'day_schedule'];
        $vals = [$dutyCode, $a, $b, $s, $schedule !== '' ? $schedule : null];
        foreach ($prices as $col => $price) {
            $cols[] = '`' . str_replace('`', '``', $col) . '`';
            $vals[] = $price;
        }
        $sql = "INSERT INTO transfers (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")";
        $pdo->prepare($sql)->execute($vals);
        echo json_encode(['ok' => true, 'dutyCode' => $dutyCode]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($entity === 'transfer-route' && $action === 'update') {
    $dutyCode = admin_transfer_duty_input($input);
    $newA = isset($input['newA']) ? trim((string)$input['newA']) : '';
    $newB = isset($input['newB']) ? trim((string)$input['newB']) : '';
    $newS = isset($input['newS']) ? trim((string)$input['newS']) : '';
    if ($dutyCode <= 0 || $newA === '' || $newB === '' || $newS === '') {
        echo json_encode(['ok' => false, 'error' => 'dutyCode, newA, newB, newS required']);
        exit;
    }
    require_once __DIR__ . '/tour_config_functions.php';
    $colMap = getTransferVehicleColumnsFromTable($pdo);
    $prices = admin_transfer_price_values($colMap, isset($input['prices']) && is_array($input['prices']) ? $input['prices'] : []);
    $sets = ['a = ?', 'b = ?', 'service = ?'];
    $vals = [$newA, $newB, $newS];
    if (array_key_exists('schedule', $input)) {
        $schedule = trim((string)$input['schedule']);
        $sets[] = 'day_schedule = ?';
        $vals[] = $schedule !== '' ? $schedule : null;
    }
    foreach ($prices as $col => $price) {
        $sets[] = '`' . str_replace('`', '``', $col) . '` = ?';
        $vals[] = $price;
    }
    $vals[] = $dutyCode;
    try {
        $stmt = $pdo->prepare("UPDATE transfers SET " . implode(', ', $sets) . " WHERE duty_code = ?");
        $stmt->execute($vals);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown entity']);
