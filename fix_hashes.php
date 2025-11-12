<?php
include('includes/db.php');

$result = $conn->query("SELECT UserID, Email, PasswordHash FROM Users");
while ($row = $result->fetch_assoc()) {
    // Skip if it’s already a bcrypt hash (starts with $2y$)
    if (strpos($row['PasswordHash'], '$2y$') !== 0) {
        $newHash = password_hash('Password123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
        $stmt->bind_param("si", $newHash, $row['UserID']);
        $stmt->execute();
        echo "✅ Updated hash for {$row['Email']}\n";
    }
}
echo "All hashes updated successfully.";
?>
