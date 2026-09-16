<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$responseMap = [];

$data = json_decode(file_get_contents("php://input"), true);

$customerName  = $data['customerName'] ?? '';
$phone         = $data['phone'] ?? '';
$carModel      = $data['carModel'] ?? '';
$serviceType   = $data['serviceType'] ?? '';
$preferredDate = $data['preferredDate'] ?? '';
$notes         = $data['notes'] ?? '';

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$query = "INSERT INTO service_bookings (customer_name, phone, car_model, service_type, preferred_date, notes) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("ssssss", $customerName, $phone, $carModel, $serviceType, $preferredDate, $notes);
    if ($stmt->execute()) {
        $responseMap = [
            "status" => "success",
            "message" => "Service appointment booked successfully"
        ];
    } else {
        $responseMap = [
            "status" => "error",
            "message" => "Failed to book appointment: " . $stmt->error
        ];
    }
    $stmt->close();
} else {
    $responseMap = [
        "status" => "error",
        "message" => "Query preparation failed: " . $conn->error
    ];
}

$conn->close();
echo json_encode($responseMap);
?>