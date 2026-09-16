<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'test';
$port = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();

if ($host !== '127.0.0.1' && $host !== 'localhost') {
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $connected = $conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $connected = $conn->real_connect($host, $user, $pass, $db, $port);
}

if (!$connected) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit();
}
?>