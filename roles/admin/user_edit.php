<?php
session_start();
include('../../includes/db.php');

// Manual admin protection
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized.");
}

$user_id  = $_POST['user_id'];
$email    = $_POST['email'];
$role     = $_POST['role'];
$password = $_POST['password'] ?? null;

$conn->begin_transaction();

// ---------- 1. UPDATE USERS TABLE ----------
$updateUser = $conn->prepare("
    UPDATE Users SET Email = ?, Role = ? WHERE UserID = ?
");
$updateUser->bind_param("ssi", $email, $role, $user_id);
$updateUser->execute();

// ---------- 2. UPDATE PASSWORD ----------
if (!empty($password)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("
        UPDATE Users SET PasswordHash = ? WHERE UserID = ?
    ");
    $stmt->bind_param("si", $hash, $user_id);
    $stmt->execute();
}

// ---------- 3. UPDATE ROLE-SPECIFIC TABLES ----------
switch ($role) {

    case 'tenant':
        $name = $_POST['name'] ?? null;
        $phone = $_POST['phone'] ?? null;
        $property_id = $_POST['property_id'] ?? null;

        $stmt = $conn->prepare("
            UPDATE Tenants
            SET Name = COALESCE(NULLIF(?, ''), Name),
                PhoneNum = COALESCE(NULLIF(?, ''), PhoneNum),
                PropertyID = COALESCE(NULLIF(?, ''), PropertyID)
            WHERE UserID = ?
        ");
        $stmt->bind_param("ssii", $name, $phone, $property_id, $user_id);
        $stmt->execute();
        break;

    case 'landlord':
        $name = $_POST['name'] ?? null;
        $stmt = $conn->prepare("
            UPDATE Landlord 
            SET Name = COALESCE(NULLIF(?, ''), Name)
            WHERE UserID = ?
        ");
        $stmt->bind_param("si", $name, $user_id);
        $stmt->execute();
        break;

    case 'staff':
        $name = $_POST['name'] ?? null;
        $contact = $_POST['contact'] ?? null;

        $stmt = $conn->prepare("
            UPDATE Staff
            SET Name = COALESCE(NULLIF(?, ''), Name),
                ContactInfo = COALESCE(NULLIF(?, ''), ContactInfo)
            WHERE UserID = ?
        ");
        $stmt->bind_param("ssi", $name, $contact, $user_id);
        $stmt->execute();
        break;
}

$conn->commit();

// Return to main dashboard
header("Location: ../../dashboard.php?success=1");
exit;
?>
