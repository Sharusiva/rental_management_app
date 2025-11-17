<?php
$staffEmail = $_SESSION['user_email'] ?? null;
$requests = [];
$unpaid_landlords = [];

if ($staffEmail) {
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
    $stmt->close(); 

    $stmt_landlords = $conn->prepare("
        SELECT L.Name, L.Email 
        FROM Landlord L
        JOIN LandlordsWithUnpaidRent V ON L.Name = V.Name
        GROUP BY L.Name, L.Email
        ORDER BY L.Name
    ");
    $stmt_landlords->execute();
    $result_landlords = $stmt_landlords->get_result();

    while ($row_landlord = $result_landlords->fetch_assoc()) {
        $unpaid_landlords[] = $row_landlord;
    }
    $stmt_landlords->close();
}
?>

<div class= "welcome-banner">
  <h2>Welcome, <?php echo htmlspecialchars($user_name);?>!</h2>
  <p>Here are your assigned maintenance tasks and system reports.</p>
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
        <th>Action</th> 
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($requests)): ?>
      <?php foreach ($requests as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['property_address']); ?></td>
        <td><?php echo htmlspecialchars($r['tenant_name']); ?></td> 
        <td><?php echo htmlspecialchars($r['request_date']); ?></td>
        <td><?php echo htmlspecialchars($r['Issue']); ?></td>
        <td><?php echo htmlspecialchars($r['current_status']); ?></td>
        <td>
          <a href="roles/staff/updateTask.php?request_num=<?php echo $r['RequestNUM']; ?>" class="action-link">
            Manage
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="6">You have no assigned tasks.</td></tr>
  <?php endif; ?>
    </tbody>
  </table>
</section>

<section class="dashboard-section">
  <h2>Landlords with Unpaid Rent</h2>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Landlord Name</th>
        <th>Contact Email</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($unpaid_landlords)): ?>
      <?php foreach ($unpaid_landlords as $landlord): ?>
      <tr>
        <td><?php echo htmlspecialchars($landlord['Name']); ?></td>
        <td><?php echo htmlspecialchars($landlord['Email']); ?></td>
        <td>
          <a href="mailto:<?php echo htmlspecialchars($landlord['Email']); ?>?subject=Unpaid%20Rent%20Notice" class="action-link">
            Email
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="3" style="text-align: center; padding: 15px;">All landlords have received their rent.</td></tr>
  <?php endif; ?>
    </tbody>
  </table>
</section>