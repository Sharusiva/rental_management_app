<?php
// 1. Authenticate and connect to DB
include('../../includes/auth.php');
include('../../includes/db.php');

// 2. Get user data and role from session
$email = $_SESSION['user_email'];
$role = $_SESSION['role'];

// 3. --- TENANT ONLY ---
if ($role !== 'tenant') {
    header('Location: ../../dashboard.php'); 
    exit;
}

// 4. --- Get Payment History ---
$payments = [];

// This query joins from the logged-in user's email all the way to their payments
$stmt = $conn->prepare("
    SELECT 
        p.DueDate, 
        p.Amount, 
        p.Status
    FROM Payments p
    JOIN Lease l ON p.LeaseNum = l.LeaseNum
    JOIN Tenants t ON l.TenantID = t.TenantID
    JOIN Users u ON t.UserID = u.UserID
    WHERE u.Email = ?
    ORDER BY p.DueDate DESC
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payment History</title>
    <!-- CSS for this page ONLY -->
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 900px;
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
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .history-table th, .history-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .history-table th {
            background: #f5f7fa;
            color: #333;
        }
        .history-table tr:hover {
            background-color: #f9f9f9;
        }

        /* Badge Styling */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            text-transform: capitalize;
        }
        .badge.paid { background: #28a745; }
        .badge.late { background: #dc3545; }
        .badge.pending { background: #ffc107; color: #000; }
        .badge.future { background: #6c757d; }
    </style>
</head>
<body>

    <div class="container">
        <!-- Link to go back to the main dashboard -->
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>

        <h2>My Payment History</h2>
        <p>A complete record of all your payments.</p>
        
        <table class="history-table">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['DueDate']); ?></td>
                            <td>$<?php echo number_format($payment['Amount'], 2); ?></td>
                            <td>
                                <?php
                                // Logic to set the badge color
                                $status = strtolower($payment['Status']);
                                $status_class = '';
                                if ($status == 'paid') {
                                    $status_class = 'paid';
                                } elseif ($status == 'late') {
                                    $status_class = 'late';
                                } elseif ($status == 'pending') {
                                    $status_class = 'pending';
                                } else {
                                    $status_class = 'future';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($payment['Status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px;">
                            No payment history found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>