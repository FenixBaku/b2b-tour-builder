<?php

$source = file_get_contents(dirname(__DIR__) . '/tour-builder.php');

$expectations = [
    'Region / City starts with No select' => '<option value="" selected>No select</option>',
    'Empty placeholder remains a selectable value' => "opt.value = '';",
    'Invalid previous values do not auto-select the first result' => "!currentVal || !optionsIncludeVal(currentVal)",
];

foreach ($expectations as $message => $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
        exit(1);
    }
}

if (substr_count($source, "placeholder = 'No select';") < 4) {
    fwrite(STDERR, 'FAIL: Hotel, room type, lunch and dinner must all use No select' . PHP_EOL);
    exit(1);
}

echo 'PASS: day dropdowns default to No select' . PHP_EOL;
