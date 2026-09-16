<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$query = "SELECT * FROM cars_details";
$result = $conn->query($query);

$cars = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cars[] = [
            "id"             => (int)$row["id"],
            "name"           => $row["name"],
            "type"           => $row["type"],
            "tagline"        => $row["tagline"],
            "price"          => (float)$row["price"],
            "rangeOrMileage" => (int)$row["rangeOrMileage"],
            "safetyRating"   => (int)$row["safetyRating"],
            "fuelType"       => $row["fuelType"],
            "imageUrl"       => $row["imageUrl"],
            "popular"        => (bool)$row["popular"]
        ];
    }
}

echo json_encode($cars);

$conn->close();
?>