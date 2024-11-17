<?php
header("Content-Type: application/json");
require 'Connection.php';

$method = $_SERVER['REQUEST_METHOD'];

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

function createUser()
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

        $stmt = $pdo->prepare("INSERT INTO User (Name, Email, NID, Phone, Password, RoleId) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $nid, $phone, $password, $roleId]);

        echo json_encode(["message" => "User created successfully", "UserId" => $pdo->lastInsertId()]);
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function getUser($id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM User WHERE UserId = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(["message" => "User not found"]);
    }
}

function getUsers()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM User");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
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

        $stmt = $pdo->prepare("UPDATE User SET Name = ?, Email = ?, NID = ?, Phone = ?, User_Status = ?, RoleId = ?, Last_Updated = NOW() WHERE UserId = ?");
        $stmt->execute([$name, $email, $nid, $phone, $userStatus, $roleId, $id]);

        echo json_encode(["message" => "User updated successfully"]);
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function deleteUser($id)
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM User WHERE UserId = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount()) {
        echo json_encode(["message" => "User deleted successfully"]);
    } else {
        echo json_encode(["message" => "User not found"]);
    }
}
?>
