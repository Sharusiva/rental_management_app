<?php
// This file is included by dashboard.php,
// so it already has access to $conn, $user_name, and $role.

$staffEmail = $_SESSION['user_email'] ?? null;
$requests = [];

if ($staffEmail) {
    // We can use the SAME view, just filter by the staff_email!
    $stmt = $conn->prepare("
        SELECT * FROM LandlordMaintenanceView
        WHERE staff_email = ?
        ORDER BY request_date DESC
    ");
    $stmt->bind_param("s", $staffEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<div class= "welcome-banner">
  <h2>Welcome, <?php echo htmlspecialchars($user_name);?>!</h2>
  <p>Here are your assigned maintenance tasks.</p>
</div>

<section class="dashboard-section">
  <h2>My Assigned Tasks</h2>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Property</th>
        <th>Tenant</th>
        <th>Date Reported</th>
        <th>Issue</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($requests)): ?>
      <?php foreach ($requests as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['property_address']); ?></td>
        <td><?php echo htmlspecialchars($r['tenant_name']); ?></td>
        <td><?php echo htmlspecialchars($r['request_date']); ?></td>
        <td><?php echo htmlspecialchars($r['issue']); ?></td>
        <td><?php echo htmlspecialchars($r['status']); ?></td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="5">You have no assigned tasks.</td></tr>
  <?php endif; ?>
    </tbody>
  </table>
</section>