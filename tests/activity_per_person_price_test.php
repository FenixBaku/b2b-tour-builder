<?php

$source = file_get_contents(dirname(__DIR__) . '/tour-builder.php');

$expectations = [
    'Per Person and Per Pax labels are detected case-insensitively' => "/\\bper\\s*(?:person|pax)\\b/i",
    'Per-person activity prices default to the complete guest count' => '? Math.max(1, totalPax)',
    'The activity ticket editor defaults per-person services to every guest' => '? Math.max(1, getGuestCounts().totalPax)',
    'A saved manual ticket count is preserved' => 'if (!rawTickets || isNaN(tickets) || tickets < 1)',
];

foreach ($expectations as $message => $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
        exit(1);
    }
}

echo 'PASS: Per-person activity prices default to every guest and allow a manual ticket count' . PHP_EOL;
