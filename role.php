<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require 'Connection.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            createRole();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getRole($_GET['id']);
            } else {
                getRoles();
            }
            break;
        case 'PUT':
            updateRole();
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteRole($_GET['id']);
            } else {
                echo json_encode(["message" => "Role ID required"]);
            }
            break;
        default:
            echo json_encode(["message" => "Method not supported"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["message" => "An unexpected error occurred", "error" => $e->getMessage()]);
}

function createRole()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['role_name'])) {
        $roleName = $data['role_name'];

        try {
            $stmt = $pdo->prepare("INSERT INTO Role (Role_Name) VALUES (?)");
            $stmt->execute([$roleName]);
            echo json_encode([
                "status" => 201,
                "message" => "Role created successfully",
                "Role_Id" => $pdo->lastInsertId()
            ]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function getRole($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Role WHERE Role_Id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            echo json_encode($role);
        } else {
            echo json_encode(["status" => 404, "message" => "Role not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function getRoles()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM Role");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($roles);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRole()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['id'], $data['role_name'])) {
        $id = $data['id'];
        $roleName = $data['role_name'];

        try {
            $stmt = $pdo->prepare("UPDATE Role SET Role_Name = ?, Created_At = CURRENT_TIMESTAMP WHERE Role_Id = ?");
            $stmt->execute([$roleName, $id]);

            if ($stmt->rowCount()) {
                echo json_encode(["status" => 200, "message" => "Role updated successfully"]);
            } else {
                echo json_encode(["status" => 404, "message" => "Role not found"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function deleteRole($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM Role WHERE Role_Id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(["status" => 200, "message" => "Role deleted successfully"]);
        } else {
            echo json_encode(["status" => 404, "message" => "Role not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}
?>
