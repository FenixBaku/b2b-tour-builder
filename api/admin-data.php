<?php
/**
 * Admin Panel üçün ətraflı məlumat API
 * Bütün cədvəllərdən stats və full data qaytarır
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/_auth_helper.php';
require_once __DIR__ . '/tour_config_functions.php';

// Konfiqurasiya məlumatlarını oxumaq üçün tam admin və admin assistant icazəlidir
auth_require_admin_or_assistant_api();

$pdo = dbConnect();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
    exit;
}

$data = getTourConfig($pdo, null);

// Stats
$stats = [
    'hotels' => 0,
    'hotelPrices' => 0,
    'activities' => 0,
    'transfers' => 0,
    'countries' => count($data['countries'] ?? []),
    'visa' => count($data['visa'] ?? []),
    'vehicles' => count($data['vehicles'] ?? []),
    'water' => count($data['water'] ?? []),
    'guides' => count($data['guides'] ?? []),
    'tours' => count($data['tours'] ?? []),
    'entrances' => count($data['entrances'] ?? []),
    'extraServices' => count($data['extraServices'] ?? $data['extras'] ?? []),
];

$hotelRoomPrices = $data['hotelRoomPrices'] ?? [];
$stats['hotels'] = count($hotelRoomPrices);
foreach ($hotelRoomPrices as $hotel => $rooms) {
    $stats['hotelPrices'] += is_array($rooms) ? count($rooms) : 0;
}

$mealsByCity = $data['mealsByCity'] ?? [];
$stats['meals'] = 0;
foreach ($mealsByCity as $city => $types) {
    foreach ($types as $arr) {
        $stats['meals'] += is_array($arr) ? count($arr) : 0;
    }
}

$transferPrices = $data['transferVehiclePrices'] ?? [];
$stats['transferRoutes'] = count($transferPrices);

$transferA = $data['transferA'] ?? [];
$transferB = $data['transferB'] ?? [];
$transferServices = $data['transferServices'] ?? [];

// Activities from tour_azerbaijan_activities_2026
$activitiesList = [];
try {
    $stmt = $pdo->query("SELECT name, service, COALESCE(city, 'baku') AS city, adult_price FROM tour_azerbaijan_activities_2026 WHERE (name IS NOT NULL AND name != '') OR (service IS NOT NULL AND service != '') ORDER BY city, name, service");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $value = ($row['name'] ?? '') . '|' . ($row['service'] ?? '');
            $label = trim($row['name'] ?? '') && trim($row['service'] ?? '') ? trim($row['name']) . ' - ' . trim($row['service']) : (trim($row['name'] ?: $row['service']) ?: $value);
            $activitiesList[] = [
                'value' => $value,
                'label' => $label,
                'city' => city_code($row['city'] ?? '') ?: CITY_DEFAULT_CODE,
                'price' => isset($row['adult_price']) ? (float)$row['adult_price'] : null,
            ];
        }
    }
} catch (Exception $e) { }
$stats['activities'] = count($activitiesList);

// tour_hotels list (for add/remove in admin)
$tourHotelsList = [];
try {
    $stmt = $pdo->query("SELECT name, region FROM tour_hotels ORDER BY region, sort_order, id");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tourHotelsList[] = $row['name'];
        }
    }
} catch (Exception $e) { }

// Hotel listing summary (from hotel_listing_full or hotelRoomPrices)
$hotelListing = [];
$hotelListingRows = []; // Düz siyahı: Otel | Otaq növü | Qiymət
$hotelRoomPricesData = $data['hotelRoomPrices'] ?? [];
foreach ($hotelRoomPricesData as $hotel => $rooms) {
    if (!is_array($rooms) || $hotel === 'Extras') continue;
    $roomCount = 0;
    $minPrice = null;
    foreach ($rooms as $roomType => $price) {
        $roomCount++;
        if ($price !== null && $price > 0) {
            if ($minPrice === null || $price < $minPrice) $minPrice = $price;
            $hotelListingRows[] = [
                'hotel' => $hotel,
                'roomType' => $roomType,
                'price' => (float) $price,
            ];
        }
    }
    $hotelListing[] = [
        'name' => $hotel,
        'roomTypes' => $roomCount,
        'minPrice' => $minPrice,
    ];
}

// Transfer vehicle columns (col => label)
$transferVehicleColumns = getTransferVehicleColumnsFromTable($pdo);

// Transferlər duty kodu üzrə (hər duty ayrıca sətir; A|B|Service təkrarlana bilər)
$transferRoutesList = [];
try {
    $stmt = $pdo->query(transferDutySelectSql($transferVehicleColumns) . ' ORDER BY duty_code ASC');
    while ($stmt && ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $minPrice = null;
        $vehiclePrices = [];
        foreach ($transferVehicleColumns as $col => $label) {
            $p = isset($row[$col]) && is_numeric($row[$col]) ? (float)$row[$col] : null;
            if ($p !== null && $p > 0 && ($minPrice === null || $p < $minPrice)) $minPrice = $p;
            $vehiclePrices[$col] = $p;
        }
        $a = trim((string)($row['a'] ?? ''));
        $b = trim((string)($row['b'] ?? ''));
        $s = trim((string)($row['service'] ?? ''));
        $transferRoutesList[] = [
            'dutyCode' => (int)$row['duty_code'],
            'a' => $a,
            'b' => $b,
            'service' => $s,
            'route' => $a . ' → ' . $b . ' (' . $s . ')',
            'title' => transferDutyTitle($row['day_schedule'] ?? ''),
            'schedule' => (string)($row['day_schedule'] ?? ''),
            'minPrice' => $minPrice,
            'vehiclePrices' => $vehiclePrices,
        ];
    }
} catch (Exception $e) { }
$stats['transferRoutes'] = count($transferRoutesList);

// Meals by city
$mealsSummary = [];
foreach ($mealsByCity as $city => $types) {
    $mealsSummary[$city] = [
        'breakfast' => count($types['breakfast'] ?? []),
        'lunch' => count($types['lunch'] ?? []),
        'dinner' => count($types['dinner'] ?? []),
        'total' => count($types['breakfast'] ?? []) + count($types['lunch'] ?? []) + count($types['dinner'] ?? []),
    ];
}

// Admin cədvəli transferRoutesList-dən qurulur; kompakt builder siyahısını iki dəfə göndərməyək
unset($data['transferDuties']);

echo json_encode([
    'ok' => true,
    'data' => $data,
    'tourHotelsList' => $tourHotelsList,
    'stats' => $stats,
    'activitiesList' => $activitiesList,
    'hotelListing' => $hotelListing,
    'hotelListingRows' => $hotelListingRows,
    'transferRoutesList' => $transferRoutesList,
    'transferVehicleColumns' => $transferVehicleColumns,
    'mealsSummary' => $mealsSummary,
    'transferA' => $transferA,
    'transferB' => $transferB,
    'transferServices' => $transferServices,
], JSON_UNESCAPED_UNICODE);
