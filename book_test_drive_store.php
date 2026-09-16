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

$customerName      = $data['customerName'] ?? '';
$phone             = $data['phone'] ?? '';
$email             = $data['email'] ?? '';
$city              = $data['city'] ?? '';
$carName           = $data['carName'] ?? '';
$carCategory       = $data['carCategory'] ?? '';
$preferredDate     = $data['preferredDate'] ?? '';
$preferredSlot     = $data['preferredSlot'] ?? '';
$testDriveLocation = $data['testDriveLocation'] ?? '';

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$query = "INSERT INTO test_drive_bookings (customer_name, phone, email, city, car_name, car_category, preferred_date, preferred_slot, test_drive_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("sssssssss", $customerName, $phone, $email, $city, $carName, $carCategory, $preferredDate, $preferredSlot, $testDriveLocation);
    if ($stmt->execute()) {
        $responseMap = [
            "status" => "success",
            "message" => "Test drive booked successfully"
        ];
    } else {
        $responseMap = [
            "status" => "error",
            "message" => "Failed to book test drive: " . $stmt->error
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