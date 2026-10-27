<?php

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/api/tour_config_functions.php';

function failMealOptionsTest(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

// Check-in tarixi ilə (sezon qiymətləri) hər yemək bir dəfə görünməlidir — əvvəllər 3-4 dəfə görünürdü
$meals = fetchMealsByCity(dbConnect(), '2026-06-15');
foreach ($meals as $city => $types) {
    foreach ($types as $type => $list) {
        $names = array_column($list, 'value');
        if (count($names) !== count(array_unique($names))) {
            failMealOptionsTest("{$city} {$type} has duplicate meal options");
        }
    }
}

echo 'PASS: lunch and dinner options appear once per city with season prices' . PHP_EOL;
