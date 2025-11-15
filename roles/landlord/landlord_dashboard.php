<?php
$landlordEmail = $_SESSION['user_email'] ?? null;
$userName      = $_SESSION['user_name'] ?? "Landlord";
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../../assets/style.css">


<div class="welcome-banner">
    <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
    <p>Here is an overview of your properties, payments, and tenant requests.</p>
</div>

<!-- ======================================================================
     FINANCIAL OVERVIEW
====================================================================== -->
<section class="dashboard-section">
    <h2>Financial Overview</h2>

<?php
$paid = $pending = $late = 0;

if ($landlordEmail) {
    $stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN pm.Status = 'Paid' THEN pm.Amount ELSE 0 END) AS total_paid,
            SUM(CASE WHEN pm.Status = 'Pending' THEN pm.Amount ELSE 0 END) AS total_pending,
            SUM(CASE WHEN pm.Status = 'Late' THEN pm.Amount ELSE 0 END) AS total_late
        FROM Payments pm
        JOIN Lease ls ON pm.LeaseNum = ls.LeaseNum
        JOIN Property p ON ls.PropertyID = p.PropertyID
        JOIN Landlord l ON p.LandlordID = l.LandlordID
        WHERE l.Email = ?
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();

    $paid    = $totals['total_paid'] ?? 0;
    $pending = $totals['total_pending'] ?? 0;
    $late    = $totals['total_late'] ?? 0;
}
?>

    <div class="finance-summary">
        <div class="finance-item"><h4>Paid</h4><p>$<?php echo number_format($paid,2); ?></p></div>
        <div class="finance-item"><h4>Pending</h4><p>$<?php echo number_format($pending,2); ?></p></div>
        <div class="finance-item"><h4>Overdue</h4><p>$<?php echo number_format($late,2); ?></p></div>
    </div>
</section>



<!-- ======================================================================
     PROPERTY OVERVIEW (WITH CHART + CHATBOT)
====================================================================== -->
<section class="dashboard-section">
    <h2>Property Overview</h2>

    <div class="overview-row">

        <!-- LEFT: Chart -->
        <div class="chart-wrapper">
            <canvas id="propertyEarningsChart"></canvas>
        </div>

        <!-- RIGHT: Chatbot -->
        <div id="ai-chatbot-container">
            <div id="ai-chat">
                <div id="ai-chat-log"></div>
                <input id="ai-chat-input" placeholder="Ask about your properties..." />
            </div>
        </div>

    </div> <!-- END overview-row -->

</section>


<?php
$properties = [];

if ($landlordEmail) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM LandlordPropertyView
        WHERE landlord_email = ?
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Property Address</th>
                <th>Tenant</th>
                <th>Lease End</th>
                <th>Rent</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!empty($properties)): ?>
            <?php foreach ($properties as $prop): ?>
                <?php
                    $leaseEnd = $prop['lease_end'] ?? null;
                    $status   = "Active";

                    if ($leaseEnd) {
                        $end  = new DateTime($leaseEnd);
                        $now  = new DateTime();
                        $diff = $now->diff($end)->days;

                        if ($end < $now)          $status = "Expired";
                        elseif ($diff <= 60)      $status = "Expiring Soon";
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($prop['property_address']); ?></td>
                    <td><?= htmlspecialchars($prop['tenant_name'] ?? 'Vacant'); ?></td>
                    <td><?= htmlspecialchars($prop['lease_end'] ?? '-'); ?></td>
                    <td>$<?= number_format($prop['rent'] ?? 0, 2); ?></td>

                    <td>
                        <?php if ($status === "Active"): ?>
                            <span class="badge active">Active</span>
                        <?php elseif ($status === "Expiring Soon"): ?>
                            <span class="badge expiring">Expiring Soon</span>
                        <?php else: ?>
                            <span class="badge expired">Expired</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else: ?>
            <tr><td colspan="5">No properties found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>



<!-- ======================================================================
     MAINTENANCE REQUESTS
====================================================================== -->
<section class="dashboard-section">
    <h2>Maintenance Requests</h2>

<?php
$requests = [];

if ($landlordEmail) {
    $stmt = $conn->prepare("
        SELECT
            p.Address,
            t.Name AS tenant_name,
            mr.Issue,
            mr.RequestDate,
            mr.current_status AS status,
            mr.Cost,
            s.Name AS staff_name
        FROM MaintenanceRequest mr
        JOIN Tenants t   ON mr.TenantID   = t.TenantID
        JOIN Property p  ON t.PropertyID  = p.PropertyID
        JOIN Staff s     ON mr.StaffID    = s.StaffID
        JOIN Landlord l  ON p.LandlordID  = l.LandlordID
        WHERE l.Email = ?
        ORDER BY mr.RequestDate DESC
    ");
    $stmt->bind_param("s", $landlordEmail);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Property</th>
                <th>Tenant</th>
                <th>Issue</th>
                <th>Staff</th>
                <th>Date</th>
                <th>Cost</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['Address']); ?></td>
                    <td><?= htmlspecialchars($req['tenant_name']); ?></td>
                    <td><?= htmlspecialchars($req['Issue']); ?></td>
                    <td><?= htmlspecialchars($req['staff_name']); ?></td>
                    <td><?= htmlspecialchars($req['RequestDate']); ?></td>
                    <td>$<?= number_format($req['Cost'], 2); ?></td>
                    <td><?= htmlspecialchars($req['status']); ?></td>
                </tr>
            <?php endforeach; ?>

        <?php else: ?>
            <tr><td colspan="7">No requests found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>



<!-- ======================================================================
     CHART + CHATBOT SCRIPTS
====================================================================== -->

<script>
document.addEventListener("DOMContentLoaded", function () {

    fetch('roles/landlord/api_property_weekly_summary.php')
        .then(res => res.json())
        .then(rows => {
            const ctx = document.getElementById('propertyEarningsChart');
            if (!ctx) { console.error("Canvas missing"); return; }

            const labels = rows.map(r => r.address);
            const income = rows.map(r => r.income_week);
            const cost   = rows.map(r => r.maintenance_week);
            const net    = rows.map(r => r.income_week - r.maintenance_week);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Net Profit',
                            data: net,
                            backgroundColor: 'rgba(76,175,80,0.8)',
                            stack: 'stack'
                        },
                        {
                            label: 'Cost',
                            data: cost,
                            backgroundColor: 'rgba(244,67,54,0.3)',
                            stack: 'stack'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x:{stacked:true}, y:{stacked:true} }
                }
            });
        });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("ai-chat-input");
    const log   = document.getElementById("ai-chat-log");

    if (!input) return;

    input.addEventListener("keydown", e => {
        if (e.key === "Enter" && input.value.trim() !== "") {

            const msg = input.value.trim();
            input.value = "";
            log.innerHTML += `<div class="user-msg">${msg}</div>`;

            fetch("roles/landlord/ai_chat.php", {
                method:"POST",
                headers:{"Content-Type":"application/json"},
                body:JSON.stringify({ message: msg })
            })
            .then(r => r.json())
            .then(d => {
                log.innerHTML += `<div class="ai-msg">${d.reply}</div>`;
                log.scrollTop = log.scrollHeight;
            });
        }
    });
});
</script>
