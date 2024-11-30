


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
            createUser();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getUser($_GET['id']);
            } else {
                getUsers();
            }
            break;
        case 'PUT':
            updateUser();
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteUser($_GET['id']);
            } else {
                echo json_encode(["message" => "User ID required"]);
            }
            break;
        default:
            echo json_encode(["message" => "Method not supported"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["message" => "An unexpected error occurred", "error" => $e->getMessage()]);
}

function createUser(): void
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['name'], $data['email'], $data['password'], $data['role_id'])) {
        $name = $data['name'];
        $email = $data['email'];
        $nid = $data['nid'] ?? null;
        $phone = $data['phone'] ?? null;
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $roleId = $data['role_id'];

        try {
            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT * FROM User WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(["status" => 200, "message" => "Email already exists"]);
                return;
            }

            $stmt = $pdo->prepare("INSERT INTO User (Name, Email, NID, Phone, Password, RoleId) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $nid, $phone, $password, $roleId]);
            echo json_encode(["message" => "User created successfully", "UserId" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function getUser($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE UserId = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(["status"=>200 , "users" => $user]);
        } else {
            echo json_encode(["status" => 200, "message" => "User not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function getUsers()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM User");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status"=>200 , "users" => $users]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateUser()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['id'], $data['name'], $data['email'], $data['user_status'], $data['role_id'])) {
        $id = $data['id'];
        $name = $data['name'];
        $email = $data['email'];
        $nid = $data['nid'] ?? null;
        $phone = $data['phone'] ?? null;
        $userStatus = $data['user_status'];
        $roleId = $data['role_id'];

        try {
            $stmt = $pdo->prepare("UPDATE User SET Name = ?, Email = ?, NID = ?, Phone = ?, User_Status = ?, RoleId = ?, Last_Updated = NOW() WHERE UserId = ?");
            $stmt->execute([$name, $email, $nid, $phone, $userStatus, $roleId, $id]);

            echo json_encode(["status" => 200, "message" => "User updated successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function deleteUser($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM User WHERE UserId = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(["status" => 200, "message" => "User deleted successfully"]);
        } else {
            echo json_encode(["status" => 200, "message" => "User not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}
?>
