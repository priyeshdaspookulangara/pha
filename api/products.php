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
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            http_response_code(200);
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Product not found."));
        }
    } else {
        $query = "SELECT * FROM products";
        $result = $conn->query($query);
        $products = array();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        http_response_code(200);
        echo json_encode($products);
    }
}

function handle_post($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name) && isset($data->reorder_level)) {
        $name = htmlspecialchars(strip_tags($data->name));
        $description = isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : '';
        $category = isset($data->category) ? htmlspecialchars(strip_tags($data->category)) : '';
        $reorder_level = intval($data->reorder_level);

        $query = "INSERT INTO products (name, description, category, reorder_level) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $description, $category, $reorder_level);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Product was created."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create product."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create product. Data is incomplete."));
    }
}

function handle_put($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['id']) && !empty($data)) {
        $id = intval($_GET['id']);

        $query = "UPDATE products SET name=?, description=?, category=?, reorder_level=? WHERE id=?";
        $stmt = $conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data->name));
        $description = htmlspecialchars(strip_tags($data->description));
        $category = htmlspecialchars(strip_tags($data->category));
        $reorder_level = intval($data->reorder_level);

        $stmt->bind_param("sssii", $name, $description, $category, $reorder_level, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Product was updated."));
            } else {
                http_response_code(200);
                echo json_encode(array("message" => "No changes detected or product not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update product."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update product. ID or data is missing."));
    }
}

function handle_delete($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Product was deleted."));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Product not found."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to delete product."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to delete product. ID is missing."));
    }
}

close_connection($conn);
?>
