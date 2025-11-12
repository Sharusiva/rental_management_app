<?php
$servername = "99.231.230.55";  // your host
$username = "dev1";             // your username
$password = "Password123!";      // your password
$database = "Final_Project";    // your database name (update this!)

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<h3>✅ Connected successfully to MySQL!</h3>";

// Optional: Run a query to test
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h4>Tables in the database:</h4><ul>";
    while($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "No tables found.";
}

$conn->close();
?>

