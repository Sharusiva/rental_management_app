<?php
session_start();
if ($_SESSION['role'] !== 'admin') { exit; }

include('../../includes/db.php');

$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];

$stmt = $conn->prepare("INSERT INTO Users (Email, PasswordHash, Role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $password, $role);
$stmt->execute();

$userId = $stmt->insert_id;

// Role-specific inserts
switch ($role) {
    case 'tenant':
        $stmt2 = $conn->prepare("INSERT INTO Tenants (Name, PhoneNum, Email, UserID, PropertyID) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssii", $_POST['name'], $_POST['phone'], $email, $userId, $_POST['property_id']);
        break;

    case 'landlord':
        $stmt2 = $conn->prepare("INSERT INTO Landlord (Name, Email, UserID) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssi", $_POST['name'], $email, $userId);
        break;

    case 'staff':
        $stmt2 = $conn->prepare("INSERT INTO Staff (Name, ContactInfo, UserID) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssi", $_POST['name'], $_POST['contact'], $userId);
        break;
}

if (isset($stmt2)) $stmt2->execute();

header("Location: admin_dashboard.php");
exit;
