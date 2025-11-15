<?php
session_start();
include('includes/db.php');

$error = "";   // <-- REQUIRED to avoid undefined variable warning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Lookup user
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = ? AND Role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if (password_verify($password, $user['PasswordHash'])) {

            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['role']       = $user['Role'];
            $_SESSION['user_id']    = $user['UserID'];

            // get role-specific name
            switch ($role) {
                case 'tenant':
                    $q = $conn->prepare("SELECT Name FROM Tenants WHERE UserID = ?");
                    break;
                case 'landlord':
                    $q = $conn->prepare("SELECT Name FROM Landlord WHERE UserID = ?");
                    break;
                case 'staff':
                    $q = $conn->prepare("SELECT Name FROM Staff WHERE UserID = ?");
                    break;
            }

            $q->bind_param("i", $user['UserID']);
            $q->execute();

            $name = $q->get_result()->fetch_assoc()['Name'] ?? "User";
            $_SESSION['user_name'] = $name;

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Incorrect password.";
        }

    } else {
        $error = "No account found with that email + role.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/login.css">
</head>

<body>

<div class="login-box">

    <h2>Rental Management Portal</h2>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)): ?>
    <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="" disabled selected>Select Role…</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="staff">Staff</option>
        </select>

        <input type="submit" value="Login">

    </form>

    <div class="register-link">
        Don’t have an account?
        <a href="register.php">Sign up here</a>
    </div>

</div>

</body>
</html>
