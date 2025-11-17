<?php
// roles/staff/staff_dashboard.php

$staffEmail = $_SESSION['user_email'] ?? null;

$requests = [];
$unpaid_landlords = [];
$top_requesters = []; // New array for the analytics view

if ($staffEmail) {
    
    // 1. Get Assigned Tasks (Existing Logic)
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

    // 2. Get Unpaid Landlords (Existing Logic)
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

    // 3. NEW: Get Top 5 Frequent Requesters (Using View: tenant_request_counts)
    $stmt_top = $conn->prepare("
        SELECT * FROM tenant_request_counts 
        ORDER BY totalrequestsfiled DESC 
        LIMIT 5
    ");
    $stmt_top->execute();
    $result_top = $stmt_top->get_result();
    while ($row_top = $result_top->fetch_assoc()) {
        $top_requesters[] = $row_top;
    }
    $stmt_top->close();
}
?>

<div class="welcome-banner">
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
          <a href="roles/staff/updateTask.php?request_num=<?php echo $r['RequestNUM']; ?>" class="action-link" style="color: #0077cc; font-weight: bold;">
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
  <h2>Frequent Requesters (Analytics)</h2>
  <p style="margin-bottom: 10px; color: #666; font-size: 0.9rem;">Tenants with the highest volume of maintenance tickets.</p>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Tenant Name</th>
        <th>Total Requests Filed</th>
        <th>Insight</th>
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($top_requesters)): ?>
      <?php foreach ($top_requesters as $t): ?>
      <?php 
          // Simple logic to flag high volume users
          $count = $t['totalrequestsfiled'];
          $isHigh = ($count >= 5);
      ?>
      <tr>
        <td><?php echo htmlspecialchars($t['tenantname']); ?></td>
        <td><strong><?php echo $count; ?></strong></td>
        <td>
            <?php if ($isHigh): ?>
                <span class="badge expired">High Activity</span>
            <?php else: ?>
                <span class="badge active">Normal</span>
            <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="3">No request data available yet.</td></tr>
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
          <a href="mailto:<?php echo htmlspecialchars($landlord['Email']); ?>?subject=Unpaid%20Rent%20Notice" class="action-link" style="color: #dc3545; font-weight: bold;">
            Send Email
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