<?php

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/order_pricing.php';

function failOrderPricingTest(string $message): void
{
    fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
    exit(1);
}

$pdo = dbConnect();
if (!$pdo) {
    failOrderPricingTest('database connection failed');
}

$duty3 = fetchTransferDutyByCode($pdo, 3);
$sprinter = $duty3['prices']['Sprinter 18'] ?? null;
if ($sprinter === null) {
    failOrderPricingTest('duty 3 needs a Sprinter 18 price for this test');
}

$order = [
    'basics' => ['adults' => '2', 'children' => '0', 'vehicle' => 'Sprinter 18', 'visa' => 'Urgent (3 Hours)', 'water' => ''],
    'days' => [[
        'city' => 'baku',
        'transfers' => [['dutyCode' => 3, 'transferA' => 'Baku', 'transferB' => 'Shamakhi', 'transferService' => 'Day Trip']],
    ]],
    // Brauzerdən gələn qiymət nəzərə alınmamalıdır
    'extraServices' => [['service' => 'does-not-exist', 'qty' => '1', 'price' => '9999']],
];
$result = order_compute_total($pdo, $order);
$expected = $sprinter + 65 * 2;
if (abs($result['total'] - $expected) > 0.001) {
    failOrderPricingTest("expected {$expected} (duty 3 Sprinter + urgent visa x2), got {$result['total']}");
}

echo 'PASS: order totals are calculated on the server from database prices' . PHP_EOL;
