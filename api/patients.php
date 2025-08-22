<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_patients($conn);
        break;
    case 'POST':
        handle_post_patients($conn);
        break;
    case 'PUT':
        handle_put_patients($conn);
        break;
    case 'DELETE':
        handle_delete_patients($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get_patients($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "SELECT * FROM patients WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $patient = $result->fetch_assoc();
            http_response_code(200);
            echo json_encode($patient);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Patient not found."));
        }
    } else {
        $query = "SELECT * FROM patients";
        $result = $conn->query($query);
        $patients = array();
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        http_response_code(200);
        echo json_encode($patients);
    }
}

function handle_post_patients($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name)) {
        $name = htmlspecialchars(strip_tags($data->name));
        $contact_number = isset($data->contact_number) ? htmlspecialchars(strip_tags($data->contact_number)) : '';
        $address = isset($data->address) ? htmlspecialchars(strip_tags($data->address)) : '';

        $query = "INSERT INTO patients (name, contact_number, address) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $name, $contact_number, $address);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Patient was created.", "patient_id" => $stmt->insert_id));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create patient."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create patient. Name is required."));
    }
}

function handle_put_patients($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['id']) && !empty($data)) {
        $id = intval($_GET['id']);

        $name = htmlspecialchars(strip_tags($data->name));
        $contact_number = htmlspecialchars(strip_tags($data->contact_number));
        $address = htmlspecialchars(strip_tags($data->address));

        $query = "UPDATE patients SET name=?, contact_number=?, address=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $contact_number, $address, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Patient was updated."));
            } else {
                http_response_code(200);
                echo json_encode(array("message" => "No changes detected or patient not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update patient."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update patient. ID or data is missing."));
    }
}

function handle_delete_patients($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $query = "DELETE FROM patients WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Patient was deleted."));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Patient not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete patient."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to delete patient. ID is missing."));
    }
}

close_connection($conn);
?>
