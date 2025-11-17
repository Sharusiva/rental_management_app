<?php
header('Content-Type: application/json');

include('../../includes/auth.php');
include('../../includes/db.php');

$user_email = $_SESSION['user_email'];
$role = $_SESSION['role'];

if ($role !== 'staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not find a valid staff profile for your user account.']);
    exit;
}

$response = [
    'success' => false,
    'message' => 'An invalid request occurred.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestNum = (int)$_POST['request_num'];
    $new_status = $_POST['status'];
    $new_cost_input = $_POST['cost'];
    $cost_to_save = NULL;

    $errors = [];
    $allowed_statuses = ['Pending', 'In Progress', 'Completed', 'On Hold'];

    if (!in_array($new_status, $allowed_statuses)) {
        $errors[] = "Invalid status selected.";
    }

    if (!empty($new_cost_input)) {
        if (!is_numeric($new_cost_input)) {
            $errors[] = "Cost must be a valid number.";
        } elseif ((float)$new_cost_input < 0) {
            $errors[] = "Cost cannot be negative.";
        } else {
            $cost_to_save = (float)$new_cost_input;
        }
    }

    if (empty($errors)) {
        $stmt_update = $conn->prepare("
            UPDATE MaintenanceRequest 
            SET current_status = ?, Cost = ? 
            WHERE RequestNUM = ? AND StaffID = ?
        ");
        $stmt_update->bind_param("sdii", $new_status, $cost_to_save, $requestNum, $loggedInStaffID);
        
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $response = ['success' => true, 'message' => 'Task updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'No changes were made. (Or this task is not assigned to you).'];
            }
        } else {
            http_response_code(500);
            $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }
        $stmt_update->close();
    } else {
        http_response_code(400);
        $response = ['success' => false, 'message' => implode(" ", $errors)];
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['request_num'])) {
        http_response_code(400);
        $response = ['success' => false, 'message' => 'No task was specified.'];
        echo json_encode($response);
        exit;
    }
    
    $requestNum = (int)$_GET['request_num'];
    
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

    if ($task) {
        $response = ['success' => true, 'task' => $task];
    } else {
        http_response_code(404);
        $response = ['success' => false, 'message' => "Error: Task #$requestNum not found or it is not assigned to you."];
    }
}

echo json_encode($response);
exit;
?>