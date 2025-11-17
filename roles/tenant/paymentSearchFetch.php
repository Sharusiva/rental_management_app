<?php
include('../../includes/auth.php');
include('../../includes/db.php');

$email = $_SESSION['user_email'];
$role = $_SESSION['role'];

if ($role !== 'tenant') {
    http_response_code(403); 
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? 'filter'; 
$search_status = $_GET['status'] ?? '';
$search_from = $_GET['date_from'] ?? '';
$search_to = $_GET['date_to'] ?? '';


$payments = [];
$params = [];
$types = "";

$sql = "SELECT p.DueDate, p.Amount, p.Status
        FROM Payments p
        JOIN Lease l ON p.LeaseNum = l.LeaseNum
        JOIN Tenants t ON l.TenantID = t.TenantID
        JOIN Users u ON t.UserID = u.UserID
        WHERE u.Email = ?";

$params[] = $email;
$types .= "s";

if (!empty($search_status)) {
    $sql .= " AND p.Status = ?";
    $params[] = $search_status;
    $types .= "s";
}

if (!empty($search_from)) {
    $sql .= " AND p.DueDate >= ?";
    $params[] = $search_from;
    $types .= "s";
}

if (!empty($search_to)) {
    $sql .= " AND p.DueDate <= ?";
    $params[] = $search_to;
    $types .= "s";
}

$sql .= " ORDER BY p.DueDate DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payments[] = $row; 
}
$stmt->close();


if ($action === 'download_csv') {
    
    $filename = "payment_history_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Due Date', 'Amount', 'Status']);
    
    if (!empty($payments)) {
        foreach ($payments as $payment) {
            fputcsv($output, [
                $payment['DueDate'], 
                $payment['Amount'], 
                $payment['Status']
            ]);
        }
    }
    
    fclose($output);
    exit; 
}


header('Content-Type: application/json');
echo json_encode($payments);
exit;
?>