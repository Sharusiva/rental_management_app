<?php
session_start();
include('includes/db.php');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = $_POST['name']     ?? '';
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role']     ?? '';

    if (!$name || !$email || !$password || !$role) {
        $error = "Please fill in all required fields.";
    } else {

        // Hash password
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insert into Users
        $stmt = $conn->prepare("INSERT INTO Users (Email, PasswordHash, Role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $hashed, $role);

        if (!$stmt->execute()) {
            $error = "An account with this email and role may already exist.";
        } else {
            $userId = $stmt->insert_id;

            // Role-specific inserts
            switch ($role) {
                case 'tenant':
                    // We expect a property_id from the AJAX selection
                    $propertyId = $_POST['property_id'] ?? null;

                    if (!$propertyId) {
                        $error = "Please select your property from the list.";
                    } else {
                        $stmt2 = $conn->prepare("
                            INSERT INTO Tenants (Name, email, PhoneNum, UserID, PropertyID)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt2->bind_param("sssii", $name, $email, $phone, $userId, $propertyId);
                        $stmt2->execute();
                        $success = "Tenant account created successfully. You can now log in.";
                    }
                    break;

                case 'landlord':
                    $stmt2 = $conn->prepare("
                        INSERT INTO Landlord (Name, Email, UserID)
                        VALUES (?, ?, ?)
                    ");
                    $stmt2->bind_param("ssi", $name, $email, $userId);
                    $stmt2->execute();
                    $success = "Landlord account created successfully. You can now log in.";
                    break;

                case 'staff':
                    // For now we'll just store email as contact info; you can expand later
                    $contact = $email;
                    $stmt2 = $conn->prepare("
                        INSERT INTO Staff (Name, ContactInfo, UserID)
                        VALUES (?, ?, ?)
                    ");
                    $stmt2->bind_param("ssi", $name, $contact, $userId);
                    $stmt2->execute();
                    $success = "Staff account created successfully. You can now log in.";
                    break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="assets/login.css">
</head>

<body>

<div class="login-box">

    <h2>Create an Account</h2>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error): ?>
        <div class="error-box"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $success): ?>
        <div class="success-box"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="register-form" autocomplete="off">

        <label>Full Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Role:</label>
        <select name="role" id="role-select" required>
            <option value="" disabled selected>Select Role…</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="staff">Staff</option>
        </select>

        <!-- Tenant-only extra fields -->
        <div id="tenant-extra" style="display:none; margin-top:10px;">

            <label>Search Your Property Address:</label>
            <input type="text" id="property-search" placeholder="Start typing your address...">

            <small style="font-size:12px;color:#6b7280;">
                Begin typing your property address; select it from the list when it appears.
            </small>

            <div id="property-results"
                 style="margin-top:8px; border:1px solid #cdd8f3; border-radius:6px; max-height:150px; overflow-y:auto; display:none; background:#fff;">
            </div>

            <label> Phone Number: </label>
            <input type="text" name="phone" required>

            <!-- Hidden field used on submit -->
            <input type="hidden" name="property_id" id="property-id-hidden">
        </div>

        <!-- Staff extra fields (optional) -->
        <div id="staff-extra" style="display:none; margin-top:10px;">
            <small style="font-size:12px;color:#6b7280;">
                Staff contact info will be stored from your email for now.
            </small>
        </div>

        <input type="submit" value="Register">

    </form>

    <div class="register-link">
        Already have an account?
        <a href="index.php">Login here</a>
    </div>

</div>

<script>
// Show/hide tenant/staff-specific sections based on selected role
document.addEventListener("DOMContentLoaded", function () {

    const roleSelect     = document.getElementById("role-select");
    const tenantExtra    = document.getElementById("tenant-extra");
    const staffExtra     = document.getElementById("staff-extra");
    const propertyInput  = document.getElementById("property-search");
    const resultsBox     = document.getElementById("property-results");
    const propertyIdHidden = document.getElementById("property-id-hidden");

    if (!roleSelect) return;

    roleSelect.addEventListener("change", () => {
        const role = roleSelect.value;

        tenantExtra.style.display = (role === "tenant") ? "block" : "none";
        staffExtra.style.display  = (role === "staff")  ? "block" : "none";

        // Reset tenant-specific fields when switching away
        if (role !== "tenant") {
            propertyInput.value = "";
            propertyIdHidden.value = "";
            resultsBox.style.display = "none";
            resultsBox.innerHTML = "";
        }
    });

    // AJAX search for property, but only after a minimum number of characters
    const MIN_CHARS = 10;

    if (propertyInput) {
        propertyInput.addEventListener("input", function () {
            const query = this.value.trim();

            propertyIdHidden.value = ""; // clear selection if user types again
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";

            if (query.length < MIN_CHARS) {
                return; // too short, don't query
            }

            fetch("ajax_property_search.php?q=" + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    resultsBox.innerHTML = "";
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsBox.innerHTML = "<div style='padding:8px;font-size:13px;color:#6b7280;'>No matching properties found.</div>";
                        resultsBox.style.display = "block";
                        return;
                    }

                    data.forEach(item => {
                        const div = document.createElement("div");
                        div.style.padding = "8px 10px";
                        div.style.cursor = "pointer";
                        div.style.fontSize = "13px";
                        div.style.borderBottom = "1px solid #e5e7eb";

                        div.innerHTML = `
                            <strong>${item.address}</strong><br>
                            <span style="color:#6b7280;">Landlord: ${item.landlord_name}</span>
                        `;

                        div.addEventListener("click", () => {
                            propertyInput.value   = item.address;
                            propertyIdHidden.value = item.id;
                            resultsBox.style.display = "none";
                            resultsBox.innerHTML = "";
                        });

                        resultsBox.appendChild(div);
                    });

                    resultsBox.style.display = "block";
                })
                .catch(err => {
                    console.error("Property search error:", err);
                });
        });
    }

});
</script>

</body>
</html>
