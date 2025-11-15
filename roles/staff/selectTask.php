<?php
// 1. Authenticate and connect to DB
include('../../includes/auth.php');
include('../../includes/db.php');

// 2. Get user data from session
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];
$user_email = $_SESSION['user_email'];

// 3. --- STAFF ONLY ---
if ($role !== 'staff') {
    header('Location: ../../dashboard.php'); 
    exit;
}

// 4. Get the logged-in staff member's StaffID
$loggedInStaffID = null;
$stmt_staff = $conn->prepare("
    SELECT s.StaffID 
    FROM Staff s
    JOIN Users u ON s.UserID = u.UserID
    WHERE u.Email = ?
");
$stmt_staff->bind_param("s", $user_email);
$stmt_staff->execute();
$stmt_staff->bind_result($loggedInStaffID);
$stmt_staff->fetch();
$stmt_staff->close();

if (!$loggedInStaffID) {
    die("Could not find a valid staff profile for your user account.");
}

$message = ""; // For success/error messages
$message_type = "success"; // For styling

// 5. --- HANDLE FORM SUBMISSION (Assign Task) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_num_to_assign'])) {
    
    $requestNum = (int)$_POST['request_num_to_assign'];

    // This is an "atomic" update. It will only update the row IF the
    // StaffID is still NULL. This prevents two staff members from
    // claiming the same task.
    $stmt_assign = $conn->prepare("
        UPDATE MaintenanceRequest 
        SET StaffID = ? 
        WHERE RequestNUM = ? AND StaffID IS NULL
    ");
    $stmt_assign->bind_param("ii", $loggedInStaffID, $requestNum);
    
    if ($stmt_assign->execute()) {
        if ($stmt_assign->affected_rows > 0) {
            $message = "Task #$requestNum has been assigned to you successfully!";
            $message_type = "success";
        } else {
            // This means someone *else* just grabbed it.
            $message = "Task #$requestNum was just assigned by another staff member.";
            $message_type = "info";
        }
    } else {
        $message = "Error assigning task: " . $conn->error;
        $message_type = "error";
    }
    $stmt_assign->close();
}


// 6. --- GET ALL UNASSIGNED TASKS ---
$unassigned_tasks = [];
// We use the new LandlordMaintenanceView to get all the data
$stmt_select = $conn->prepare("
    SELECT 
        RequestNUM, Issue, request_date, 
        tenant_name, tenant_phone,
        property_address, property_city
    FROM LandlordMaintenanceView
    WHERE StaffID IS NULL
    ORDER BY request_date ASC
");
$stmt_select->execute();
$result = $stmt_select->get_result();
while ($row = $result->fetch_assoc()) {
    $unassigned_tasks[] = $row;
}
$stmt_select->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Unassigned Tasks</title>
    
    <!-- CSS for this page ONLY -->
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0077cc;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Table styling */
        .task-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .task-table th, .task-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .task-table th {
            background: #f5f7fa;
            color: #333;
        }
        .task-table tr:hover {
            background-color: #f9f9f9;
        }

        /* Button styling */
        .btn-assign {
            display: inline-block;
            padding: 6px 12px;
            background-color: #0077cc;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn-assign:hover {
            background-color: #005fa3;
        }
        
        /* Message styling */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
        }
        .message.success {
            background-color: #e6ffed;
            border: 1px solid #b7e9c7;
            color: #006421;
        }
        .message.error {
            background-color: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
        }
        .message.info {
            background-color: #e6f7ff;
            border: 1px solid #b3e0ff;
            color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Link to go back to the main dashboard -->
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>

        <h2>Unassigned Task Pool</h2>
        <p>Select a task to assign it to your personal task list.</p>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- 1. READ-ONLY TASK DETAILS -->
        <table class="task-table">
            <thead>
                <tr>
                    <th>Reported</th>
                    <th>Tenant</th>
                    <th>Address</th>
                    <th>Issue</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($unassigned_tasks)): ?>
                    <?php foreach ($unassigned_tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['request_date']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($task['tenant_name']); ?><br>
                                <small><?php echo htmlspecialchars($task['tenant_phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($task['property_address'] . ', ' . $task['property_city']); ?></td>
                            <td><?php echo htmlspecialchars($task['Issue']); ?></td>
                            <td>
                                <!-- Mini-form for each task -->
                                <!-- (THE FIX: Change the action to be the full server path) -->
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <input type="hidden" name="request_num_to_assign" value="<?php echo $task['RequestNUM']; ?>">
                                    <button type="submit" class="btn-assign">Assign to Me</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            Good job! There are no unassigned tasks.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>