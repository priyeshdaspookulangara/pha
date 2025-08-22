<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_notifications($conn);
        break;
    case 'POST':
        handle_post_notifications($conn);
        break;
    case 'PUT':
        handle_put_notifications($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get_notifications($conn) {
    if (isset($_GET['doctor_id'])) {
        $doctor_id = intval($_GET['doctor_id']);

        $query = "SELECT * FROM notifications WHERE doctor_id = ? ORDER BY created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = array();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        http_response_code(200);
        echo json_encode($notifications);
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Doctor ID is required."));
    }
}

function handle_post_notifications($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->doctor_id) && !empty($data->message)) {
        $doctor_id = intval($data->doctor_id);
        $message = htmlspecialchars(strip_tags($data->message));

        $query = "INSERT INTO notifications (doctor_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $doctor_id, $message);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Notification created successfully."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create notification."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create notification. Data is incomplete."));
    }
}

function handle_put_notifications($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Mark the notification as read
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Notification marked as read."));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Notification not found or already marked as read."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update notification."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Notification ID is required."));
    }
}

close_connection($conn);
?>
