<?php
include('includes/auth.php');
include('includes/db.php');
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
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

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
  <div class="sidebar-header">
    <h3><?php echo htmlspecialchars($user_name); ?></h3>
    <p><?php echo ucfirst($role); ?></p>
    <span class="close-btn" onclick="toggleSidebar()">✖</span>
  </div>
  <h2>Rental System</h2>
  <ul class="sidebar-menu">
    <?php if ($role == 'admin'): ?>
      <li><a href="roles/admin.php">Admin Panel</a></li>
    <?php elseif ($role == 'landlord'): ?>
      <li><a href="roles/landlord/calender.php">My Calender</a></li>
      <li><a href="roles/landlord/payments.php">Payments</a></li>
      <li><a href="roles/landlord/register_property.php">Register a Property<a/</li>
    <?php elseif ($role == 'tenant'): ?>
      <li><a href="roles/tenant/my_lease.php">My Lease</a></li>
      <li><a href="roles/tenant/requests.php"> Make a request</a></li>
      <li><a href="roles/tenant/payments.php"> Payments</a></li>
    <?php elseif ($role == 'staff'): ?>
      <li><a href="roles/staff.php">My Assigned Tasks</a></li>
    <?php endif; ?>
    <li><a href="index.php">Logout</a></li>
  </ul>
</div>

<div class="content">
   <?php if ($role === 'admin'): ?> 
      <h2>🧑‍💼 Admin Dashboard</h2>
      <p>Welcome, Administrator. This section will show system stats, user management tools, and reports.</p>

   <?php  elseif ($role === 'landlord'): ?> 
      <div class= "welcome-banner">
      <h2>Welcome, <?php echo htmlspecialchars($user_name);?> ! </h2>
      <p> Here is an overview of your properties, payments and  tenant requests </p>
      </div>
      <!-- Financial Overview -->
      <section class="dashboard-section">
      <h2> Financial Overview </h2>
	<?php $landlordEmail = $_SESSION['user_email'] ?? null;

	  if ($landlordEmail) {
    	    // Prepare and execute query securely
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
     <!-- Property and Tenant Overview -->
     <?php
	$landlordEmail = $_SESSION['user_email'] ?? null;
	$properties = [];

	if ($landlordEmail) {
    	$stmt = $conn->prepare("
             SELECT 
            	p.PropertyID,
           	p.Address AS property_address,
           	t.Name AS tenant_name,
            	ls.RentPrice AS rent,
            	ls.EndDate AS lease_end
             FROM Property p
             LEFT JOIN Lease ls ON p.PropertyID = ls.PropertyID
             LEFT JOIN Tenants t ON ls.TenantID = t.TenantID
             JOIN Landlord l ON p.LandlordID = l.LandlordID
             WHERE l.Email = ?
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
     <!-- Maintenance Requests -->
	<?php
		$landlordEmail = $_SESSION['user_email'] ?? null;
		$requests = [];

		if ($landlordEmail) {
    		$stmt = $conn->prepare("
        		SELECT 
          		p.Address AS property_address,
          		t.Name AS tenant_name,
          		mr.Issue AS issue,
          		s.Name AS staff_name,
         		mr.RequestDate AS request_date,
          		mr.Cost AS cost,
			mr.current_status AS status
       		 FROM MaintenanceRequest mr
                 JOIN Tenants t ON mr.TenantID = t.TenantID
       	  	 JOIN Staff s ON mr.StaffID = s.StaffID
       	  	 JOIN Property p ON t.Address = p.Address
       	 	 JOIN Landlord l ON p.LandlordID = l.LandlordID
       		 WHERE l.Email = ?
       		 ORDER BY mr.RequestDate DESC
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
	   <?php endforeach; ?>
	   <?php endif; ?>
        </tbody>
      </table>
     </section>

   <?php elseif ($role === 'tenant'): ?>
      <h2>👤 Tenant Dashboard</h2>

   <?php else: ?>
      <h2> Maintenance Staff Dashboard </h2>
   <?php endif; ?>
</div>
</body>
<script>
 function toggleSidebar() {
   document.getElementById("sidebar").classList.toggle("active");
 }
</script>

</html>

