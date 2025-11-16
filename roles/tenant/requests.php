<?php
include('../../includes/auth.php');
include('../../includes/db.php');

$email = $_SESSION['user_email'];
$role = $_SESSION['role'];

if ($role !== 'tenant') {
    header('Location: ../../dashboard.php'); 
    exit;
}

$message = "";
$message_type = "";

$tenantID = null;
$stmt_tenant = $conn->prepare("
    SELECT t.TenantID 
    FROM Tenants t
    JOIN Users u ON t.UserID = u.UserID
    WHERE u.Email = ?
");
$stmt_tenant->bind_param("s", $email);
$stmt_tenant->execute();
$stmt_tenant->bind_result($tenantID);
$stmt_tenant->fetch();
$stmt_tenant->close();

if (!$tenantID) {
    die("Could not find a valid tenant profile for your user account.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $issue = trim($_POST['issue']); 

    $errors = [];
    if (empty($issue)) {
        $errors[] = "The issue description is a required field.";
    }
    if (strlen($issue) > 500) { 
        $errors[] = "The issue description must be 500 characters or less.";
    }

    if (empty($errors)) {

        $stmt_insert = $conn->prepare(
            "INSERT INTO MaintenanceRequest (Issue, TenantID, RequestDate, current_status) 
             VALUES (?, ?, CURDATE(), 'Pending')"
        );
        $stmt_insert->bind_param("si", $issue, $tenantID);
        
        if ($stmt_insert->execute()) {
            $message = "Request submitted successfully!";
            $message_type = "success";
        } else {
            $message = "An error occurred. Please try again. " . $conn->error;
            $message_type = "error";
        }
        $stmt_insert->close();
    } else {
        // Validation failed
        $message = implode(" ", $errors);
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Maintenance Request</title>
    <!-- (NEW) CSS FOR THIS PAGE -->
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            margin: 0; 
            padding: 0;
            background-color: #f9f9f9; 
        }
        .container {
            max-width: 700px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group textarea {
             width: 100%;
             padding: 8px;
             border: 1px solid #ccc;
             border-radius: 4px;
             box-sizing: border-box;
             min-height: 120px;
             font-family: inherit;
        }
        .btn-submit {
            display: inline-block;
            padding: 10px 18px;
            background-color: #0077cc;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #005fa3;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
        }
        .message.success {
            background-color: #e6ffed;
            border: 1px solid #b7e9c7;
            color: #006421;
        }
        .message.error {
            background-color: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="../../dashboard.php" class="back-link">Back to Dashboard</a>

        <h2>Submit Maintenance Request</h2>
        <p>Please describe the issue in detail. A staff member will be assigned to it shortly.</p>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>


        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <div class="form-group">
                <label for="issue">Describe the issue</label>
                <textarea name="issue" id="issue" rows="5" placeholder="E.g., The kitchen sink is leaking under the cabinet." required></textarea>
            </div>
            <button type="submit" class="btn-submit">Submit Request</button>
        </form>
    </div>

</body>
</html>