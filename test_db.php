<?php
// Yalnız CLI üçün: php test_db.php (veb-dən cədvəl siyahısını göstərməsin)
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require 'config.php';
$pdo = dbConnect();
if (!$pdo) {
    die("Connection failed\n");
}
$stmt = $pdo->query('SHOW TABLES');
if ($stmt) {
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} else {
    echo "Query failed\n";
}
