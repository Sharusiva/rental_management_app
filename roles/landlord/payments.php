<?php
session_start();
include('../../includes/db.php');

// Make sure landlord is logged in
if (!isset($_SESSION['user_email'])) {
  header("Location: ../../index.php");
  exit();
}

// Get logged-in landlord email
$landlordEmail = $_SESSION['user_email'];

// Find landlord ID
$stmt = $conn->prepare("SELECT LandlordID FROM Landlord WHERE Email = ?");
$stmt->bind_param("s", $landlordEmail);
$stmt->execute();
$stmt->bind_result($landlordID);
$stmt->fetch();
$stmt->close();

// Get all payments for this landlord
$query = "
SELECT 
    p.PaymentID,
    p.PaymentDate,
    p.DueDate,
    p.Amount,
    p.Status,
    t.Name AS TenantName,
    pr.Address AS PropertyAddress
FROM Payments p
JOIN Lease l ON p.LeaseNum = l.LeaseNum
JOIN Property pr ON l.PropertyID = pr.PropertyID
JOIN Tenants t ON l.TenantID = t.TenantID
WHERE pr.LandlordID = ?
ORDER BY p.DueDate DESC;
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $landlordID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Landlord Payments</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .content {
      margin: 60px auto;
      max-width: 1000px;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0;
    }

    .back-btn {
      background-color: #0077cc;
      color: white;
      padding: 10px 18px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.2s ease-in-out;
    }

    .back-btn:hover {
      background-color: #005fa3;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      border-bottom: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }

    th {
      background: #0077cc;
      color: #fff;
    }

    .status {
      padding: 4px 8px;
      border-radius: 4px;
      color: #fff;
      font-weight: bold;
    }

    .status.paid { background: #28a745; }
    .status.pending { background: #ffc107; color: #333; }
    .status.late { background: #dc3545; }

    .request-btn {
      background: #0077cc;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      cursor: pointer;
      transition: 0.2s;
    }

    .request-btn:hover {
      background: #005fa3;
    }

    .no-data {
      text-align: center;
      color: gray;
      padding: 20px;
      font-style: italic;
    }
  </style>
</head>

<body>
  <div class="content">
    <h2>💰 Payment Management
      <a href="../../../dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    </h2>

    <table>
      <thead>
        <tr>
          <th>Tenant</th>
          <th>Property</th>
          <th>Amount</th>
          <th>Due Date</th>
          <th>Payment Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['TenantName']); ?></td>
              <td><?php echo htmlspecialchars($row['PropertyAddress']); ?></td>
              <td>$<?php echo number_format($row['Amount'], 2); ?></td>
              <td><?php echo $row['DueDate']; ?></td>
              <td><?php echo $row['PaymentDate'] ?? '—'; ?></td>
              <td>
                <span class="status <?php echo strtolower($row['Status']); ?>">
                  <?php echo $row['Status']; ?>
                </span>
		<td>
		  <?php
		    $status = strtolower(trim($row['Status']));
		    if ($status !== 'paid' && $status !== 'late'):
		      $tenantEmail = $row['TenantEmail'] ?? 'unknown@example.com';
		      $subject = rawurlencode("Rent Payment Reminder for {$row['PropertyAddress']}");
		      $body = rawurlencode("Hello {$row['TenantName']},\n\nThis is a friendly reminder that your rent payment of $"
		        . number_format($row['Amount'], 2)
		        . " for the property at {$row['PropertyAddress']} is due on {$row['DueDate']}.\n\n"
		        . "Please make the payment on time to avoid late fees.\n\nThank you,\nYour Landlord");
		  ?>
		    <a href="mailto:<?php echo $tenantEmail; ?>?subject=<?php echo $subject; ?>&body=<?php echo $body; ?>" class="request-btn">
		      Request Payment
		    </a>
		  <?php else: ?>
		    <span style="color: gray;">—</span>
		  <?php endif; ?>
		</td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="no-data">No payments found for your properties.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
