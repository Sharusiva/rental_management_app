<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../includes/auth.php');
include('../../includes/db.php');

if ($_SESSION['role'] !== 'tenant') {
    header('Location: ../../dashboard.php');
    exit;
}


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

$tenantEmail = $_SESSION['user_email'];
$lease = null; 
$stmt = $conn->prepare("
    SELECT * FROM TenantLeaseDetailsView
    WHERE user_email = ?
    LIMIT 1
");
$stmt->bind_param("s", $tenantEmail);
$stmt->execute();
$result = $stmt->get_result();
$lease = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lease Details</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #0077cc;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
        .lease-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .lease-header h2 {
            margin: 0;
            color: #111;
        }
        .lease-header p {
            margin: 5px 0 0;
            font-size: 1.1rem;
            color: #555;
        }

        .lease-section {
            margin-bottom: 30px;
        }
        .lease-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            color: #0077cc;
        }
        .lease-details p {
            margin: 10px 0;
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
        }
        .lease-details p strong {
            display: inline-block;
            width: 160px; 
            color: #000;
        }
        
        .no-lease {
            text-align: center;
            padding: 40px;
            background-color: #fdfdfd;
            border: 1px dashed #ddd;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>
        
        <?php if ($lease): ?>
            
            <div class="lease-header">
                <h2>Lease Agreement Details</h2>
                <p><?php echo htmlspecialchars($lease['property_address']); ?></p>
            </div>

            <div class="lease-section">
                <h3>Lease Terms</h3>
                <div class="lease-details">
                    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($lease['StartDate']); ?></p>
                    <p><strong>End Date:</strong> <?php echo htmlspecialchars($lease['EndDate']); ?></p>
                    <p><strong>Monthly Rent:</strong> $<?php echo number_format($lease['RentPrice'], 2); ?></p>
                    <p><strong>Rent Due On:</strong> The <?php echo htmlspecialchars($lease['DayOfMonthDue'] . getOrdinalSuffix($lease['DayOfMonthDue'])); ?> of each month</p>
                </div>
            </div>

            <div class="lease-section">
                <h3>Tenant Information</h3>
                <div class="lease-details">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($lease['tenant_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($lease['tenant_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($lease['tenant_phone']); ?></p>
                </div>
            </div>

            <div class="lease-section">
                <h3>Property & Landlord Information</h3>
                <div class="lease-details">
                    <p><strong>Property Address:</strong> <?php echo htmlspecialchars($lease['property_address'] . ', ' . $lease['property_city'] . ', ' . $lease['property_province']); ?></p>
                    <p><strong>Landlord Name:</strong> <?php echo htmlspecialchars($lease['landlord_name']); ?></p>
                    <p><strong>Landlord Contact:</strong> <?php echo htmlspecialchars($lease['landlord_contact']); ?></p>
                </div>
            </div>

        <?php else: ?>
            
            <div class="no-lease">
                <h2>No Lease Found</h2>
                <p>We could not find an active lease associated with your account.</p>
            </div>

        <?php endif; ?>
        
    </div>

</body>
</html>