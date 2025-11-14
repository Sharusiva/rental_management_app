<?php
<<<<<<< HEAD
// This file is included by dashboard.php,
// so it already has access to $conn, $user_name, and $role.
?>
<div class= "welcome-banner">
  <h2>Welcome, <?php echo htmlspecialchars($user_name);?> ! </h2>
  <p> Here is an overview of your properties, payments and  tenant requests </p>
</div>

<section class="dashboard-section">
  <h2> Financial Overview </h2>
<?php $landlordEmail = $_SESSION['user_email'] ?? null;

if ($landlordEmail) {
      $stmt = $conn->prepare("
          SELECT
            SUM(CASE WHEN pmt.Status = 'Paid' THEN pmt.Amount ELSE 0 END) AS total_paid,
            SUM(CASE WHEN pmt.Status = 'Pending' THEN pmt.Amount ELSE 0 END) AS total_pending,
            SUM(CASE WHEN pmt.Status = 'Late' THEN pmt.Amount ELSE 0 END) AS total_late
          FROM Payments AS pmt
          JOIN Lease AS ls ON pmt.LeaseNum = ls.LeaseNum
          JOIN Property AS pr ON ls.PropertyID = pr.PropertyID
          JOIN Landlord AS l ON pr.LandlordID = l.LandlordID
          WHERE l.Email = ?
      ");
      $stmt->bind_param("s", $landlordEmail);
      $stmt->execute();
      $result = $stmt->get_result();

      $totals = $result->fetch_assoc();
      $paid = $totals['total_paid'] ?? 0;
      $pending = $totals['total_pending'] ?? 0;
      $late = $totals['total_late'] ?? 0;
    } else {
        $paid = $pending = $late = 0;
      }
?>
  <div class="finance-summary">
   <div class="finance-item">
     <h4>Paid</h4>
     <p><?php echo number_format($paid,2); ?></p>
   </div>
   <div class="finance-item">
     <h4>Pending</h4>
     <p><?php echo number_format($pending,2); ?></p>
   </div>
   <div class="finance-item">
     <h4>Overdue</h4>
     <p><?php echo number_format($late,2); ?></p>
   </div>
  </div>
</section>

<?php
$landlordEmail = $_SESSION['user_email'] ?? null;
$properties = [];

if ($landlordEmail) {
    // This query is now much cleaner
    $stmt = $conn->prepare("
        SELECT * FROM LandlordPropertyView 
        WHERE landlord_email = ?
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}
?>
<section class="dashboard-section">
  <h2>Property Overview</h2>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Property Address</th>
        <th>Tenant</th>
        <th>Lease End</th>
        <th>Rent</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
  <?php if (!empty($properties)): ?>
      <?php foreach ($properties as $prop): ?>
        <?php
            $leaseEnd = $prop['lease_end'] ?? null;
            $status = "Active";
            if ($leaseEnd) {
                $end = new DateTime($leaseEnd);
                $today = new DateTime();
                $interval = $today->diff($end)->days;
                if ($end < $today) {
                    $status = "Expired";
                } elseif ($interval <= 60) {
                    $status = "Expiring Soon";
                }
            }
        ?>
      <tr>
        <td><?php echo htmlspecialchars($prop['property_address']); ?></td>
        <td><?php echo htmlspecialchars($prop['tenant_name'] ?? 'Vacant'); ?></td>
        <td><?php echo htmlspecialchars($prop['lease_end'] ?? '-'); ?></td>
        <td>$<?php echo number_format($prop['rent'] ?? 0, 2); ?></td>
        <td>
          <?php if ($status === 'Active'): ?>
            <span class="badge active">Active</span>
          <?php elseif ($status === 'Expiring Soon'): ?>
            <span class="badge expiring">Expiring Soon</span>
          <?php else: ?>
            <span class="badge expired">Expired</span>
          <?php endif; ?>
        </td>
      </tr>
     <?php endforeach; ?>
     <?php else: ?>
      <tr><td colspan="5">No properties found.</td></tr>
     <?php endif; ?>
    </tbody>
  </table>
</section>

<?php
$landlordEmail = $_SESSION['user_email'] ?? null;
$requests = [];

if ($landlordEmail) {
    $stmt = $conn->prepare("
        SELECT * FROM LandlordMaintenanceView
        WHERE landlord_email = ?
        ORDER BY request_date DESC
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

?>
<section class="dashboard-section">
  <h2>Maintenance Requests</h2>
  <table class="dashboard-table">
    <thead>
      <tr>
        <th>Property</th>
        <th>Tenant</th>
        <th>Date Reported</th>
        <th>Cost</th>
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
        <td><?php echo htmlspecialchars($r['cost']); ?></td>
        <td><?php echo htmlspecialchars($r['status']); ?></td>
      </tr>
      <?php endforeach; ?>
  <?php else: ?>
      <tr><td colspan="5">No requests found.</td></tr>
  <?php endif; ?>
    </tbody>
  </table>
</section>
=======
$landlordEmail = $_SESSION['user_email'] ?? null;
$userName = $_SESSION['user_name'];
?>

<div class="welcome-banner">
    <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
    <p>Here is an overview of your properties, payments, and tenant requests.</p>
</div>

<!-- ==========================================================================================
    FINANCIAL OVERVIEW
=========================================================================================== -->
<section class="dashboard-section">
    <h2>Financial Overview</h2>

    <?php
    $paid = $pending = $late = 0;

    if ($landlordEmail) {
        $stmt = $conn->prepare("
            SELECT
                SUM(CASE WHEN pm.Status = 'Paid' THEN pm.Amount ELSE 0 END) AS total_paid,
                SUM(CASE WHEN pm.Status = 'Pending' THEN pm.Amount ELSE 0 END) AS total_pending,
                SUM(CASE WHEN pm.Status = 'Late' THEN pm.Amount ELSE 0 END) AS total_late
            FROM Payments pm
            JOIN Lease ls ON pm.LeaseNum = ls.LeaseNum
            JOIN Property p ON ls.PropertyID = p.PropertyID
            JOIN Landlord l ON p.LandlordID = l.LandlordID
            WHERE l.Email = ?
        ");
        $stmt->bind_param("s", $landlordEmail);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        $paid    = $data['total_paid'] ?? 0;
        $pending = $data['total_pending'] ?? 0;
        $late    = $data['total_late'] ?? 0;
    }
    ?>

    <div class="finance-summary">
        <div class="finance-item"><h4>Paid</h4><p>$<?php echo number_format($paid, 2); ?></p></div>
        <div class="finance-item"><h4>Pending</h4><p>$<?php echo number_format($pending, 2); ?></p></div>
        <div class="finance-item"><h4>Overdue</h4><p>$<?php echo number_format($late, 2); ?></p></div>
    </div>
</section>

<!-- ==========================================================================================
    PROPERTY OVERVIEW (Landlord → Property → Tenants)
=========================================================================================== -->
<section class="dashboard-section">
    <h2>Property Overview</h2>

    <?php
    $properties = [];

    if ($landlordEmail) {
        $stmt = $conn->prepare("
            SELECT 
                p.PropertyID,
                p.Address,
                t.Name AS tenant_name,
                ls.EndDate AS lease_end,
                ls.RentPrice AS rent
            FROM Property p
            LEFT JOIN Tenants t ON t.PropertyID = p.PropertyID
            LEFT JOIN Lease ls ON ls.PropertyID = p.PropertyID AND ls.TenantID = t.TenantID
            JOIN Landlord l ON p.LandlordID = l.LandlordID
            WHERE l.Email = ?
            ORDER BY p.PropertyID
        ");
        $stmt->bind_param("s", $landlordEmail);
        $stmt->execute();
        $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    ?>

    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Property</th>
                <th>Tenant</th>
                <th>Lease End</th>
                <th>Rent</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $prop): ?>
                <?php
                    $leaseEnd = $prop['lease_end'];
                    $status = "Active";

                    if (!$leaseEnd) {
                        $status = "No Lease";
                    } else {
                        $end = new DateTime($leaseEnd);
                        $now = new DateTime();
                        $days = $now->diff($end)->days;

                        if ($end < $now) $status = "Expired";
                        elseif ($days <= 60) $status = "Expiring Soon";
                    }
                ?>

                <tr>
                    <td><?php echo htmlspecialchars($prop['Address']); ?></td>
                    <td><?php echo htmlspecialchars($prop['tenant_name'] ?? 'Vacant'); ?></td>
                    <td><?php echo htmlspecialchars($prop['lease_end'] ?? '-'); ?></td>
                    <td><?php echo "$" . number_format($prop['rent'] ?? 0, 2); ?></td>

                    <td>
                        <?php if ($status === "Active"): ?>
                            <span class="badge active">Active</span>
                        <?php elseif ($status === "Expiring Soon"): ?>
                            <span class="badge expiring">Expiring Soon</span>
                        <?php elseif ($status === "Expired"): ?>
                            <span class="badge expired">Expired</span>
                        <?php else: ?>
                            <span class="badge neutral">No Lease</span>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No properties found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<!-- ==========================================================================================
    MAINTENANCE REQUESTS (clean FK-based version)
=========================================================================================== -->
<section class="dashboard-section">
    <h2>Maintenance Requests</h2>

    <?php
    $requests = [];

    if ($landlordEmail) {
        $stmt = $conn->prepare("
            SELECT
                p.Address,
                t.Name AS tenant_name,
                mr.Issue,
                mr.RequestDate,
                mr.current_status AS status,
                mr.Cost,
                s.Name AS staff_name
            FROM MaintenanceRequest mr
            JOIN Tenants t ON mr.TenantID = t.TenantID
            JOIN Property p ON t.PropertyID = p.PropertyID
            JOIN Staff s ON mr.StaffID = s.StaffID
            JOIN Landlord l ON p.LandlordID = l.LandlordID
            WHERE l.Email = ?
            ORDER BY mr.RequestDate DESC
        ");
        $stmt->bind_param("s", $landlordEmail);
        $stmt->execute();
        $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    ?>

    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Property</th>
                <th>Tenant</th>
                <th>Issue</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?php echo htmlspecialchars($req['Address']); ?></td>
                    <td><?php echo htmlspecialchars($req['tenant_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['Issue']); ?></td>
                    <td><?php echo htmlspecialchars($req['staff_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['RequestDate']); ?></td>
                    <td><?php echo "$" . number_format($req['Cost'], 2); ?></td>
                    <td><?php echo htmlspecialchars($req['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No requests found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
>>>>>>> c244653f3e011942432fbc8decc17475e635e311
