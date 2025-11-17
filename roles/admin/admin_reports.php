<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../includes/auth.php');
include('../../includes/db.php');

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

$portfolio = [];
$result = $conn->query("SELECT * FROM PropertyOverview ORDER BY rent DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $portfolio[] = $row;
}

$tenant_stats = [];
$result = $conn->query("SELECT * FROM tenant_request_counts ORDER BY totalrequestsfiled DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $tenant_stats[] = $row;
}

$support_log = [];
$result = $conn->query("SELECT * FROM TenantsIssueAndSupport");
if ($result) {
    while ($row = $result->fetch_assoc()) $support_log[] = $row;
}

$orphans = [];
$result = $conn->query("SELECT * FROM LandlordPropertyFullJoin");
if ($result) {
    while ($row = $result->fetch_assoc()) $orphans[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Analytics Reports</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0; 
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .badge-alert {
            color: #dc3545;
            background: #ffe6e6;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .badge-normal {
            color: #28a745;
            background: #e6ffed;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="welcome-banner">
        <h2>System Analytics & Reports</h2>
        <p>Global overview of properties, tenant activity, and database integrity.</p>
    </div>

    <a href="../../dashboard.php" class="btn-return">⬅ Back to Dashboard</a>

    <section class="dashboard-section">
        <h2>Global Occupancy Report</h2>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Property Address</th>
                    <th>Current Tenant</th>
                    <th>Lease End</th>
                    <th>Monthly Rent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($portfolio)): ?>
                    <?php foreach ($portfolio as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['property_address']); ?></td>
                            <td><?php echo htmlspecialchars($p['tenant_name'] ?? 'Vacant'); ?></td>
                            <td><?php echo htmlspecialchars($p['lease_end'] ?? '-'); ?></td>
                            <td>$<?php echo number_format($p['rent'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No property data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="dashboard-section">
        <h2>Tenant Request Volume</h2>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Tenant Name</th>
                    <th>Total Requests Filed</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tenant_stats)): ?>
                    <?php foreach ($tenant_stats as $stat): ?>
                        <?php 
                            $count = $stat['totalrequestsfiled'];
                            $isHigh = ($count >= 5); 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['tenantname']); ?></td>
                            <td><strong><?php echo $count; ?></strong></td>
                            <td>
                                <?php if ($isHigh): ?>
                                    <span class="badge-alert">High Activity</span>
                                <?php else: ?>
                                    <span class="badge-normal">Normal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No request data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="dashboard-section">
        <h2>Live Maintenance Feed</h2>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Reported Issue</th>
                    <th>Assigned Staff</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($support_log)): ?>
                    <?php foreach ($support_log as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['Tennant_Name']); ?></td>
                            <td><?php echo htmlspecialchars($log['Issue']); ?></td>
                            <td>
                                <?php if ($log['Staff_Name']): ?>
                                    <?php echo htmlspecialchars($log['Staff_Name']); ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No maintenance logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="dashboard-section">
        <h2>Property Landlord Check</h2>
        <p style="margin-bottom:15px; color:#666; font-size:0.9em;">
            This section identifies Landlords with no properties, or Properties with no Landlords.
        </p>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Landlord Name</th>
                    <th>Property Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orphans)): ?>
                    <?php foreach ($orphans as $o): ?>
                        <tr>
                            <td>
                                <?php echo !empty($o['landlord_name']) ? htmlspecialchars($o['landlord_name']) : '<em style="color:#ccc">Missing</em>'; ?>
                            </td>
                            <td>
                                <?php echo !empty($o['property_address']) ? htmlspecialchars($o['property_address']) : '<em style="color:#ccc">Missing</em>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center; color: green;">✅ All data is linked correctly. No orphans found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</div>

</body>
</html>