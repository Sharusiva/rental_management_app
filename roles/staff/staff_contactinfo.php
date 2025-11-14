<?php

include('../../includes/auth.php');
include('../../includes/db.php');

$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

$contacts = [];
$stmt_contacts = $conn->prepare("
    SELECT entity_name, contact_email, entity_type
    FROM MixedContactInfo
    ORDER BY entity_type, entity_name
");
$stmt_contacts->execute();
$result_contacts = $stmt_contacts->get_result();

while ($row = $result_contacts->fetch_assoc()) {
    $contacts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact List</title>
  
  <link rel="stylesheet" href="../../assets/style.css">

</head>
<body>
<div class="topbar">
   <div class="profile-icon" onclick="toggleSidebar()">
    <svg xmlns="http://www.w3.org/2000/svg" height="28" width="28" viewBox="0 0 24 24" fill="white">
      <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
    </svg>
   </div>
   <h2 class="app-title">Rental System Dashboard</h2>
</div>



<div class="content">
    <div class="welcome-banner">
      <h2>Contact List</h2>
      <p>A full directory of all tenants and landlords.</p>
    </div>

    <a href="../../dashboard.php" class="btn-return"> Return to Dashboard</a>

    <section class="dashboard-section">
      <h2>Contact List (Tenants & Landlords)</h2>
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
      <?php if (!empty($contacts)): ?>
          <?php foreach ($contacts as $contact): ?>
          <tr>
            <td><?php echo htmlspecialchars($contact['entity_name']); ?></td>
            <td><?php echo htmlspecialchars($contact['contact_email']); ?></td>
            <td><?php echo htmlspecialchars($contact['entity_type']); ?></td>
          </tr>
          <?php endforeach; ?>
      <?php else: ?>
          <tr><td colspan="3">No contacts found in the view.</td></tr>
      <?php endif; ?>
        </tbody>
      </table>
    </section>
</div>

</body>

</html>