<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get($conn);
        break;
    case 'POST':
        handle_post($conn);
        break;
    case 'PUT':
        handle_put($conn);
        break;
    case 'DELETE':
        handle_delete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "SELECT * FROM distributors WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $distributor = $result->fetch_assoc();
            http_response_code(200);
            echo json_encode($distributor);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Distributor not found."));
        }
    } else {
        $query = "SELECT * FROM distributors";
        $result = $conn->query($query);
        $distributors = array();
        while ($row = $result->fetch_assoc()) {
            $distributors[] = $row;
        }
        http_response_code(200);
        echo json_encode($distributors);
    }
}

function handle_post($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name)) {
        $name = htmlspecialchars(strip_tags($data->name));
        $contact_person = isset($data->contact_person) ? htmlspecialchars(strip_tags($data->contact_person)) : null;
        $contact_email = isset($data->contact_email) ? htmlspecialchars(strip_tags($data->contact_email)) : null;
        $phone = isset($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;

        $query = "INSERT INTO distributors (name, contact_person, contact_email, phone) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $name, $contact_person, $contact_email, $phone);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Distributor was created."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create distributor."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create distributor. Name is required."));
    }
}

function handle_put($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['id']) && !empty($data)) {
        $id = intval($_GET['id']);

        $name = htmlspecialchars(strip_tags($data->name));
        $contact_person = htmlspecialchars(strip_tags($data->contact_person));
        $contact_email = htmlspecialchars(strip_tags($data->contact_email));
        $phone = htmlspecialchars(strip_tags($data->phone));

        $query = "UPDATE distributors SET name=?, contact_person=?, contact_email=?, phone=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $name, $contact_person, $contact_email, $phone, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Distributor was updated."));
            } else {
                http_response_code(200);
                echo json_encode(array("message" => "No changes detected or distributor not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update distributor."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update distributor. ID or data is missing."));
    }
}

function handle_delete($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $query = "DELETE FROM distributors WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Distributor was deleted."));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Distributor not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete distributor."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to delete distributor. ID is missing."));
    }
}

close_connection($conn);
?>
