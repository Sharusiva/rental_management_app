<?php
$landlordEmail = $_SESSION['user_email'] ?? null;
$userName = $_SESSION['user_name'];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="welcome-banner">
    <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
    <p>Here is an overview of your properties, payments, and tenant requests.</p>
</div>

<!-- ==========================================================================================
    FINANCIAL OVERVIEW
=========================================================================================== -->
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
        $data = $stmt->get_result()->fetch_assoc();

        $paid    = $data['total_paid'] ?? 0;
        $pending = $data['total_pending'] ?? 0;
        $late    = $data['total_late'] ?? 0;
    }
    ?>

    <div class="finance-summary">
        <div class="finance-item"><h4>Paid</h4><p>$<?php echo number_format($paid, 2); ?></p></div>
        <div class="finance-item"><h4>Pending</h4><p>$<?php echo number_format($pending, 2); ?></p></div>
        <div class="finance-item"><h4>Overdue</h4><p>$<?php echo number_format($late, 2); ?></p></div>
    </div>
</section>

<!-- ==========================================================================================
    PROPERTY OVERVIEW 
=========================================================================================== -->
    <section class="dashboard-section">
    <h2>Property Overview</h2>
   <div class = overview-row>
    <!-- Stacked bar chart for weekly earnings vs maintenance costs --!>
    <div style="max-width: 700px; margin-bottom: 20px;">
        <canvas id="propertyEarningsChart"></canvas>
    </div>
    <! -- AI chat to ask about property related queries -- !>
     <div id="ai-chatbot-container">
    	<div id="ai-chat">
         <div id="ai-chat-log"></div>
         <input id="ai-chat-input" placeholder="Ask about your properties..." />
        </div>
     </div>
    </div>
    <?php
    $properties = [];

    if ($landlordEmail) {
        $stmt = $conn->prepare("
            SELECT 
                p.PropertyID,
                p.Address,
                t.Name AS tenant_name,
                ls.EndDate AS lease_end,
                ls.RentPrice AS rent
            FROM Property p
            LEFT JOIN Tenants t ON t.PropertyID = p.PropertyID
            LEFT JOIN Lease ls ON ls.PropertyID = p.PropertyID AND ls.TenantID = t.TenantID
            JOIN Landlord l ON p.LandlordID = l.LandlordID
            WHERE l.Email = ?
            ORDER BY p.PropertyID
        ");
        $stmt->bind_param("s", $landlordEmail);
        $stmt->execute();
        $properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    ?>

    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Property</th>
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
                    $leaseEnd = $prop['lease_end'];
                    $status = "Active";

                    if (!$leaseEnd) {
                        $status = "No Lease";
                    } else {
                        $end = new DateTime($leaseEnd);
                        $now = new DateTime();
                        $days = $now->diff($end)->days;

                        if ($end < $now) $status = "Expired";
                        elseif ($days <= 60) $status = "Expiring Soon";
                    }
                ?>

                <tr>
                    <td><?php echo htmlspecialchars($prop['Address']); ?></td>
                    <td><?php echo htmlspecialchars($prop['tenant_name'] ?? 'Vacant'); ?></td>
                    <td><?php echo htmlspecialchars($prop['lease_end'] ?? '-'); ?></td>
                    <td><?php echo "$" . number_format($prop['rent'] ?? 0, 2); ?></td>

                    <td>
                        <?php if ($status === "Active"): ?>
                            <span class="badge active">Active</span>
                        <?php elseif ($status === "Expiring Soon"): ?>
                            <span class="badge expiring">Expiring Soon</span>
                        <?php elseif ($status === "Expired"): ?>
                            <span class="badge expired">Expired</span>
                        <?php else: ?>
                            <span class="badge neutral">No Lease</span>
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

<!-- ==========================================================================================
    MAINTENANCE REQUESTS (clean FK-based version)
=========================================================================================== -->
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
            JOIN Tenants t ON mr.TenantID = t.TenantID
            JOIN Property p ON t.PropertyID = p.PropertyID
            JOIN Staff s ON mr.StaffID = s.StaffID
            JOIN Landlord l ON p.LandlordID = l.LandlordID
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
                    <td><?php echo htmlspecialchars($req['Address']); ?></td>
                    <td><?php echo htmlspecialchars($req['tenant_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['Issue']); ?></td>
                    <td><?php echo htmlspecialchars($req['staff_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['RequestDate']); ?></td>
                    <td><?php echo "$" . number_format($req['Cost'], 2); ?></td>
                    <td><?php echo htmlspecialchars($req['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No requests found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<!-- Script to render the stacked bar chart --!>
<script>
document.addEventListener("DOMContentLoaded", function () {

    fetch('roles/landlord/api_property_weekly_summary.php')
        .then(response => response.json())
        .then(rows => {

            console.log("CHART DATA:", rows); // DEBUG

            // Canvas must exist
            const ctx = document.getElementById('propertyEarningsChart');
            if (!ctx) {
                console.error("Canvas not found!");
                return;
            }

            const labels = rows.map(r => r.address);
            const incomeData = rows.map(r => r.income_week);
            const maintenanceData = rows.map(r => r.maintenance_week);
	    const net = rows.map (r => r.income_week - r.maintenance_week);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Net Profit',
                            data: net,
                            backgroundColor: 'rgba(76, 175, 80, 0.8)',
                            stack: 'stack1'
                        },
                        {
                            label: 'Cost',
                            data: maintenanceData,
                            backgroundColor: 'rgba(244, 67, 54, 0.3)',
                            stack: 'stack1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Net Profit per Property'
                        }
                    },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    }
                }
            });

        })
        .catch(err => {
            console.error("Error loading chart data:", err);
        });

});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById("ai-chat-input");
    const log = document.getElementById("ai-chat-log");

    if (!input) {
        console.error("Chat input not found!");
        return;
    }

    input.addEventListener("keydown", function(e) {
        if (e.key === "Enter" && this.value.trim() !== "") {

            const message = this.value.trim();
            this.value = "";

            // Display user message
            log.innerHTML += `<div><strong>You:</strong> ${message}</div>`;

            fetch("roles/landlord/ai_chat.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ message })
            })
            .then(res => res.json())
            .then(data => {
                log.innerHTML += `<div><strong>AI:</strong> ${data.reply}</div>`;
                log.scrollTop = log.scrollHeight;
            });
        }
    });
});
</script>
