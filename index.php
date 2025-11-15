<?php
session_start();
session_unset();           // remove all previous session variables
session_regenerate_id(true); // generate a new session ID to avoid collisions

include('includes/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Fetch from Users
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = ? AND Role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['PasswordHash'])) {
            // Valid login — fetch the user's name based on role
            switch ($role) {
                case 'tenant':
                    $q = $conn->prepare("SELECT Name FROM Tenants WHERE email = ?");
                    break;
                case 'landlord':
                    $q = $conn->prepare("SELECT Name FROM Landlord WHERE Email = ?");
                    break;
                case 'staff':
                    $q = $conn->prepare("SELECT Name FROM Staff WHERE ContactInfo = ?");
                    break;
                case 'admin':
                    $_SESSION['user_name'] = 'Admin';
                    break;
            }

            if ($role !== 'admin') {
                $q->bind_param("s", $email);
                $q->execute();
                $res = $q->get_result();
                $row = $res->fetch_assoc();
                $_SESSION['user_name'] = $row['Name'];
            }

            // Store session info
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user['UserID'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error_message =  " Invalid password";
        }
    } else {
        $error_message = " No user found with that email/role combination";
    }
}
?>

<!DOCTYPE html>
<link rel="stylesheet" href="assets/login.css">
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
</head>
<body>
  <div class="login-box">
    <h2>Rental Management Portal </h2>
    <form method="POST">
      <?php if  (!empty($error_message)): ?>
	<p style="color:red; font-weight:bold; text-align:center; margin-bottom:10px;">
        <?= htmlspecialchars($error_message) ?>
        </p>
      <?php endif; ?>
      <label>Email:</label>
      <input type="email" name="email" required>
      <label>Password:</label>
      <input type="password" name="password" required>
      <label>Role:</label>
      <select name="role" required>
        <option value="admin">Admin</option>
        <option value="landlord">Landlord</option>
        <option value="tenant">Tenant</option>
        <option value="staff">Staff</option>
      </select>
      <input type="submit" value="Login">
      <p class="register-text">
        Don't have an account?
        <a href="register.php">Sign up here</a>
    </p>
    </form>
  </div>
</body>
</html>
