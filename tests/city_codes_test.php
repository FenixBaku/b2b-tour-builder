<?php

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/api/tour_config_functions.php';

function failCityCodeTest(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

$cases = [
    'baku' => 'BAK', 'BAK' => 'BAK', 'gabala' => 'QBL', 'qabala' => 'QBL', 'Qəbələ' => 'QBL',
    'shaki' => 'SHK', 'Şəki' => 'SHK', 'shamakhi' => 'SHM', 'shahdag' => 'SHD', 'unknown' => '',
];
foreach ($cases as $input => $expected) {
    if (city_code($input) !== $expected) {
        failCityCodeTest("city_code('{$input}') should be '{$expected}', got '" . city_code($input) . "'");
    }
}

// Excel vərəq adları 31 simvolda kəsilir: "Tufandag Mountain Resort - Gaba"
if (city_code_from_text('Tufandag Mountain Resort - Gaba') !== 'QBL' || city_code_from_text('LakeSide Hotel - Gabala') !== 'QBL') {
    failCityCodeTest('hotel sheet names must resolve to QBL');
}
if (city_code_from_text('Adisson') !== '') {
    failCityCodeTest('a sheet name without a city must not resolve to a city');
}

$config = getTourConfig(dbConnect());
foreach (['mealsByCity', 'hotelsByRegion', 'transfersByRegion'] as $key) {
    foreach (array_keys($config[$key] ?? []) as $cityKey) {
        if (!isset(CITY_REGISTRY[$cityKey])) {
            failCityCodeTest("{$key} has a non-code key '{$cityKey}'");
        }
    }
}
if (!in_array('SHM', $config['cities'], true)) {
    failCityCodeTest('Shamakhi has activities and must be selectable');
}

echo 'PASS: cities are stored and served as codes (BAK, QBL, ...)' . PHP_EOL;
