<?php
session_start();
include('includes/db.php');

$error = "";   // <-- REQUIRED to avoid undefined variable warning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Lookup user
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $dbrole = $user['Role'];

        if (password_verify($password, $user['PasswordHash'])) {
            
            //Admin Override
            if ($dbrole === 'admin') {
                $_SESSION['user_email'] = $user['Email'];
                $_SESSION['role']       = 'admin';
                $_SESSION['user_id']    = $user['UserID'];
                $_SESSION['user_name']  = "Administrator";

                header("Location: dashboard.php");
                exit;
            }
            // Flow for normal users
            if ($dbrole !== $role){
                $error = "Incorrect role selected for this account";
            } else {
                    // get role-specific name
                    switch ($dbrole) {
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
                }
            if (!empty($error)) {
                // DO NOT CONTINUE LOGIN
                // Just stop processing and let HTML show the error box
            }
            else {
                // Only run DB name lookup if no error
                if (isset($q) && $q) {
                    $q->bind_param("i", $user['UserID']);
                    $q->execute(); 
                    $name = $q->get_result()->fetch_assoc()['Name'] ?? "User";
                }

            
                
                //Store session
                $_SESSION['user_email'] = $user['Email'];
                $_SESSION['role']       = $dbrole;
                $_SESSION['user_id']    = $user['UserID'];
                $_SESSION['user_name']  = $name;

                header("Location: dashboard.php");
                exit;
            }

        } else {
            $error = "Incorrect password.";
        }

    } else {
        $error = "No account found with that email";
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
