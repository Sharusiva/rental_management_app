<?php
// This file is included by dashboard.php,
// so it already has access to $conn, $user_name, and $role.

$tenantEmail = $_SESSION['user_email'] ?? null;
$leaseInfo = null;
$requests = [];

if ($tenantEmail) {
    // --- 1. Get Tenant's Lease & Landlord Info ---
    $stmt_lease = $conn->prepare("
        SELECT 
            p.Address, 
            ls.RentPrice, 
            ls.EndDate, 
            l.Name as landlord_name, 
            l.Email as landlord_email
        FROM Lease ls
        JOIN Tenants t ON ls.TenantID = t.TenantID
        JOIN Property p ON ls.PropertyID = p.PropertyID
        JOIN Landlord l ON p.LandlordID = l.LandlordID
        WHERE t.Email = ?
    ");
    $stmt_lease->bind_param("s", $tenantEmail);
    $stmt_lease->execute();
    $result_lease = $stmt_lease->get_result();
    $leaseInfo = $result_lease->fetch_assoc(); // A tenant should only have one active lease

    // --- 2. Get Tenant's Maintenance Requests ---
    $stmt_req = $conn->prepare("
        SELECT 
            mr.Issue, 
            mr.RequestDate, 
            mr.current_status, 
            s.Name as staff_name
        FROM MaintenanceRequest mr
        JOIN Tenants t ON mr.TenantID = t.TenantID
        LEFT JOIN Staff s ON mr.StaffID = s.StaffID
        WHERE t.Email = ?
        ORDER BY mr.RequestDate DESC
    ");
    $stmt_req->bind_param("s", $tenantEmail);
    $stmt_req->execute();
    $result_req = $stmt_req->get_result();
    while ($row = $result_req->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<div class= "welcome-banner">
  <h2>Welcome, <?php echo htmlspecialchars($user_name);?>!</h2>
  <p>Here is an overview of your lease and maintenance requests.</p>
</div>

<section class="dashboard-section">
  <h2>My Lease Details</h2>
  <?php if ($leaseInfo): ?>
    <div class="lease-summary">
        <div class="lease-item">
            <h4>Property Address</h4>
            <p><?php echo htmlspecialchars($leaseInfo['Address']); ?></p>
        </div>
        <div class="lease-item">
            <h4>Monthly Rent</h4>
            <p>$<?php echo number_format($leaseInfo['RentPrice'], 2); ?></p>
        </div>
        <div class="lease-item">
            <h4>Lease End Date</h4>
            <p><?php echo htmlspecialchars($leaseInfo['EndDate']); ?></p>
        </div>
        <div class="lease-item">
            <h4>Landlord Name</h4>
            <p><?php echo htmlspecialchars($leaseInfo['landlord_name']); ?></p>
        </div>
        <div class="lease-item">
            <h4>Landlord Email</h4>
            <p><?php echo htmlspecialchars($leaseInfo['landlord_email']); ?></p>
        </div>
    </div>
  <?php else: ?>
    <p>Your lease information is not currently available.</p>
  <?php endif; ?>
</section>

<section class="dashboard-section">
  <h2>My Maintenance Requests</h2>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Date Submitted</th>
        <th>Issue</th>
        <th>Status</th>
        <th>Assigned Staff</th>
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($requests)): ?>
      <?php foreach ($requests as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['RequestDate']); ?></td>
        <td><?php echo htmlspecialchars($r['Issue']); ?></td>
        <td><?php echo htmlspecialchars($r['current_status']); ?></td>
        <td><?php echo htmlspecialchars($r['staff_name'] ?? 'Not Assigned'); ?></td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="4">You have not submitted any maintenance requests.</td></tr>
  <?php endif; ?>
    </tbody>
  </table>
</section>