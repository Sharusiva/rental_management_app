<?php
// 1. Authenticate and connect to DB
include('../../includes/auth.php');
include('../../includes/db.php');

// 2. Get user data from session
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];
$user_email = $_SESSION['user_email'];

// 3. --- STAFF ONLY ---
//    We still need this for security
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
$task = null; // To store task data

// 5. Check for the RequestNUM from the URL or POST. This is mandatory.
// (FIX 1: Get $requestNum from GET on page load, or POST on form submit)
if (isset($_GET['request_num'])) {
    $requestNum = (int)$_GET['request_num'];
} elseif (isset($_POST['request_num'])) {
    $requestNum = (int)$_POST['request_num'];
} else {
    die("No task was specified. Please go back to the dashboard and select a task.");
}


// 6. --- HANDLE FORM SUBMISSION (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- (NEW) VALIDATION BLOCK ---
    $errors = [];
    $allowed_statuses = ['Pending', 'In Progress', 'Completed', 'On Hold'];
    
    $new_status = $_POST['status'];
    $new_cost_input = $_POST['cost'];
    $cost_to_save = NULL;

    // 1. Validate Status
    if (!in_array($new_status, $allowed_statuses)) {
        $errors[] = "Invalid status selected.";
    }

    // 2. Validate Cost (data type check)
    if (!empty($new_cost_input)) {
        if (!is_numeric($new_cost_input)) {
            $errors[] = "Cost must be a valid number.";
        } elseif ((float)$new_cost_input < 0) {
            $errors[] = "Cost cannot be negative.";
        } else {
            $cost_to_save = (float)$new_cost_input; // Set the valid, non-empty cost
        }
    }
    // If $new_cost_input is empty, $cost_to_save remains NULL, which is correct.

    // --- (END) VALIDATION BLOCK ---

    if (empty($errors)) {
        // Validation passed, proceed with database update
        
        // Security Check: Update the row ONLY if the RequestNUM and StaffID match
        $stmt_update = $conn->prepare("
            UPDATE MaintenanceRequest 
            SET current_status = ?, Cost = ? 
            WHERE RequestNUM = ? AND StaffID = ?
        ");
        // Use the validated $cost_to_save variable
        $stmt_update->bind_param("sdii", $new_status, $cost_to_save, $requestNum, $loggedInStaffID);
        
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $message = "Task updated successfully!";
                $message_type = "success";
            } else {
                $message = "No changes were made. (Or this task is not assigned to you).";
                $message_type = "info";
            }
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
        $stmt_update->close();

    } else {
        // Validation failed, show errors
        $message = "Update failed: " . implode(" ", $errors);
        $message_type = "error";
    }
}



$stmt_select = $conn->prepare("
    SELECT 
        Issue, request_date, Cost, current_status,
        tenant_name, tenant_phone,
        property_address, property_city
    FROM LandlordMaintenanceView
    WHERE RequestNUM = ? AND StaffID = ?
");
$stmt_select->bind_param("ii", $requestNum, $loggedInStaffID);
$stmt_select->execute();
$result = $stmt_select->get_result();
$task = $result->fetch_assoc();
$stmt_select->close();

if (!$task) {
    die("Error: Task #$requestNum not found or it is not assigned to you.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $task['current_status'] = $new_status;
    $task['Cost'] = $cost_to_save;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Task #<?php echo $requestNum; ?></title>
    
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 700px;
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
        .task-info {
            background: #fdfdfd;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .task-info h3 { margin-top: 0; }
        .task-info p { margin: 5px 0; }
        .task-info strong { color: #333; }

        form {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        .form-group textarea {
             width: 100%;
             padding: 8px;
             border: 1px solid #ccc;
             border-radius: 4px;
             box-sizing: border-box;
             min-height: 80px;
             background: #f9f9f9;
             font-family: inherit;
        }
        .issue-box {
             background: #f9f9f9;
             border: 1px solid #eee;
             border-radius: 4px;
             padding: 10px;
             min-height: 60px;
             line-height: 1.5;
             font-family: inherit;
        }
        .btn-submit {
            display: inline-block;
            padding: 10px 18px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #218838;
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
        <a href="../../dashboard.php" class="back-link"> Back to Dashboard</a>

        <h2>Manage Maintenance Task #<?php echo $requestNum; ?></h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="task-info">
            <h3>Task Details</h3>
            <p><strong>Tenant:</strong> <?php echo htmlspecialchars($task['tenant_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($task['tenant_phone']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($task['property_address'] . ', ' . $task['property_city']); ?></p>
            <p><strong>Reported:</strong> <?php echo htmlspecialchars($task['request_date']); ?></p>
            <p><strong>Issue:</strong></p>
            <div class="issue-box">
                <?php echo nl2br(htmlspecialchars($task['Issue']));  ?>
            </div>
        </div>


        <form method="POST" action="">
            
            <input type="hidden" name="request_num" value="<?php echo $requestNum; ?>">

            <div class="form-group">
                <label for="status">Update Status</label>
                <select name="status" id="status">
                    <option value="Pending" <?php if($task['current_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="In Progress" <?php if($task['current_status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                    <option value="Completed" <?php if($task['current_status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                    <option value="On Hold" <?php if($task['current_status'] == 'On Hold') echo 'selected'; ?>>On Hold</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cost">Update Cost (e.g., 50.00)</label>
                <input type="number" step="0.01" min="0" name="cost" id="cost" value="<?php echo htmlspecialchars($task['Cost']); ?>" placeholder="0.00">
            </div>

            <button type="submit" class="btn-submit">Update Task</button>
        </form>
    </div>

</body>
</html>