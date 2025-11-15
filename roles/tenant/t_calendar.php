<?php
// 1. Authenticate and connect to DB
include('../../includes/auth.php');
include('../../includes/db.php');

// 2. Add a security check for tenants
if ($_SESSION['role'] !== 'tenant') {
    header('Location: ../../dashboard.php');
    exit;
}

/**
 * Formats a number as an ordinal string (e.g., 1 -> 1st, 2 -> 2nd).
 */
function getOrdinalSuffix($day) {
    if (in_array(($day % 100), [11, 12, 13])) {
        return 'th';
    }
    switch ($day % 10) {
        case 1:  return 'st';
        case 2:  return 'nd';
        case 3:  return 'rd';
        default: return 'th';
    }
}

$tenantEmail = $_SESSION['user_email']; // This is the User's login email
$tenantID = null;
$events = []; // Initialize the events array
$leaseInfo = []; // Store lease info

// 3. Get the TenantID by joining Users and Tenants
$stmt_tenant = $conn->prepare("
    SELECT t.TenantID 
    FROM Tenants t
    JOIN Users u ON t.UserID = u.UserID
    WHERE u.Email = ?
");
$stmt_tenant->bind_param("s", $tenantEmail);
$stmt_tenant->execute();
$stmt_tenant->bind_result($tenantID);
$stmt_tenant->fetch();
$stmt_tenant->close();


// 4. Only run if we found a valid TenantID
if ($tenantID) {
    
    // --- First, get the main lease event (as a background) ---
    $query_lease = "
        SELECT 
            p.Address AS Address, 
            l.StartDate, 
            l.EndDate,
            l.DayOfMonthDue
        FROM Lease l
        JOIN Property p ON l.PropertyID = p.PropertyID
        WHERE l.TenantID = ?
        LIMIT 1
    ";
    $stmt_lease = $conn->prepare($query_lease);
    $stmt_lease->bind_param("i", $tenantID);
    $stmt_lease->execute();
    $result_lease = $stmt_lease->get_result();
    
    if ($lease = $result_lease->fetch_assoc()) {
        $leaseInfo = $lease; // Save for later
        $day = $lease['DayOfMonthDue'];
        $ordinalDay = $day . getOrdinalSuffix($day); // e.g., "1st" or "15th"

        $events[] = [
            'title' => 'My Lease (Rent Due on the ' . $ordinalDay . ')',
            'start' => $lease['StartDate'],
            'end' => $lease['EndDate'],
            'color' => '#0077cc', // Blue for the lease
            'display' => 'background' 
        ];
    }
    $stmt_lease->close();

    // --- (NEW) Second, get ALL "Late" payments ---
    // These are RED
    $query_late = "
        SELECT p.DueDate, p.Amount
        FROM Payments p
        JOIN Lease l ON p.LeaseNum = l.LeaseNum
        WHERE l.TenantID = ? AND p.Status = 'Late'
        ORDER BY p.DueDate ASC
    ";
    $stmt_late = $conn->prepare($query_late);
    $stmt_late->bind_param("i", $tenantID);
    $stmt_late->execute();
    $result_late = $stmt_late->get_result();
    
    $late_dates = []; // Keep track of late dates
    while ($payment = $result_late->fetch_assoc()) {
        $events[] = [
            'title' => 'RENT LATE: $' . number_format($payment['Amount'], 2),
            'start' => $payment['DueDate'],
            'color' => '#dc3545' // Red
        ];
        $late_dates[] = $payment['DueDate']; // Store this date
    }
    $stmt_late->close();
    
    // --- (NEW) Third, get the SINGLE NEXT "Pending" or "Future" payment ---
    // This is the YELLOW box
    $query_next = "
        SELECT p.DueDate, p.Amount, p.Status
        FROM Payments p
        JOIN Lease l ON p.LeaseNum = l.LeaseNum
        WHERE l.TenantID = ? 
          AND p.Status IN ('Pending', 'Future')
          AND p.DueDate >= CURDATE()
        ORDER BY p.DueDate ASC
        LIMIT 1
    ";
    $stmt_next = $conn->prepare($query_next);
    $stmt_next->bind_param("i", $tenantID);
    $stmt_next->execute();
    $result_next = $stmt_next->get_result();

    if ($next_payment = $result_next->fetch_assoc()) {
        // Only add this yellow box if it's NOT on a date that is already marked as LATE
        // (This check is just in case of weird data)
        if (!in_array($next_payment['DueDate'], $late_dates)) {
            $events[] = [
                'title' => 'Next Rent Payment: $' . number_format($next_payment['Amount'], 2),
                'start' => $next_payment['DueDate'],
                'color' => '#ffc107', // Yellow
                'textColor' => '#000'
            ];
        }
    }
    $stmt_next->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Calendar</title>
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f9f9f9; padding:20px; }
    #calendar { max-width: 1000px; margin: auto; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; }
    .header-bar { max-width: 1000px; margin: auto; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    
    .back-btn {
      display: inline-block;
      padding: 8px 15px;
      background-color: #0077cc;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background-color 0.2s;
    }
    .back-btn:hover {
      background-color: #005fa3;
    }
  </style>
</head>
<body>
 <div class="header-bar">
  <h2>📅 My Calendar</h2>
  <a href="../../dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
 </div>

 <div id='calendar'>
   <script>
   document.addEventListener('DOMContentLoaded', function() {
     var calendarEl = document.getElementById('calendar');
     var events = <?php echo json_encode($events); ?>; 

     var calendar = new FullCalendar.Calendar(calendarEl, {
       initialView: 'dayGridMonth',
       height: 700,
       events: events,
       headerToolbar: {
         left: 'prev,next today',
         center: 'title',
         right: 'dayGridMonth,timeGridWeek,listWeek'
       }
     });
     calendar.render();
   });
   </script>

 </div>
</body>
</html>