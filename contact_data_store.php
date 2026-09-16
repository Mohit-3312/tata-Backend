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

$name    = $data['name'] ?? '';
$email   = $data['email'] ?? '';
$phone   = $data['phone'] ?? '';
$subject = $data['subject'] ?? '';
$message = $data['message'] ?? '';

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$query = "INSERT INTO contact_inquiries (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    if ($stmt->execute()) {
        $responseMap = [
            "status" => "success",
            "message" => "Contact details stored successfully"
        ];
    } else {
        $responseMap = [
            "status" => "error",
            "message" => "Failed to store details: " . $stmt->error
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