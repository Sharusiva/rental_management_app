<?php
// 1. Authenticate and connect to DB

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('includes/auth.php');
include('includes/db.php');

// 2. Get user data from session
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

<div id="sidebar" class="sidebar">
  <div class="sidebar-header">
    <h3><?php echo htmlspecialchars($user_name); ?></h3>
    <p><?php echo ucfirst($role); ?></p>
    <span class="close-btn" onclick="toggleSidebar()">✖</span>
  </div>
  <h2>Rental System</h2>
  <ul class="sidebar-menu">
    <?php // Links are all correct from the root folder ?>
    <?php if ($role == 'admin'): ?>
      <li><a href="roles/admin.php">Admin Panel</a></li>
    <?php elseif ($role == 'landlord'): ?>
      <li><a href="roles/landlord/calender.php">My Calender</a></li>
      <li><a href="roles/landlord/payments.php">Payments</a></li>
      <li><a href="roles/landlord/register_property.php">Register a Property</a></li>

    <?php elseif ($role == 'tenant'): ?>
      <li><a href="roles/tenant/my_lease.php">My Lease</a></li>
      <li><a href="roles/tenant/requests.php">Make a request</a></li>
      <li><a href="roles/tenant/payments.php">Payments</a></li>
      <li><a href="roles/tenant/t_calendar.php">My Calendar</a></li>

    <?php elseif ($role == 'staff'): ?>
      <li><a href="roles/staff/staff_contactinfo.php">Contact Info</a></li>
      <li><a href="roles/staff/selectTask.php">Select a Unassigned Task</a></li>
    <?php endif; ?>
    <li><a href="index.php">Logout</a></li>
  </ul>
</div>

<div class="content">
    <?php
    // 6. Use a switch to include the correct content file
    switch ($role) {
        case 'landlord':
            include('roles/landlord/landlord_dashboard.php');
            break;

        case 'admin':
            // include('roles/admin/admin_content.php'); 
            echo '<h2> Admin Dashboard</h2><p>Welcome, Administrator.</p>'; 
            break;

        case 'tenant':

            include('roles/tenant/tenant_dashboard.php');
            break;

        case 'staff':
            // You would create 'roles/staff/staff_content.php'
            // include('roles/staff/staff_content.php');
            include('roles/staff/staff_dashboard.php');
            break;

        default:
            echo '<p>Error: No valid role found.</p>';
            break;
    }
    ?>
</div>

</body>
<script>
function toggleSidebar() {
  document.body.classList.toggle("sidebar-active");
}
</script>
</html>