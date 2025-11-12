<?php
include('../../includes/db.php');

session_start();

$landlordEmail = $_SESSION['user_email']; // e.g. sarahc@mail.com

// Get the landlord's ID
$stmt = $conn->prepare("SELECT LandlordID FROM Landlord WHERE Email = ?");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$stmt->bind_result($landlordID);
$stmt->fetch();
$stmt->close();

// Fetch lease info for this landlord
$query = "
    SELECT 
        t.Name AS tenant,
        p.Address AS Address,
        l.StartDate,
        l.EndDate
    FROM Lease l
    JOIN Tenants t ON l.TenantID = t.TenantID
    JOIN Property p ON l.PropertyID = p.PropertyID
    WHERE p.LandlordID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $landlordID);
$stmt->execute();
$result = $stmt->get_result();

$leases = [];
while ($row = $result->fetch_assoc()) {
    $leases[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lease Calendar</title>
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
  <style>
    body { font-family: Arial; background:#f9f9f9; padding:20px; }
    #calendar { max-width: 1000px; margin: auto; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; }
  </style>
</head>
<body>
 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <h2>📅 Lease Calendar</h2>
  <a href="../../dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
 </div>

 <div id='calendar'>
 	 <?php
		$events = [];
		foreach ($leases as $lease) {
		  $events[] = [
		    'title' => $lease['tenant'] . ' – ' . $lease['Address'],
		    'start' => $lease['StartDate'],
		    'end' => $lease['EndDate'],
		    'color' => '#0077cc'
		  ];
		}
	?>
        <script>
	document.addEventListener('DOMContentLoaded', function() {
	  var calendarEl = document.getElementById('calendar');
	  var events = <?php echo json_encode($events); ?>; // outputs clean JSON

	  var calendar = new FullCalendar.Calendar(calendarEl, {
	    initialView: 'dayGridMonth',
	    height: 700,
	    events: events
	  });
	  calendar.render();
	});
	</script>

  </div>
</body>
</html>

