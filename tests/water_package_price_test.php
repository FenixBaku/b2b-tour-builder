<?php

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/api/tour_config_functions.php';

$config = getTourConfig(dbConnect());
$water = $config['water'] ?? [];

$twoBottle = null;
foreach ($water as $option) {
    if (is_array($option) && ($option['value'] ?? '') === '2x 300ml daily') {
        $twoBottle = $option;
        break;
    }
}

if (!$twoBottle || (float)($twoBottle['price'] ?? 0) !== 2.0) {
    fwrite(STDERR, 'FAIL: 2x 300ml daily is not exposed with its 2 USD per-person daily price' . PHP_EOL);
    exit(1);
}

$source = file_get_contents(dirname(__DIR__) . '/tour-builder.php');
if (strpos($source, "addRow('Water Package'") === false) {
    fwrite(STDERR, 'FAIL: Water Package is not added to the package summary' . PHP_EOL);
    exit(1);
}

echo 'PASS: Water Package price is available and included in the summary' . PHP_EOL;
