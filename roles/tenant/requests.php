<?php
include('../../includes/auth.php');
include('../../includes/db.php');
$email = $_SESSION['user_email'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $issue = $_POST['issue'];
  $tenant = $conn->query("SELECT TenantID FROM Tenants WHERE email='$email'")->fetch_assoc()['TenantID'];
  $conn->query("INSERT INTO MaintenanceRequest (Issue, TenantID, RequestDate) VALUES ('$issue', $tenant, CURDATE())");
  echo "<p>✅ Request submitted!</p>";
}
?>
<h2>Submit Maintenance Request</h2>
<form method="POST">
  <textarea name="issue" rows="4" cols="50" placeholder="Describe the issue" required></textarea><br>
  <input type="submit" value="Submit">
</form>
