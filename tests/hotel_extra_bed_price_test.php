<?php

$source = file_get_contents(dirname(__DIR__) . '/tour-builder.php');

$expectations = [
    'Extra Bed is detected independently of adult or child wording' => "if (/\\bextra\\s*bed\\b/i.test(t)) return 1;",
    'Extra Bed summary is displayed as a bed quantity' => "const quantityLabel = isExtraBed ? qty + ' bed(s)'",
    'Hotel-extra total still respects the selected quantity' => 'const lineTotal = unit * qty * mult;',
];

foreach ($expectations as $message => $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
        exit(1);
    }
}

echo 'PASS: Extra Bed is charged per selected bed instead of per guest' . PHP_EOL;
