<?php
include('../../includes/db.php');
include('../../includes/auth.php');

// Security check
if ($_SESSION['role'] !== 'tenant') {
    header('Location: ../../dashboard.php');
    exit;
}

$tenantEmail = $_SESSION['user_email'];

// Get the tenant's ID
$stmt = $conn->prepare("
    SELECT t.TenantID 
    FROM Tenants t
    JOIN Users u ON t.UserID = u.UserID
    WHERE u.Email = ?
");
$stmt->bind_param("s", $tenantEmail);
$stmt->execute();
$stmt->bind_result($tenantID);
$stmt->fetch();
$stmt->close();

// Fetch lease info AND DayOfMonthDue
$query = "
    SELECT 
        p.Address,
        l.StartDate,
        l.EndDate,
        l.DayOfMonthDue
    FROM Lease l
    JOIN Property p ON l.PropertyID = p.PropertyID
    WHERE l.TenantID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tenantID);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($lease = $result->fetch_assoc()) {
    // 1. The Lease Bar (Blue) - Matches Landlord Style
    $events[] = [
        'title' => 'Lease: ' . $lease['Address'],
        'start' => $lease['StartDate'],
        'end'   => $lease['EndDate'],
        'color' => '#0077cc' // Blue
    ];

    // 2. Rent Due Indicator (Red) - Recurring Monthly
    // We use the RRule plugin to make this repeat on the specific day
    $day = (int)$lease['DayOfMonthDue'];
    $events[] = [
        'title' => 'RENT DUE',
        'rrule' => [
            'freq' => 'monthly',
            'bymonthday' => $day,
            'dtstart' => $lease['StartDate'],
            'until' => $lease['EndDate']
        ],
        'color' => '#dc3545', // Red
        'textColor' => 'white'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Calendar</title>
  
  <!-- FullCalendar CSS -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  
  <!-- FullCalendar JS -->
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
  
  <!-- RRule Plugin (REQUIRED for the monthly red button to work) -->
  <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.8/index.global.min.js'></script>

  <style>
    /* Matches the simple Landlord Calendar style exactly */
    body { font-family: Arial, sans-serif; background:#f9f9f9; padding:20px; }
    #calendar { max-width: 1000px; margin: auto; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; }
    
    .back-btn {
        text-decoration:none; 
        background:#0077cc; 
        color:white; 
        padding:8px 15px; 
        border-radius:6px; 
        font-weight:600;
    }
    .back-btn:hover { background:#005fa3; }
  </style>
</head>
<body>
 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <h2>📅 My Calendar</h2>
  <a href="../../dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
 </div>

 <div id='calendar'></div>

 <script>
   document.addEventListener('DOMContentLoaded', function() {
     var calendarEl = document.getElementById('calendar');
     var events = <?php echo json_encode($events); ?>; 

     var calendar = new FullCalendar.Calendar(calendarEl, {
       initialView: 'dayGridMonth',
       height: 700,
       events: events,
       // Ensure the red event stacks nicely
       eventDisplay: 'block' 
     });
     calendar.render();
   });
 </script>

</body>
</html>