<?php
include('../../includes/auth.php');
include('../../includes/db.php');

if ($_SESSION['role'] !== 'staff') {
    header('Location: ../../dashboard.php'); 
    exit;
}

$contacts = [];
$result = $conn->query("SELECT entity_name, contact_email, entity_type FROM MixedContactInfo ORDER BY entity_type, entity_name");
if ($result) {
    while ($row = $result->fetch_assoc()) $contacts[] = $row;
}

$properties = [];
$result = $conn->query("SELECT full_property_address, landlord_email FROM LandlordAndPropertyAddresses ORDER BY full_property_address");
if ($result) {
    while ($row = $result->fetch_assoc()) $properties[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Contact Directory</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <style>
      body { background-color: #f9f9f9; padding: 20px; }
      .container { max-width: 900px; margin: 0 auto; }
  </style>
</head>
<body>

<div class="container">
    <div class="welcome-banner">
      <h2>📞 Staff Directory</h2>
      <p>Contact information for Tenants and Landlords, plus a property lookup.</p>
    </div>

    <a href="../../dashboard.php" class="btn-return">⬅ Back to Dashboard</a>

    <section class="dashboard-section">
      <h2>People Directory</h2>
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
      <?php if (!empty($contacts)): ?>
          <?php foreach ($contacts as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['entity_name']); ?></td>
            <td>
                <a href="mailto:<?php echo htmlspecialchars($c['contact_email']); ?>">
                    <?php echo htmlspecialchars($c['contact_email']); ?>
                </a>
            </td>
            <td>
                <span class="badge <?php echo ($c['entity_type'] == 'Tenant') ? 'active' : 'expiring'; ?>">
                    <?php echo htmlspecialchars($c['entity_type']); ?>
                </span>
            </td>
          </tr>
          <?php endforeach; ?>
      <?php else: ?>
          <tr><td colspan="3">No contacts found.</td></tr>
      <?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="dashboard-section">
      <h2>Property Owners</h2>
      <p style="margin-bottom:10px; color:#666;">Use this to find the Landlord for a specific address.</p>
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Full Property Address</th>
            <th>Landlord Email</th>
          </tr>
        </thead>
        <tbody>
      <?php if (!empty($properties)): ?>
          <?php foreach ($properties as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['full_property_address']); ?></td>
            <td>
                <a href="mailto:<?php echo htmlspecialchars($p['landlord_email']); ?>">
                    <?php echo htmlspecialchars($p['landlord_email']); ?>
                </a>
            </td>
          </tr>
          <?php endforeach; ?>
      <?php else: ?>
          <tr><td colspan="2">No property records found.</td></tr>
      <?php endif; ?>
        </tbody>
      </table>
    </section>
</div>

</body>
</html>