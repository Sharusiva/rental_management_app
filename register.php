<?php
include('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $role = $_POST['role'];

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert into Users
    $stmt = $conn->prepare("INSERT INTO Users (Email, PasswordHash, Role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $hashed, $role);
    $stmt->execute();
    $userId = $stmt->insert_id;

    // Insert into role-specific table
    switch ($role) {
        case 'tenant':
            $stmt2 = $conn->prepare("INSERT INTO Tenants (Name, email, UserID) VALUES (?, ?, ?)");
            break;
        case 'landlord':
            $stmt2 = $conn->prepare("INSERT INTO Landlord (Name, Email, UserID) VALUES (?, ?, ?)");
            break;
        case 'staff':
            $stmt2 = $conn->prepare("INSERT INTO Staff (Name, ContactInfo, UserID) VALUES (?, ?, ?)");
            break;
    }

    $stmt2->bind_param("ssi", $name, $email, $userId);
    $stmt2->execute();

    echo "✅ Registration successful!";
}
?>
