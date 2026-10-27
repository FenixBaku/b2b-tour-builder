<?php
/**
 * Sifariş qiymətinin server tərəfində hesablanması.
 * tour-builder.php-dəki updateSummary() ilə eyni qaydalar — sifariş yadda saxlananda brauzerin göndərdiyi
 * cəm yox, bu hesablama total_amount kimi yazılır (borc/faktura bu məbləğdən yaranır).
 */
require_once __DIR__ . '/../api/tour_config_functions.php';

const ORDER_VISA_PRICES = ['Not Required' => 0, 'Standard (3-5 Days)' => 31, 'Urgent (3 Hours)' => 65];

function op_norm($s) {
    $s = function_exists('mb_strtolower') ? mb_strtolower(trim((string)$s), 'UTF-8') : strtolower(trim((string)$s));
    return preg_replace('/\s+/u', ' ', $s);
}

/** JS parseInt: baştakı rəqəmlər, yoxdursa null. */
function op_parse_int($v) {
    return preg_match('/^\s*([+-]?\d+)/', (string)$v, $m) ? (int)$m[1] : null;
}

function op_num($v) {
    return is_numeric($v) ? (float)$v : null;
}

function op_find_item($list, $value) {
    if (!is_array($list)) return null;
    foreach ($list as $item) {
        if (is_array($item) && (($item['value'] ?? $item['label'] ?? null) === $value)) return $item;
    }
    return null;
}

function op_guest_counts(array $basics) {
    $a = op_parse_int(($basics['adults'] ?? '') !== '' ? $basics['adults'] : '1');
    $c = op_parse_int(($basics['children'] ?? '') !== '' ? $basics['children'] : '0');
    $adults = max(0, (int)$a);
    $children = max(0, (int)$c);
    return [
        'adults' => $adults,
        'children' => $children,
        'totalPax' => max(1, $adults + $children),
        'adultMultiplier' => max(1, $adults),
        'mealGuests' => max(1, $adults + $children),
    ];
}

function op_hotel_price(array $cfg, $hotel, $roomType) {
    if ($hotel === '') return 0.0;
    if ($roomType !== '' && isset($cfg['hotelRoomPrices'][$hotel][$roomType])) {
        return (float)$cfg['hotelRoomPrices'][$hotel][$roomType];
    }
    $prices = $cfg['hotelPrices'] ?? [];
    if (isset($prices[$hotel]) && is_numeric($prices[$hotel])) return (float)$prices[$hotel];
    $upper = mb_strtoupper($hotel, 'UTF-8');
    foreach ($prices as $k => $p) {
        $ku = mb_strtoupper((string)$k, 'UTF-8');
        if (strpos($ku, $upper) !== false || strpos($upper, $ku) !== false) return (float)$p;
    }
    return 0.0;
}

function op_hotel_extra_price(array $cfg, $hotel, $type) {
    $map = $cfg['hotelExtras'] ?? [];
    $item = op_find_item($map[$hotel] ?? null, $type);
    if ($item && is_numeric($item['price'] ?? null)) return (float)$item['price'];
    $upper = mb_strtoupper($hotel, 'UTF-8');
    foreach ($map as $k => $list) {
        $ku = mb_strtoupper((string)$k, 'UTF-8');
        if ($ku !== $upper && strpos($ku, $upper) === false && strpos($upper, $ku) === false) continue;
        $match = op_find_item($list, $type);
        if ($match && is_numeric($match['price'] ?? null)) return (float)$match['price'];
    }
    return 0.0;
}

function op_hotel_extra_multiplier($type, array $g) {
    $t = op_norm($type);
    if (preg_match('/\bextra\s*bed\b/i', $t)) return 1;
    if (strpos($t, 'child') !== false || strpos($t, 'cnb') !== false) return max(0, $g['children']);
    if (strpos($t, 'adult') !== false) return max(0, $g['adults']);
    return max(1, $g['totalPax']);
}

function op_meal_price(array $cfg, $type, $value, $city) {
    $byCity = $cfg['mealsByCity'][$city] ?? ($cfg['mealsByCity'][CITY_DEFAULT_CODE] ?? []);
    $item = op_find_item($byCity[$type] ?? [], $value);
    if (!$item || (!isset($item['price']) && !isset($item['price2']))) return 0.0;
    $p = (float)($item['price'] ?? 0);
    return $p ?: (float)($item['price2'] ?? 0);
}

function op_vehicle_price_in_map($map, $vehicle) {
    if (!is_array($map) || $vehicle === '') return null;
    if (isset($map[$vehicle]) && is_numeric($map[$vehicle])) return (float)$map[$vehicle];
    $want = op_norm($vehicle);
    foreach ($map as $name => $p) {
        if (op_norm($name) === $want && is_numeric($p)) return (float)$p;
    }
    return null;
}

function op_service_key($s) {
    $n = str_replace("\t", '', op_norm($s));
    if (strpos($n, 'day trip') !== false) return 'daytrip';
    if (strpos($n, 'overnight') !== false) return 'overnight';
    if (strpos($n, 'private tour') !== false) return 'privatetour';
    if (strpos($n, 'private transfer') !== false || strpos($n, 'privatetransfer') !== false) return 'privatetransfer';
    if (strpos($n, '2 way') !== false || strpos($n, '2ways') !== false) return 'twoway';
    if (strpos($n, '1 way') !== false || strpos($n, '1way') !== false || strpos($n, 'airport transfer 1') !== false) return 'oneway';
    if (strpos($n, 'airport transfer 2') !== false) return 'twoway';
    return preg_replace('/[^a-z0-9]/', '', $n);
}

/** Duty kodu olmayan köhnə transferlər: marşrut üzrə axtarış (builder-dəki findRoutePriceMap). */
function op_legacy_route_prices(array $prices, $a, $b, $svc, $city) {
    $candidates = [$a];
    $aNorm = op_norm($a);
    if ($aNorm === 'hotel' || $aNorm === 'hotel in region') $candidates[] = city_name($city) ?: 'Baku';
    if (strpos($aNorm, 'airport') !== false) $candidates[] = 'Airport';
    if (city_code($a) !== '') $candidates[] = city_name($a);
    $candidates = array_values(array_unique($candidates));
    $bNorm = op_norm($b);
    $svcKey = op_service_key($svc);

    foreach ($candidates as $cand) {
        $exact = $cand . '|' . $b . '|' . $svc;
        if (!empty($prices[$exact])) return $prices[$exact];
        $want = op_norm($cand) . '|' . $bNorm . '|' . op_norm($svc);
        foreach ($prices as $key => $map) {
            $parts = explode('|', $key);
            if (count($parts) < 3) continue;
            $ks = implode('|', array_slice($parts, 2));
            if (op_service_key($ks) === '' || op_service_key($ks) !== $svcKey) continue;
            if (op_norm($parts[0]) . '|' . op_norm($parts[1]) . '|' . op_norm($ks) === $want) return $map;
        }
    }
    $best = null;
    $bestScore = 0;
    foreach ($prices as $key => $map) {
        $parts = explode('|', $key);
        if (count($parts) < 3 || op_norm($parts[1]) !== $bNorm) continue;
        $ks = implode('|', array_slice($parts, 2));
        if (op_service_key($ks) === '' || op_service_key($ks) !== $svcKey) continue;
        $score = 0;
        foreach ($candidates as $cand) {
            if (op_norm($cand) === op_norm($parts[0])) $score += 100;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $map;
        }
    }
    return $bestScore >= 100 ? $best : null;
}

/**
 * @return array{total: float, perPerson: float, lines: array<int, array{cat: string, desc: string, amount: float}>}
 */
function order_compute_total(PDO $pdo, array $order) {
    $basics = is_array($order['basics'] ?? null) ? $order['basics'] : [];
    $days = array_values(array_filter(is_array($order['days'] ?? null) ? $order['days'] : [], 'is_array'));
    $checkin = (string)($basics['checkin'] ?? '');
    $cfg = getTourConfig($pdo, preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) ? $checkin : null);
    $activities = fetchActivitiesByCity($pdo);
    $g = op_guest_counts($basics);
    $vehicle = (string)($basics['vehicle'] ?? '');
    $lines = [];
    $add = function ($cat, $desc, $amount) use (&$lines) {
        $lines[] = ['cat' => $cat, 'desc' => $desc, 'amount' => (float)$amount];
    };
    $dutyCache = [];

    $accommodation = 0.0;
    $hasHotel = false;
    $activitiesTotal = 0.0;
    $mealsTotal = 0.0;
    foreach ($days as $day) {
        $city = city_code($day['city'] ?? '') ?: CITY_DEFAULT_CODE;
        $hotel = (string)($day['hotel'] ?? '');

        if ($hotel !== '') {
            $hasHotel = true;
            $rooms = array_values(array_filter(is_array($day['rooms'] ?? null) ? $day['rooms'] : [], function ($r) {
                return is_array($r) && ($r['roomType'] ?? '') !== '';
            }));
            if (!$rooms) $rooms = [['roomType' => '', 'roomCount' => 1]];
            foreach ($rooms as $room) {
                $count = max(1, (int)op_parse_int($room['roomCount'] ?? 1));
                $accommodation += op_hotel_price($cfg, $hotel, (string)$room['roomType']) * $count;
            }
        }

        foreach ((is_array($day['hotelExtras'] ?? null) ? $day['hotelExtras'] : []) as $ex) {
            $type = (string)($ex['type'] ?? '');
            $qty = (int)op_parse_int($ex['qty'] ?? '0');
            if ($type === '' || $hotel === '' || $qty < 1) continue;
            $line = op_hotel_extra_price($cfg, $hotel, $type) * $qty * op_hotel_extra_multiplier($type, $g);
            if ($line > 0) $add('Hotel extras', $hotel . ' – ' . $type . ' x' . $qty, $line);
        }

        foreach ((is_array($day['activities'] ?? null) ? $day['activities'] : []) as $act) {
            $value = (string)($act['value'] ?? '');
            if ($value === '') continue;
            $item = op_find_item($activities[$city] ?? [], $value);
            if (!$item) continue; // builder yalnız şəhərin siyahısındakı aktivitetləri göstərir
            $price = (float)($item['price'] ?? 0);
            $tickets = (int)($act['tickets'] ?? 0);
            if ($tickets < 1) {
                $tickets = preg_match('/\bper\s*(?:person|pax)\b/i', (string)$item['label']) ? $g['totalPax'] : $g['adultMultiplier'];
            }
            $activitiesTotal += $price * $tickets;
        }

        foreach (['lunch', 'dinner'] as $mealType) {
            $value = (string)($day['meals'][$mealType]['option'] ?? '');
            if ($value !== '') $mealsTotal += op_meal_price($cfg, $mealType, $value, $city) * $g['mealGuests'];
        }

        $transfers = is_array($day['transfers'] ?? null) ? $day['transfers'] : [];
        foreach ($transfers as $t) {
            if (!is_array($t)) continue;
            $duty = (int)($t['dutyCode'] ?? 0);
            $price = null;
            if ($duty > 0) {
                if (!array_key_exists($duty, $dutyCache)) $dutyCache[$duty] = fetchTransferDutyByCode($pdo, $duty);
                if ($dutyCache[$duty]) $price = op_vehicle_price_in_map($dutyCache[$duty]['prices'], $vehicle);
            }
            if (!($duty > 0 && $dutyCache[$duty]) && ($t['transferA'] ?? '') !== '' && ($t['transferB'] ?? '') !== '' && ($t['transferService'] ?? '') !== '' && $vehicle !== '') {
                $map = op_legacy_route_prices($cfg['transferVehiclePrices'] ?? [], (string)$t['transferA'], (string)$t['transferB'], (string)$t['transferService'], $city);
                $price = op_vehicle_price_in_map($map, $vehicle);
            }
            $desc = ($duty > 0 ? 'Duty ' . $duty . ': ' : '') . trim(($t['transferA'] ?? '') . ' → ' . ($t['transferB'] ?? ''));
            $add('Transportation', $desc, (float)$price);
        }
    }

    if ($hasHotel) $add('Accommodation', 'Hotels', $accommodation);
    if ($activitiesTotal > 0) $add('Activities', 'Activities', $activitiesTotal);
    if ($mealsTotal > 0) $add('Meals', 'Lunch & Dinner', $mealsTotal);

    $water = (string)($basics['water'] ?? '');
    if ($water !== '' && op_norm($water) !== 'none') {
        $item = op_find_item($cfg['water'] ?? [], $water);
        $perDay = (float)($item['price'] ?? 0);
        $add('Water Package', $water, $perDay * $g['totalPax'] * max(1, count($days)));
    }

    $visa = (string)($basics['visa'] ?? '');
    if ($visa !== '') {
        $low = strtolower($visa);
        $per = ORDER_VISA_PRICES[$visa] ?? (strpos($low, 'urgent') !== false ? 65 : (strpos($low, 'standard') !== false ? 31 : 0));
        $add('E-Visa', $visa, $per * max(0, $g['adults']));
    }

    foreach ((is_array($order['extraServices'] ?? null) ? $order['extraServices'] : []) as $ex) {
        $service = (string)($ex['service'] ?? '');
        if ($service === '') continue;
        $item = op_find_item($cfg['extraServices'] ?? [], $service);
        $unit = (float)($item['price'] ?? 0); // qiymət DB-dən, brauzerdən gələn yox
        $qty = (int)op_parse_int(($ex['qty'] ?? '') !== '' ? $ex['qty'] : '1');
        if ($qty < 1) $qty = 1;
        if ($unit * $qty > 0) $add('Extra Services', $service, $unit * $qty);
    }

    $total = 0.0;
    foreach ($lines as $l) $total += $l['amount'];
    $total = round($total, 2);
    return ['total' => $total, 'perPerson' => round($total / $g['adultMultiplier'], 2), 'lines' => $lines];
}
