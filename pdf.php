<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/cities.php';
require_once __DIR__ . '/auth/_mail_helper.php';
require_once __DIR__ . '/auth/_auth_helper.php';

auth_require_login('/bb/auth/login.php');

$orderJson  = (string)($_POST['order_data_json'] ?? '');
$perPerson  = (string)($_POST['per_person'] ?? '');
$grandTotal = (string)($_POST['grand_total'] ?? '');

$orderData = [];
if ($orderJson !== '') {
    $decoded = json_decode($orderJson, true);
    if (is_array($decoded)) {
        $orderData = $decoded;
    }
}

$basics = isset($orderData['basics']) && is_array($orderData['basics']) ? $orderData['basics'] : [];
$days   = isset($orderData['days']) && is_array($orderData['days']) ? array_values(array_filter($orderData['days'], 'is_array')) : [];
$extras = isset($orderData['extraServices']) && is_array($orderData['extraServices']) ? array_values(array_filter($orderData['extraServices'], 'is_array')) : [];

function pdf_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pdf_format_date_short(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d F, Y', $ts) : (string)$date;
}

function pdf_format_date_long(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('l, d F Y', $ts) : (string)$date;
}

function pdf_day_label(int $dayNumber): string
{
    $mod100 = $dayNumber % 100;
    if ($mod100 >= 11 && $mod100 <= 13) {
        return $dayNumber . 'th Day';
    }

    return match ($dayNumber % 10) {
        1 => $dayNumber . 'st Day',
        2 => $dayNumber . 'nd Day',
        3 => $dayNumber . 'rd Day',
        default => $dayNumber . 'th Day',
    };
}

function pdf_trip_code(string $orderJson, array $orderData): string
{
    $candidate = (string)($_POST['trip_id'] ?? $orderData['trip_id'] ?? $orderData['tripId'] ?? '');
    $candidate = preg_replace('/\D+/', '', $candidate) ?? '';
    if ($candidate !== '') {
        return $candidate;
    }

    $seed = $orderJson !== '' ? $orderJson : json_encode($orderData);
    $hash = abs(crc32((string)$seed));
    return (string)(1000000 + ($hash % 9000000));
}

function pdf_reference_code(string $tripId, array $orderData): string
{
    $candidate = (string)($_POST['reference_id'] ?? $orderData['reference_id'] ?? $orderData['referenceId'] ?? '');
    $candidate = preg_replace('/\D+/', '', $candidate) ?? '';
    if ($candidate !== '') {
        return $candidate;
    }

    return substr(str_pad($tripId, 5, '0', STR_PAD_LEFT), -5);
}

function pdf_days_between(?string $start, ?string $end): int
{
    if (!$start || !$end) {
        return 0;
    }
    $startTs = strtotime($start);
    $endTs   = strtotime($end);
    if (!$startTs || !$endTs || $endTs <= $startTs) {
        return 0;
    }

    return (int)round(($endTs - $startTs) / 86400);
}

function pdf_get_activity_description(?string $tour, ?string $entrance): string
{
    static $map = null;

    if ($map === null) {
        $map = [];
        $pdo = dbConnect();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT name, service, description FROM tour_azerbaijan_activities_2026 WHERE description IS NOT NULL AND description != ''");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $name = isset($row['name']) ? trim((string)$row['name']) : '';
                        $service = isset($row['service']) ? trim((string)$row['service']) : '';
                        $desc = isset($row['description']) ? trim((string)$row['description']) : '';
                        if ($desc === '') {
                            continue;
                        }
                        $key = $name . '|' . $service;
                        if ($key !== '|') {
                            $map[$key] = $desc;
                        }
                    }
                }
            } catch (Exception $e) {
                // quietly ignore DB errors for PDF description lookup
            }
        }
    }

    $tour = trim((string)$tour);
    $entrance = trim((string)$entrance);

    if ($tour === '' && $entrance === '') {
        return '';
    }

    $key = $tour . '|' . $entrance;
    if ($key !== '|' && isset($map[$key])) {
        return $map[$key];
    }

    // Fallback: try to match by name only if service is empty or key not found
    if ($tour !== '') {
        foreach ($map as $k => $desc) {
            if (str_starts_with($k, $tour . '|')) {
                return $desc;
            }
        }
    }

    return '';
}

/** Transfer proqramı: duty kodu varsa həmin duty-dən, yoxdursa (köhnə sifariş) marşrut üzrə ilk uyğun sətirdən. */
function pdf_get_transfer_schedule(?string $a, ?string $b, ?string $service, $dutyCode = null): string
{
    static $map = null;
    static $byDuty = null;

    if ($map === null) {
        $map = [];
        $byDuty = [];
        $pdo = dbConnect();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT duty_code, a, b, service, day_schedule FROM transfers WHERE day_schedule IS NOT NULL AND day_schedule != '' ORDER BY duty_code ASC");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $from = isset($row['a']) ? trim((string)$row['a']) : '';
                        $to = isset($row['b']) ? trim((string)$row['b']) : '';
                        $svc = isset($row['service']) ? trim((string)$row['service']) : '';
                        $sched = isset($row['day_schedule']) ? trim((string)$row['day_schedule']) : '';
                        if ($sched === '') {
                            continue;
                        }
                        $byDuty[(int)$row['duty_code']] = $sched;
                        if ($from === '' || $to === '' || $svc === '') {
                            continue;
                        }
                        $key = $from . '|' . $to . '|' . $svc;
                        if (!isset($map[$key])) {
                            $map[$key] = $sched;
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore DB errors for schedule lookup
            }
        }
    }

    $dutyCode = (int)$dutyCode;
    if ($dutyCode > 0 && isset($byDuty[$dutyCode])) {
        return $byDuty[$dutyCode];
    }

    $a = trim((string)$a);
    $b = trim((string)$b);
    $service = trim((string)$service);

    if ($a === '' || $b === '' || $service === '') {
        return '';
    }

    $key = $a . '|' . $b . '|' . $service;
    return $map[$key] ?? '';
}

function pdf_collect_activity_labels_basic(array $day): array
{
    $labels = [];
    $activities = isset($day['activities']) && is_array($day['activities']) ? $day['activities'] : [];
    foreach ($activities as $activity) {
        if (!is_array($activity)) {
            continue;
        }
        $tour = trim((string)($activity['tour'] ?? ''));
        $entrance = trim((string)($activity['entrance'] ?? ''));
        if ($tour === '' && $entrance === '') {
            continue;
        }
        if ($tour !== '' && $entrance !== '') {
            $labels[] = $tour . ' - ' . $entrance;
        } elseif ($tour !== '') {
            $labels[] = $tour;
        } elseif ($entrance !== '') {
            $labels[] = $entrance;
        }
    }

    if (!$labels && !empty($day['tour'])) {
        $labels[] = (string)$day['tour'];
    }

    return array_values(array_unique(array_filter($labels)));
}

function pdf_collect_activity_labels_with_desc(array $day): array
{
    $labels = [];
    $activities = isset($day['activities']) && is_array($day['activities']) ? $day['activities'] : [];
    foreach ($activities as $activity) {
        if (!is_array($activity)) {
            continue;
        }
        $tour = trim((string)($activity['tour'] ?? ''));
        $entrance = trim((string)($activity['entrance'] ?? ''));
        if ($tour === '' && $entrance === '') {
            continue;
        }

        $baseLabel = '';
        if ($tour !== '' && $entrance !== '') {
            $baseLabel = $tour . ' - ' . $entrance;
        } elseif ($tour !== '') {
            $baseLabel = $tour;
        } elseif ($entrance !== '') {
            $baseLabel = $entrance;
        }

        if ($baseLabel === '') {
            continue;
        }

        $desc = pdf_get_activity_description($tour, $entrance);
        if ($desc !== '') {
            $labels[] = $baseLabel . ' — ' . $desc;
        } else {
            $labels[] = $baseLabel;
        }
    }

    if (!$labels && !empty($day['tour'])) {
        $labels[] = (string)$day['tour'];
    }

    return array_values(array_unique(array_filter($labels)));
}

/** Gün üzrə transfer siyahısı (köhnə tək transfer və ya transfers[] massivi). */
function pdf_day_transfers_list(array $day): array
{
    if (!empty($day['transfers']) && is_array($day['transfers'])) {
        $out = [];
        foreach ($day['transfers'] as $t) {
            if (!is_array($t)) {
                continue;
            }
            $a = trim((string)($t['transferA'] ?? ''));
            $b = trim((string)($t['transferB'] ?? ''));
            $s = trim((string)($t['transferService'] ?? ''));
            $duty = (int)($t['dutyCode'] ?? 0);
            if ($a !== '' || $b !== '' || $s !== '' || $duty > 0) {
                $out[] = ['transferA' => $a, 'transferB' => $b, 'transferService' => $s, 'dutyCode' => $duty];
            }
        }
        if ($out) {
            return $out;
        }
    }
    $transferA = trim((string)($day['transferA'] ?? ''));
    $transferB = trim((string)($day['transferB'] ?? ''));
    $service   = trim((string)($day['transferService'] ?? ''));
    if ($transferA !== '' || $transferB !== '' || $service !== '') {
        return [['transferA' => $transferA, 'transferB' => $transferB, 'transferService' => $service]];
    }
    return [];
}

function pdf_collect_day_services(array $day): array
{
    $items = [];

    foreach (pdf_day_transfers_list($day) as $t) {
        $transferA = $t['transferA'];
        $transferB = $t['transferB'];
        $service   = $t['transferService'];
        if ($transferA !== '' || $transferB !== '') {
            $route = trim($transferA . (($transferA !== '' && $transferB !== '') ? ' to ' : '') . $transferB);
            $items[] = trim($route . ($service !== '' ? ' (' . $service . ')' : ''));
        }
    }

    foreach (pdf_collect_activity_labels_basic($day) as $label) {
        $items[] = $label;
    }

    $hotel = trim((string)($day['hotel'] ?? ''));
    $roomType = trim((string)($day['roomType'] ?? ''));
    if ($hotel !== '') {
        $hotelLine = 'Hotel: ' . $hotel . ($roomType !== '' ? ' / ' . $roomType : '');
        $extras = isset($day['hotelExtras']) && is_array($day['hotelExtras']) ? $day['hotelExtras'] : [];
        $extraParts = [];
        foreach ($extras as $ex) {
            if (!is_array($ex)) {
                continue;
            }
            $type = trim((string)($ex['type'] ?? ''));
            if ($type === '') {
                continue;
            }
            $qty = (int)($ex['qty'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }
            $extraParts[] = $type . ($qty > 1 ? ' x' . $qty : '');
        }
        if ($extraParts) {
            $hotelLine .= ' + ' . implode(', ', $extraParts);
        }
        $items[] = $hotelLine;
    }

    return array_values(array_filter($items));
}

/** Short Itinerary: eyni xidmət sətri yalnız ilk günə düşür. */
function pdf_unique_short_itinerary_lines(array $lines, array &$seen): array
{
    $unique = [];
    foreach ($lines as $line) {
        $key = trim((string)$line);
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $unique[] = $line;
    }

    return $unique;
}

function pdf_meal_summary(array $day): string
{
    $meals = isset($day['meals']) && is_array($day['meals']) ? $day['meals'] : [];
    $parts = [];

    $lunch = isset($meals['lunch']) && is_array($meals['lunch']) ? $meals['lunch'] : [];
    $dinner = isset($meals['dinner']) && is_array($meals['dinner']) ? $meals['dinner'] : [];

    $lunchValue = trim((string)($lunch['option'] ?? ''));
    $dinnerValue = trim((string)($dinner['option'] ?? ''));

    if ($lunchValue !== '') {
        $parts[] = 'Lunch: ' . $lunchValue;
    }
    if ($dinnerValue !== '') {
        $parts[] = 'Dinner: ' . $dinnerValue;
    }

    return implode(' | ', $parts);
}

function pdf_quote_lines(array $days, array $basics, array $extras, int $nights): array
{
    $lines = [];

    if ($nights > 0) {
        $lines[] = $nights . ' night(s) accommodation as per selected hotels';
    }

    $hasTransfers = false;
    $hasActivities = false;
    $hasMeals = false;
    foreach ($days as $day) {
        if (!is_array($day)) {
            continue;
        }
        if (!empty($day['transferA']) || !empty($day['transferB'])) {
            $hasTransfers = true;
        }
        if (pdf_collect_activity_labels_basic($day)) {
            $hasActivities = true;
        }
        if (pdf_meal_summary($day) !== '') {
            $hasMeals = true;
        }
    }

    if ($hasTransfers) {
        $lines[] = 'Selected transfers and transportation as per itinerary';
    }
    if ($hasActivities) {
        $lines[] = 'Selected tours and entrance tickets';
    }

    $visa = trim((string)($basics['visa'] ?? ''));
    if ($visa !== '' && stripos($visa, 'not required') === false) {
        $lines[] = 'Visa service: ' . $visa;
    }

    $guide = trim((string)($basics['guide'] ?? ''));
    if ($guide !== '') {
        $lines[] = 'Guide service: ' . $guide;
    }

    $water = trim((string)($basics['water'] ?? ''));
    if ($water !== '') {
        $lines[] = 'Water service: ' . $water;
    }

    if ($hasMeals) {
        $lines[] = 'Selected meal services';
    }

    if ($extras) {
        $lines[] = 'Additional extra services included in this quote';
    }

    if (!empty($basics['insurance'])) {
        $lines[] = 'Travel insurance included';
    }

    return array_values(array_unique(array_filter($lines)));
}

function pdf_hotel_rows(array $days): array
{
    $rows = [];
    foreach ($days as $day) {
        if (!is_array($day)) {
            continue;
        }
        $hotel = trim((string)($day['hotel'] ?? ''));
        if ($hotel === '') {
            continue;
        }
        $roomType = trim((string)($day['roomType'] ?? ''));
        $key = $hotel . '||' . $roomType;
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'hotel' => $hotel,
                'roomType' => $roomType,
                'nights' => 0,
                'cities' => [],
            ];
        }
        $rows[$key]['nights']++;
        $city = pdf_city_label($day['city'] ?? '');
        if ($city !== '') {
            $rows[$key]['cities'][$city] = true;
        }
    }

    foreach ($rows as &$row) {
        $row['cities'] = implode(', ', array_keys($row['cities']));
    }
    unset($row);

    return array_values($rows);
}

/** Şəhər kodu (BAK) və ya köhnə dəyər (baku) → müştəri üçün ad (Baku). */
function pdf_city_label($city): string
{
    $city = trim((string)$city);
    if ($city === '') {
        return '';
    }
    return city_name($city) ?: ucfirst($city);
}

function pdf_vehicle_rows(array $days): array
{
    $rows = [];
    foreach ($days as $day) {
        if (!is_array($day)) {
            continue;
        }
        $vehicle = trim((string)($day['vehicle'] ?? ''));
        if ($vehicle === '') {
            continue;
        }
        if (!isset($rows[$vehicle])) {
            $rows[$vehicle] = [
                'vehicle' => $vehicle,
                'days' => 0,
            ];
        }
        $rows[$vehicle]['days']++;
    }

    return array_values($rows);
}

$tripId = pdf_trip_code($orderJson, $orderData);
$referenceId = pdf_reference_code($tripId, $orderData);

$destination = trim((string)($basics['country'] ?? 'Azerbaijan'));
$checkin = trim((string)($basics['checkin'] ?? ''));
$checkout = trim((string)($basics['checkout'] ?? ''));
$adults = (int)($basics['adults'] ?? 0);
$children = (int)($basics['children'] ?? 0);
$paxTotal = max(0, $adults + $children);

$nights = pdf_days_between($checkin, $checkout);
if ($nights === 0 && count($days) > 1) {
    $nights = count($days) - 1;
}
$durationDays = $nights > 0 ? $nights + 1 : count($days);
$durationText = $nights > 0 ? ($nights . ' Nights / ' . max(1, $durationDays) . ' Days') : (count($days) > 0 ? count($days) . ' Days' : '—');
$paxText = $paxTotal > 0 ? trim(($adults > 0 ? $adults . ' Adult' . ($adults > 1 ? 's' : '') : '') . ($children > 0 ? (($adults > 0 ? ', ' : '') . $children . ' Child' . ($children > 1 ? 'ren' : '')) : '')) : '—';

$hotelRows = pdf_hotel_rows($days);
$vehicleRows = pdf_vehicle_rows($days);
$quoteLines = pdf_quote_lines($days, $basics, $extras, $nights);
$itineraryExists = !empty($days);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Confirmed Quote · Destinations Azerbaijan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --navy: #0d2440;
            --navy-soft: #17385d;
            --blue: #1c5ca6;
            --gold: #c99a41;
            --ink: #162130;
            --muted: #5a697c;
            --line: #dfe5ec;
            --line-soft: #edf1f6;
            --panel: #f7f9fc;
            --white: #ffffff;
        }

        html {
            font-size: 14px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #eef3f8;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        .quote-shell {
            width: min(980px, calc(100% - 36px));
            margin: 24px auto;
            background: var(--white);
            border: 1px solid var(--line);
            box-shadow: 0 12px 40px rgba(13, 36, 64, 0.08);
            position: relative;
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.045;
            color: var(--navy);
            font-size: 22px;
            line-height: 1.9;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transform: rotate(-18deg) scale(1.2);
            transform-origin: center;
            white-space: pre-wrap;
            padding: 110px 30px 0;
        }

        .top-band {
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, var(--navy) 0%, #102a49 60%, var(--navy-soft) 100%);
            color: #fff;
            padding: 26px 42px 0;
        }

        .top-band-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 24px;
        }

        .brand img {
            display: block;
            max-height: 36px;
            filter: brightness(0) invert(1) opacity(.92);
        }

        .doc-type {
            text-align: right;
        }

        .doc-eyebrow {
            font-size: 10px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: rgba(201, 154, 65, .95);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .doc-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 35px;
            line-height: .95;
            font-weight: 700;
        }

        .doc-sub {
            margin-top: 6px;
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
        }

        .sub-band {
            background: rgba(255, 255, 255, .06);
            border-top: 1px solid rgba(255, 255, 255, .08);
            display: flex;
            flex-wrap: wrap;
            gap: 16px 24px;
            padding: 11px 0 13px;
            font-size: 11px;
            color: rgba(255, 255, 255, .76);
        }

        .sub-band strong {
            color: #fff;
            font-weight: 600;
        }

        .content {
            position: relative;
            z-index: 1;
            padding: 34px 42px 42px;
        }

        .intro-grid {
            display: grid;
            grid-template-columns: 1.35fr .95fr;
            gap: 22px;
            margin-bottom: 28px;
        }

        .intro-card,
        .price-card,
        .section-box,
        .day-card {
            border: 1px solid var(--line);
            background: var(--white);
        }

        .intro-card {
            padding: 24px 24px 20px;
        }

        .quote-chip {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #edf4ff;
            border: 1px solid #cfe0f8;
            color: var(--blue);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .16em;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .intro-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 31px;
            color: var(--navy);
            margin-bottom: 6px;
        }

        .intro-copy {
            font-size: 13px;
            line-height: 1.75;
            color: var(--muted);
            margin-bottom: 18px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .meta-item {
            background: var(--panel);
            border: 1px solid var(--line-soft);
            padding: 13px 14px;
            min-height: 74px;
        }

        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 7px;
        }

        .meta-value {
            font-size: 14px;
            line-height: 1.35;
            font-weight: 600;
            color: var(--ink);
        }

        .pricing-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .price-card {
            padding: 20px 22px;
        }

        .price-card.primary {
            background: var(--navy);
            border-color: var(--navy);
            color: #fff;
        }

        .price-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .18em;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .price-card.primary .price-label {
            color: rgba(201, 154, 65, .95);
        }

        .price-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            line-height: .95;
            color: var(--navy);
            font-weight: 700;
        }

        .price-card.primary .price-value {
            color: #fff;
        }

        .price-note {
            margin-top: 7px;
            font-size: 11px;
            color: var(--muted);
        }

        .price-card.primary .price-note {
            color: rgba(255, 255, 255, .72);
        }

        .section {
            margin-top: 28px;
        }

        .section-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .section-head::before {
            content: '';
            width: 4px;
            height: 17px;
            background: var(--blue);
        }

        .section-title {
            font-size: 10px;
            letter-spacing: .2em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--navy);
        }

        .section-rule {
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .section-box {
            padding: 18px 20px;
        }

        .two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .mini-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .bullet-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .bullet-list li {
            padding-left: 16px;
            position: relative;
            font-size: 13px;
            line-height: 1.65;
            color: var(--ink);
        }

        .bullet-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: .65em;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .16em;
            text-align: left;
            color: var(--muted);
            font-weight: 800;
            padding: 0 0 10px;
            border-bottom: 1px solid var(--line);
        }

        .table tbody td {
            padding: 12px 0;
            border-bottom: 1px solid var(--line-soft);
            vertical-align: top;
            font-size: 13px;
            line-height: 1.55;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .day-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 52px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #edf4ff;
            border: 1px solid #d3e2f8;
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
        }

        .service-lines {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .service-lines span {
            display: block;
        }

        .day-cards {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .day-card {
            padding: 18px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
        }

        .day-card-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 11px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }

        .day-card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.25;
        }

        .day-card-date {
            font-size: 12px;
            color: var(--muted);
            text-align: right;
            white-space: nowrap;
        }

        .detail-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .detail-list li {
            font-size: 13px;
            line-height: 1.7;
            color: var(--ink);
        }

        .detail-list strong {
            color: var(--navy);
        }

        .transfer-desc {
            margin-top: 4px;
            font-size: 12px;
            color: var(--muted);
        }

        .activities-list {
            list-style: none;
            margin: 4px 0 0 0;
            padding: 0;
        }

        .activities-list li {
            margin: 2px 0;
        }

        .terms {
            font-size: 12px;
            line-height: 1.75;
            color: var(--muted);
        }

        .footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            font-size: 11px;
            color: var(--muted);
        }

        .footer-brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: var(--blue);
            font-weight: 700;
        }

        .empty {
            padding: 28px 10px;
            text-align: center;
            color: var(--muted);
            font-style: italic;
        }

        @media print {
            body {
                background: #fff;
            }

            .quote-shell {
                width: 100%;
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }

        @media (max-width: 820px) {
            .quote-shell {
                width: 100%;
                margin: 0;
                border-left: none;
                border-right: none;
            }

            .top-band,
            .content {
                padding-left: 20px;
                padding-right: 20px;
            }

            .top-band-main,
            .intro-grid,
            .two-col,
            .footer {
                grid-template-columns: 1fr;
                flex-direction: column;
            }

            .doc-type,
            .day-card-date {
                text-align: left;
            }

            .meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body>
<div class="quote-shell">
    <div class="watermark"><?php echo pdf_e(str_repeat('Trip# ' . $tripId . '  •  ', 90)); ?></div>

    <div class="top-band">
        <div class="top-band-main">
            <div class="brand">
                <img src="./destinations_Azerbaijan_logo.png" alt="Destinations Azerbaijan">
            </div>
            <div class="doc-type">
                <div class="doc-eyebrow">Official Travel Document</div>
                <div class="doc-title">Confirmed Quote</div>
                <div class="doc-sub">Dynamic itinerary summary generated from your selected tour data</div>
            </div>
        </div>
        <div class="sub-band">
            <div>Destination: <strong><?php echo pdf_e($destination !== '' ? $destination : 'Azerbaijan'); ?></strong></div>
            <div>Issue Date: <strong><?php echo pdf_e(date('d F Y')); ?></strong></div>
            <div>Trip ID: <strong><?php echo pdf_e($tripId); ?></strong></div>
            <div>Reference ID: <strong><?php echo pdf_e($referenceId); ?></strong></div>
        </div>
    </div>

    <div class="content">
        <div class="intro-grid">
            <div class="intro-card">
                <div class="quote-chip">Confirmed Quote</div>
                <div class="intro-title">Travel Proposal Overview</div>
                <div class="intro-copy">
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-label">Destination</div>
                        <div class="meta-value"><?php echo pdf_e($destination !== '' ? $destination : 'Azerbaijan'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Start Date</div>
                        <div class="meta-value"><?php echo pdf_e(pdf_format_date_short($checkin)); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Duration</div>
                        <div class="meta-value"><?php echo pdf_e($durationText); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Pax</div>
                        <div class="meta-value"><?php echo pdf_e($paxText); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Trip ID</div>
                        <div class="meta-value"><?php echo pdf_e($tripId); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Reference ID</div>
                        <div class="meta-value"><?php echo pdf_e($referenceId); ?></div>
                    </div>
                </div>
            </div>

            <div class="pricing-stack">
                <?php if (trim($grandTotal) !== ''): ?>
                    <div class="price-card primary">
                        <div class="price-label">Quote Price · Total</div>
                        <div class="price-value"><?php echo pdf_e($grandTotal); ?></div>
                        <div class="price-note">Total quoted amount for the current itinerary</div>
                    </div>
                <?php endif; ?>

                <?php if (trim($perPerson) !== ''): ?>
                    <div class="price-card">
                        <div class="price-label">Per Pax</div>
                        <div class="price-value"><?php echo pdf_e($perPerson); ?></div>
                        <div class="price-note">Calculated from the current passenger count</div>
                    </div>
                <?php endif; ?>

                <?php if (trim($grandTotal) === '' && trim($perPerson) === ''): ?>
                    <div class="price-card">
                        <div class="price-label">Pricing</div>
                        <div class="price-value" style="font-size:28px;">—</div>
                        <div class="price-note">Price summary becomes visible when totals are available</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Hotels / Accommodations</div>
                <div class="section-rule"></div>
            </div>
            <div class="section-box">
                <?php if ($hotelRows): ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Hotel</th>
                            <th>Room Type</th>
                            <th>Nights</th>
                            <th>City</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($hotelRows as $hotelRow): ?>
                            <tr>
                                <td><?php echo pdf_e($hotelRow['hotel']); ?></td>
                                <td><?php echo pdf_e($hotelRow['roomType'] !== '' ? $hotelRow['roomType'] : '—'); ?></td>
                                <td><?php echo pdf_e((string)$hotelRow['nights']); ?></td>
                                <td><?php echo pdf_e($hotelRow['cities'] !== '' ? $hotelRow['cities'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">Accommodation data was not found in the current itinerary.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Transportation / Vehicle</div>
                <div class="section-rule"></div>
            </div>
            <div class="section-box">
                <?php if ($vehicleRows): ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Days</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vehicleRows as $vehicleRow): ?>
                            <tr>
                                <td><?php echo pdf_e($vehicleRow['vehicle']); ?></td>
                                <td><?php echo pdf_e((string)$vehicleRow['days']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">Vehicle data was not found in the current itinerary.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Inclusions / Exclusions</div>
                <div class="section-rule"></div>
            </div>
            <div class="section-box two-col">
                <div>
                    <div class="mini-title">Inclusions</div>
                    <?php if ($quoteLines): ?>
                        <ul class="bullet-list">
                            <?php foreach ($quoteLines as $line): ?>
                                <li><?php echo pdf_e($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty" style="padding:10px 0;">No dynamic inclusions were detected.</div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="mini-title">Exclusions</div>
                    <ul class="bullet-list">
                        <li>Personal expenses, shopping, minibar, porterage and tips</li>
                        <li>Services not explicitly selected in the itinerary or extra services area</li>
                        <li>Any taxes, supplements or surcharges applied after confirmation</li>
                        <li>Early check-in, late check-out or optional activities unless selected</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Short Itinerary</div>
                <div class="section-rule"></div>
            </div>
            <div class="section-box">
                <?php if ($itineraryExists): ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th style="width:90px;">Day</th>
                            <th style="width:180px;">Date</th>
                            <th>Service</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $seenShortItineraryServices = []; ?>
                        <?php foreach ($days as $index => $day): ?>
                            <?php
                            $serviceLines = pdf_unique_short_itinerary_lines(
                                pdf_collect_day_services($day),
                                $seenShortItineraryServices
                            );
                            ?>
                            <tr>
                                <td><span class="day-index"><?php echo pdf_e('Day ' . ($index + 1)); ?></span></td>
                                <td><?php echo pdf_e(pdf_format_date_long((string)($day['date'] ?? ''))); ?></td>
                                <td>
                                    <div class="service-lines">
                                        <?php if ($serviceLines): ?>
                                            <?php foreach ($serviceLines as $serviceLine): ?>
                                                <span><?php echo pdf_e($serviceLine); ?></span>
                                            <?php endforeach; ?>
                                        <?php elseif ($index === 0): ?>
                                            <span>Selected services will appear here.</span>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">Itinerary information was not found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Detailed Itinerary</div>
                <div class="section-rule"></div>
            </div>
            <?php if ($itineraryExists): ?>
                <div class="day-cards">
                    <?php foreach ($days as $index => $day): ?>
                        <?php
                        $dayTransfers = pdf_day_transfers_list($day);
                        $firstTransfer = $dayTransfers[0] ?? ['transferA' => '', 'transferB' => '', 'transferService' => ''];
                        $transferA = $firstTransfer['transferA'];
                        $transferB = $firstTransfer['transferB'];
                        $transferService = $firstTransfer['transferService'];
                        $routeTitle = trim($transferA . (($transferA !== '' && $transferB !== '') ? ' to ' : '') . $transferB);
                        if ($routeTitle === '' && count($dayTransfers) > 1) {
                            $routeTitle = count($dayTransfers) . ' transfers';
                        }
                        if ($routeTitle === '') {
                            $activityLabels = pdf_collect_activity_labels_basic($day);
                            $routeTitle = $activityLabels[0] ?? ('Service in ' . (pdf_city_label($day['city'] ?? '') ?: 'selected city'));
                        }
                        $hotelText = trim((string)($day['hotel'] ?? ''));
                        $roomText = trim((string)($day['roomType'] ?? ''));
                        $mealText = pdf_meal_summary($day);
                        ?>
                        <div class="day-card">
                            <div class="day-card-head">
                                <div class="day-card-title"><?php echo pdf_e(pdf_day_label($index + 1) . ' - ' . $routeTitle); ?></div>
                                <div class="day-card-date"><?php echo pdf_e(pdf_format_date_long((string)($day['date'] ?? ''))); ?></div>
                            </div>
                            <ul class="detail-list">
                                <?php foreach ($dayTransfers as $trItem): ?>
                                    <?php
                                    $ta = $trItem['transferA'];
                                    $tb = $trItem['transferB'];
                                    $ts = $trItem['transferService'];
                                    $transferSchedule = pdf_get_transfer_schedule($ta, $tb, $ts, $trItem['dutyCode'] ?? null);
                                    ?>
                                    <?php if ($ta !== '' || $tb !== ''): ?>
                                    <li>
                                        <strong>Transfer:</strong>
                                        <?php echo pdf_e(trim($ta . (($ta !== '' && $tb !== '') ? ' to ' : '') . $tb)); ?>
                                        <?php if ($ts !== ''): ?>
                                            <strong>/</strong> <?php echo pdf_e($ts); ?>
                                        <?php endif; ?>
                                        <?php if ($transferSchedule !== ''): ?>
                                            <div class="transfer-desc"><?php echo nl2br(pdf_e($transferSchedule)); ?></div>
                                        <?php endif; ?>
                                    </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($hotelText !== ''): ?>
                                    <?php
                                    $hotelExtrasPdf = isset($day['hotelExtras']) && is_array($day['hotelExtras']) ? $day['hotelExtras'] : [];
                                    $hotelExtrasLine = '';
                                    foreach ($hotelExtrasPdf as $hex) {
                                        if (!is_array($hex)) {
                                            continue;
                                        }
                                        $ht = trim((string)($hex['type'] ?? ''));
                                        if ($ht === '') {
                                            continue;
                                        }
                                        $hq = (int)($hex['qty'] ?? 1);
                                        if ($hq < 1) {
                                            $hq = 1;
                                        }
                                        $hotelExtrasLine .= ($hotelExtrasLine !== '' ? ', ' : '') . $ht . ($hq > 1 ? ' x' . $hq : '');
                                    }
                                    ?>
                                    <li><strong>Hotel:</strong> <?php echo pdf_e($hotelText . ($roomText !== '' ? ' / ' . $roomText : '') . ($hotelExtrasLine !== '' ? ' + ' . $hotelExtrasLine : '')); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($day['city'])): ?>
                                    <li><strong>City:</strong> <?php echo pdf_e(pdf_city_label($day['city'])); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($day['activities']) && is_array($day['activities'])): ?>
                                    <li>
                                        <strong>Activities:</strong>
                                        <ul class="activities-list">
                                            <?php foreach ($day['activities'] as $activity): ?>
                                                <?php
                                                if (!is_array($activity)) {
                                                    continue;
                                                }
                                                $tour = trim((string)($activity['tour'] ?? ''));
                                                $entrance = trim((string)($activity['entrance'] ?? ''));
                                                if ($tour === '' && $entrance === '') {
                                                    continue;
                                                }
                                                $baseLabel = '';
                                                if ($tour !== '' && $entrance !== '') {
                                                    $baseLabel = $tour . ' - ' . $entrance;
                                                } elseif ($tour !== '') {
                                                    $baseLabel = $tour;
                                                } elseif ($entrance !== '') {
                                                    $baseLabel = $entrance;
                                                }
                                                if ($baseLabel === '') {
                                                    continue;
                                                }
                                                $desc = pdf_get_activity_description($tour, $entrance);
                                                ?>
                                                <li>
                                                    <strong><?php echo pdf_e($baseLabel); ?></strong>
                                                    <?php if ($desc !== ''): ?>
                                                        : <?php echo pdf_e($desc); ?>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                                <?php if ($mealText !== ''): ?>
                                    <li><strong>Meals:</strong> <?php echo pdf_e($mealText); ?></li>
                                <?php endif; ?>
                                <?php if (empty($dayTransfers) && $hotelText === '' && !pdf_collect_activity_labels_basic($day) && $mealText === ''): ?>
                                    <li>Selected services for this day will appear here once day details are filled.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="section-box">
                    <div class="empty">Detailed itinerary is empty because no day data was received.</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($extras): ?>
            <div class="section">
                <div class="section-head">
                    <div class="section-title">Extra Services</div>
                    <div class="section-rule"></div>
                </div>
                <div class="section-box">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Qty</th>
                            <th>Notes</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($extras as $extra): ?>
                            <?php
                            $notes = '';
                            if (isset($extra['description']) && trim((string)$extra['description']) !== '') {
                                $notes = (string)$extra['description'];
                            } elseif (isset($extra['comments']) && trim((string)$extra['comments']) !== '') {
                                $notes = (string)$extra['comments'];
                            }
                            ?>
                            <tr>
                                <td><?php echo pdf_e((string)($extra['service'] ?? '—')); ?></td>
                                <td><?php echo pdf_e((string)($extra['date'] ?? '—')); ?></td>
                                <td><?php echo pdf_e((string)($extra['qty'] ?? '1')); ?></td>
                                <td><?php echo $notes !== '' ? pdf_e($notes) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-head">
                <div class="section-title">Terms And Conditions</div>
                <div class="section-rule"></div>
            </div>
            <div class="section-box terms">
                Rates in this document reflect the current dynamic selection in the builder and may change when dates, suppliers, room types, services or passenger counts are updated.
                Standard hotel check-in / check-out timings depend on supplier policy. Optional services, taxes, supplements and charges outside the selected package are not included unless shown in this document.
                Final confirmation remains subject to service availability at the time of booking.
            </div>
        </div>

        <div class="footer">
            <div class="footer-brand">Destinations Azerbaijan</div>
            <div>Confidential · Generated on <?php echo pdf_e(date('d F Y H:i')); ?></div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>