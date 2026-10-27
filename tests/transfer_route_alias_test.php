<?php

require dirname(__DIR__) . '/api/tour_config_functions.php';

function failTransferAliasTest(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

$prices = addTransferRouteAliases([
    'Airport|Hotel|1 way Transfer' => ['Sprinter 18' => 35.0],
    'Baku|Gabala|Day Trip' => ['Sprinter 18' => 147.0],
]);

if (isset($prices['Hotel|Hotel|1 way Transfer'])) {
    failTransferAliasTest('Airport price leaked into an independent Hotel-origin route');
}

if (($prices['Airport|Hotel|1 way Transfer']['Sprinter 18'] ?? null) !== 35.0) {
    failTransferAliasTest('The original airport route price changed');
}

if (($prices['Hotel|Gabala|Day Trip']['Sprinter 18'] ?? null) !== 147.0) {
    failTransferAliasTest('The supported Baku/Hotel Day Trip alias was not preserved');
}

echo 'PASS: transfer route prices remain isolated by route and service' . PHP_EOL;
