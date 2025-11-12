<?php
session_start();
include('../../includes/db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../../index.php");
    exit();
}

$message = "";

// Get landlord ID
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT LandlordID FROM Landlord WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($landlordID);
$stmt->fetch();
$stmt->close();

// Handle property registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address = trim($_POST['address']);

    if (!empty($address)) {
        $stmt = $conn->prepare("INSERT INTO Property (Address, LandlordID) VALUES (?, ?)");
        $stmt->bind_param("si", $address, $landlordID);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Property successfully registered!</p>";
        } else {
            $message = "<p class='error'>❌ Error: " . htmlspecialchars($stmt->error) . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p class='error'>⚠️ Please enter a property address.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Property</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .content {
        margin: 60px auto;
        max-width: 600px;
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
        margin-top: 0;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input[type="text"] {
        width: 100%;
        padding: 10px;
        margin-top: 6px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    input[type="submit"] {
        background: #0077cc;
        color: #fff;
        border: none;
        padding: 10px 16px;
        margin-top: 15px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s;
    }

    input[type="submit"]:hover {
        background: #005fa3;
    }

    .back-btn {
        background-color: #6c757d;
        color: white;
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        margin-left: 10px;
    }

    .success { color: green; }
    .error { color: red; }
</style>
</head>
<body>

<div class="content">
    <h2>🏠 Register a New Property</h2>
    <?php echo $message; ?>

    <form method="POST">
        <label for="address">Property Address:</label>
        <input type="text" id="address" name="address" placeholder="Enter property address" required>

        <input type="submit" value="Add Property">
        <a href="../../../dashboard.php" class="back-btn">⬅ Back</a>
    </form>
</div>

</body>
</html>
