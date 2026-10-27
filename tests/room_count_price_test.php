<?php

$source = file_get_contents(dirname(__DIR__) . '/tour-builder.php');

$expectations = [
    'Room count is available in each day row' => 'day-room-count-input',
    'Room count is saved with the itinerary' => 'dayData.roomCount =',
    'Multiple room types are saved with the itinerary' => 'dayData.rooms = collectDayRooms(card)',
    'Users can add another room type' => 'day-add-room-type',
    'Explicit room capacity is detected' => '(?:p|pax|person)',
    'Suggested rooms cover all guests' => 'Math.ceil(getGuestCounts().totalPax / capacity)',
    'Accommodation uses each room price multiplied by its room count' => 'accommodationPerDay.push(roomPrice * room.roomCount)',
    'Accommodation summary exposes room nights' => "roomNights + ' room-night(s)'",
];

foreach ($expectations as $message => $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, "FAIL: {$message}" . PHP_EOL);
        exit(1);
    }
}

if (strpos($source, 'accommodationTotal *= adultMultiplier') !== false) {
    fwrite(STDERR, 'FAIL: Accommodation must not be multiplied by the adult count' . PHP_EOL);
    exit(1);
}

echo 'PASS: Accommodation is priced per room and room count is persisted' . PHP_EOL;
