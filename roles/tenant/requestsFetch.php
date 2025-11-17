<?php
header('Content-Type: application/json');

include('../../includes/auth.php');
include('../../includes/db.php');

$email = $_SESSION['user_email'];
$role = $_SESSION['role'];

if ($role !== 'tenant') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

$tenantID = null;
$stmt_tenant = $conn->prepare("
    SELECT t.TenantID 
    FROM Tenants t
    JOIN Users u ON t.UserID = u.UserID
    WHERE u.Email = ?
");
$stmt_tenant->bind_param("s", $email);
$stmt_tenant->execute();
$stmt_tenant->bind_result($tenantID);
$stmt_tenant->fetch();
$stmt_tenant->close();

if (!$tenantID) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not find a valid tenant profile for your user account.']);
    exit;
}

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $issue = trim($_POST['issue']); 

    $errors = [];
    if (empty($issue)) {
        $errors[] = "The issue description is a required field.";
    }
    if (strlen($issue) > 500) { 
        $errors[] = "The issue description must be 500 characters or less.";
    }

    if (empty($errors)) {
        $stmt_insert = $conn->prepare(
            "INSERT INTO MaintenanceRequest (Issue, TenantID, RequestDate, current_status) 
             VALUES (?, ?, CURDATE(), 'Pending')"
        );
        $stmt_insert->bind_param("si", $issue, $tenantID);
        
        if ($stmt_insert->execute()) {
            $response = [
                'success' => true,
                'message' => 'Request submitted successfully!'
            ];
        } else {
            http_response_code(500);
            $response = [
                'success' => false,
                'message' => 'Database error: ' . $conn->error
            ];
        }
        $stmt_insert->close();
    } else {
        http_response_code(400); 
        $response = [
            'success' => false,
            'message' => implode(" ", $errors)
        ];
    }
} else {
    http_response_code(405); 
    $response = [
        'success' => false,
        'message' => 'Invalid request method.'
    ];
}

echo json_encode($response);
exit;
?>