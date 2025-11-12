<?php
// --- Database connection setup ---
$servername = "99.231.230.55";   // your host
$username   = "dev1";            // your username
$password   = "Password123!";     // your password
$database   = "Final_Project";   // your database name

// --- Create connection ---
$conn = new mysqli($servername, $username, $password, $database);

// --- Check connection ---
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

// ✅ Connection successful, ready for queries
?>

