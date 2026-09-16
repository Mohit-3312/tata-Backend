<?php
// =========================================================
// CORS HEADERS
// =========================================================
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === "http://localhost:5173" || $origin === "http://127.0.0.1:5173") {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json; charset=UTF-8");

// Handle Preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =========================================================
// DATABASE CONNECTION
// =========================================================
require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    sendError("Database connection failed: " . $conn->connect_error);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// =========================================================
// ROUTING
// =========================================================
try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $action);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($action === 'car') {
                insertCar($conn, $data);
            } elseif ($action === 'evcar') {
                insertEvCar($conn, $data);
            } else {
                sendError("Invalid POST action.");
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($action === 'car') {
                updateCar($conn, $data);
            } elseif ($action === 'evcar') {
                updateEvCar($conn, $data);
            } else {
                sendError("Invalid PUT action.");
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendError("ID is required.");
            }
            $id = (int)$id;

            $tableMap = [
                'car'       => 'cars_details',
                'evcar'     => 'ev_car_details',
                'contact'   => 'contact_inquiries',
                'service'   => 'service_bookings',
                'testdrive' => 'test_drive_bookings'
            ];

            if (isset($tableMap[$action])) {
                deleteRecord($conn, $tableMap[$action], $id);
            } else {
                sendError("Invalid DELETE action.");
            }
            break;

        default:
            sendError("Unsupported HTTP method.");
            break;
    }
} catch (Exception $e) {
    sendError($e->getMessage());
}

$conn->close();

// =========================================================
// GET HANDLERS
// =========================================================
function handleGet($conn, $action) {
    if ($action === 'cars') {
        echo json_encode(getCars($conn));
    } elseif ($action === 'evcars') {
        echo json_encode(getEvCars($conn));
    } elseif ($action === 'contacts') {
        echo json_encode(getContacts($conn));
    } elseif ($action === 'services') {
        echo json_encode(getServices($conn));
    } elseif ($action === 'testdrives') {
        echo json_encode(getTestDrives($conn));
    } else {
        $result = [
            "cars"       => getCars($conn),
            "evcars"     => getEvCars($conn),
            "contacts"   => getContacts($conn),
            "services"   => getServices($conn),
            "testdrives" => getTestDrives($conn)
        ];
        echo json_encode($result);
    }
}

function getCars($conn) {
    $res = $conn->query("SELECT * FROM cars_details ORDER BY id DESC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
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
    return $list;
}

function getEvCars($conn) {
    $res = $conn->query("SELECT * FROM ev_car_details ORDER BY id DESC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            "id"              => (int)$row["id"],
            "name"            => $row["name"],
            "tagline"         => $row["tagline"],
            "price"           => (float)$row["price"],
            "claimedrange"    => (int)$row["claimedrange"],
            "batterycapacity" => (int)$row["batterycapacity"],
            "fastchargertime" => (int)$row["fastchargertime"],
            "imageurl"        => $row["imageurl"],
            "warranty"        => (float)$row["warranty"]
        ];
    }
    return $list;
}

function getContacts($conn) {
    $res = $conn->query("SELECT * FROM contact_inquiries ORDER BY id DESC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            "id"           => (int)$row["id"],
            "name"         => $row["name"],
            "email"        => $row["email"],
            "phone"        => $row["phone"],
            "subject"      => $row["subject"],
            "message"      => $row["message"],
            "submitted_at" => $row["submitted_at"] ?? null
        ];
    }
    return $list;
}

function getServices($conn) {
    $res = $conn->query("SELECT * FROM service_bookings ORDER BY id DESC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            "id"             => (int)$row["id"],
            "customer_name"  => $row["customer_name"],
            "phone"          => $row["phone"],
            "car_model"      => $row["car_model"],
            "service_type"   => $row["service_type"],
            "preferred_date" => $row["preferred_date"],
            "notes"          => $row["notes"],
            "created_at"     => $row["created_at"] ?? null
        ];
    }
    return $list;
}

function getTestDrives($conn) {
    $res = $conn->query("SELECT * FROM test_drive_bookings ORDER BY id DESC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            "id"                  => (int)$row["id"],
            "customer_name"       => $row["customer_name"],
            "phone"               => $row["phone"],
            "email"               => $row["email"],
            "city"                => $row["city"],
            "car_name"            => $row["car_name"],
            "car_category"        => $row["car_category"],
            "preferred_date"      => $row["preferred_date"],
            "preferred_slot"      => $row["preferred_slot"],
            "test_drive_location" => $row["test_drive_location"],
            "created_at"          => $row["created_at"] ?? null
        ];
    }
    return $list;
}

// =========================================================
// INSERT / UPDATE / DELETE
// =========================================================
function insertCar($conn, $d) {
    $stmt = $conn->prepare("INSERT INTO cars_details (name, type, tagline, price, rangeOrMileage, safetyRating, fuelType, imageUrl, popular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $name = $d['name'] ?? '';
    $type = $d['type'] ?? '';
    $tagline = $d['tagline'] ?? '';
    $price = (float)($d['price'] ?? 0);
    $rangeOrMileage = (int)($d['rangeOrMileage'] ?? 0);
    $safetyRating = (int)($d['safetyRating'] ?? 0);
    $fuelType = $d['fuelType'] ?? '';
    $imageUrl = $d['imageUrl'] ?? '';
    $popular = !empty($d['popular']) ? 1 : 0;

    $stmt->bind_param("sssdiissi", $name, $type, $tagline, $price, $rangeOrMileage, $safetyRating, $fuelType, $imageUrl, $popular);
    $stmt->execute();
    $stmt->close();
    sendSuccess("Record inserted successfully.");
}

function updateCar($conn, $d) {
    $stmt = $conn->prepare("UPDATE cars_details SET name=?, type=?, tagline=?, price=?, rangeOrMileage=?, safetyRating=?, fuelType=?, imageUrl=?, popular=? WHERE id=?");
    $id = (int)($d['id'] ?? 0);
    $name = $d['name'] ?? '';
    $type = $d['type'] ?? '';
    $tagline = $d['tagline'] ?? '';
    $price = (float)($d['price'] ?? 0);
    $rangeOrMileage = (int)($d['rangeOrMileage'] ?? 0);
    $safetyRating = (int)($d['safetyRating'] ?? 0);
    $fuelType = $d['fuelType'] ?? '';
    $imageUrl = $d['imageUrl'] ?? '';
    $popular = !empty($d['popular']) ? 1 : 0;

    $stmt->bind_param("sssdiissii", $name, $type, $tagline, $price, $rangeOrMileage, $safetyRating, $fuelType, $imageUrl, $popular, $id);
    $stmt->execute();
    $stmt->close();
    sendSuccess("Record updated successfully.");
}

function insertEvCar($conn, $d) {
    $stmt = $conn->prepare("INSERT INTO ev_car_details (name, tagline, price, claimedrange, batterycapacity, fastchargertime, imageurl, warranty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $name = $d['name'] ?? '';
    $tagline = $d['tagline'] ?? '';
    $price = (float)($d['price'] ?? 0);
    $claimedrange = (int)($d['claimedrange'] ?? 0);
    $batterycapacity = (int)($d['batterycapacity'] ?? 0);
    $fastchargertime = (int)($d['fastchargertime'] ?? 0);
    $imageurl = $d['imageurl'] ?? '';
    $warranty = (float)($d['warranty'] ?? 0);

    $stmt->bind_param("ssdiisid", $name, $tagline, $price, $claimedrange, $batterycapacity, $fastchargertime, $imageurl, $warranty);
    $stmt->execute();
    $stmt->close();
    sendSuccess("Record inserted successfully.");
}

function updateEvCar($conn, $d) {
    $stmt = $conn->prepare("UPDATE ev_car_details SET name=?, tagline=?, price=?, claimedrange=?, batterycapacity=?, fastchargertime=?, imageurl=?, warranty=? WHERE id=?");
    $id = (int)($d['id'] ?? 0);
    $name = $d['name'] ?? '';
    $tagline = $d['tagline'] ?? '';
    $price = (float)($d['price'] ?? 0);
    $claimedrange = (int)($d['claimedrange'] ?? 0);
    $batterycapacity = (int)($d['batterycapacity'] ?? 0);
    $fastchargertime = (int)($d['fastchargertime'] ?? 0);
    $imageurl = $d['imageurl'] ?? '';
    $warranty = (float)($d['warranty'] ?? 0);

    $stmt->bind_param("ssdiisidi", $name, $tagline, $price, $claimedrange, $batterycapacity, $fastchargertime, $imageurl, $warranty, $id);
    $stmt->execute();
    $stmt->close();
    sendSuccess("Record updated successfully.");
}

function deleteRecord($conn, $table, $id) {
    $stmt = $conn->prepare("DELETE FROM $table WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    sendSuccess("Record deleted successfully.");
}

// =========================================================
// RESPONSE HELPERS
// =========================================================
function sendSuccess($message) {
    echo json_encode(["success" => true, "message" => $message]);
    exit();
}

function sendError($message) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $message]);
    exit();
}
?>