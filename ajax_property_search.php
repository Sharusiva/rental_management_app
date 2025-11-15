<?php
// ajax_property_search.php
include('includes/db.php');

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 5) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.PropertyID,
        p.Address,
        l.Name AS landlord_name
    FROM Property p
    JOIN Landlord l ON p.LandlordID = l.LandlordID
    WHERE p.Address LIKE CONCAT('%', ?, '%')
    LIMIT 5
");
$stmt->bind_param("s", $q);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = [
        'id'            => (int)$row['PropertyID'],
        'address'       => $row['Address'],
        'landlord_name' => $row['landlord_name']
    ];
}

echo json_encode($rows);
