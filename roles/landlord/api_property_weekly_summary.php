<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../includes/auth.php');
include('../../includes/db.php');

header('Content-Type: application/json');

$landlordEmail = $_SESSION['user_email'] ?? null;

if (!$landlordEmail) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.PropertyID,
        p.Address,
        COALESCE((
            SELECT SUM(pm.Amount)
            FROM Lease ls
            JOIN Payments pm ON pm.LeaseNum = ls.LeaseNum
            WHERE ls.PropertyID = p.PropertyID
              AND pm.PaymentDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ), 0) AS income_week,
        COALESCE((
            SELECT SUM(mr.Cost)
            FROM MaintenanceRequest mr
	    JOIN Tenants t on mr.TenantID = t.TenantID
            WHERE t.PropertyID = p.PropertyID
              AND mr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ), 0) AS maintenance_week
    FROM Property p
    JOIN Landlord l ON p.LandlordID = l.LandlordID
    WHERE l.Email = ?
    ORDER BY p.PropertyID
");

$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'property_id'      => (int)$row['PropertyID'],
        'address'          => $row['Address'],
        'income_week'      => (float)$row['income_week'],
        'maintenance_week' => (float)$row['maintenance_week'],
    ];
}

echo json_encode($rows);
exit;
