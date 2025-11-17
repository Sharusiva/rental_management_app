<?php
include('../../includes/auth.php');

if ($_SESSION['role'] !== 'staff') {
    header('Location: ../../dashboard.php'); 
    exit;
}

$requestNum = (int)($_GET['request_num'] ?? 0);
if ($requestNum <= 0) {
    die("No task was specified. Please go back to the dashboard and select a task.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Task #<?php echo $requestNum; ?></title>
    
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
        .task-info {
            background: #fdfdfd;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .task-info h3 { margin-top: 0; }
        .task-info p { margin: 5px 0; }
        .task-info strong { color: #333; }

        form {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }
        .issue-box {
             background: #f9f9f9;
             border: 1px solid #eee;
             border-radius: 4px;
             padding: 10px;
             min-height: 60px;
             line-height: 1.5;
             font-family: inherit;
        }
        .btn-submit {
            display: inline-block;
            padding: 10px 18px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
        .btn-submit:disabled {
            background-color: #a0a0a0;
            cursor: not-allowed;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
            display: none;
        }
        .message.success {
            background-color: #e6ffed;
            border: 1px solid #b7e9c7;
            color: #006421;
            display: block;
        }
        .message.error {
            background-color: #ffe6e6;
            border: 1px solid #ffb3b3;
            color: #cc0000;
            display: block;
        }
        .message.info {
            background-color: #e6f7ff;
            border: 1px solid #b3e0ff;
            color: #0056b3;
            display: block;
        }
        .loading {
            text-align: center;
            padding: 40px;
            font-size: 1.2rem;
            color: #555;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>

        <h2>Manage Maintenance Task #<?php echo $requestNum; ?></h2>
        
        <div id="message-container" class="message"></div>
        
        <div id="loader" class="loading">Loading task details...</div>

        <div id="task-content" style="display: none;">
            <div class="task-info">
                <h3>Task Details</h3>
                <p><strong>Tenant:</strong> <span id="tenant-name"></span></p>
                <p><strong>Phone:</strong> <span id="tenant-phone"></span></p>
                <p><strong>Address:</strong> <span id="property-address"></span></p>
                <p><strong>Reported:</strong> <span id="request-date"></span></p>
                <p><strong>Issue:</strong></p>
                <div class="issue-box" id="issue-box"></div>
            </div>

            <form method="POST" id="update-form">
                <input type="hidden" name="request_num" value="<?php echo $requestNum; ?>">

                <div class="form-group">
                    <label for="status">Update Status</label>
                    <select name="status" id="status-select">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cost">Update Cost (e.g., 50.00)</label>
                    <input type="number" step="0.01" min="0" name="cost" id="cost-input" placeholder="0.00">
                </div>

                <button type="submit" id="submit-button" class="btn-submit">Update Task</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const requestNum = <?php echo $requestNum; ?>;
            const loader = document.getElementById('loader');
            const taskContent = document.getElementById('task-content');
            const messageContainer = document.getElementById('message-container');
            
            const form = document.getElementById('update-form');
            const button = document.getElementById('submit-button');
            
            const tenantName = document.getElementById('tenant-name');
            const tenantPhone = document.getElementById('tenant-phone');
            const propertyAddress = document.getElementById('property-address');
            const requestDate = document.getElementById('request-date');
            const issueBox = document.getElementById('issue-box');
            const statusSelect = document.getElementById('status-select');
            const costInput = document.getElementById('cost-input');

            async function loadTaskDetails() {
                try {
                    const response = await fetch(`updateTaskFetch.php?request_num=${requestNum}`);
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Error loading task.');
                    }
                    
                    const task = result.task;
                    tenantName.textContent = task.tenant_name;
                    tenantPhone.textContent = task.tenant_phone;
                    propertyAddress.textContent = `${task.property_address}, ${task.property_city}`;
                    requestDate.textContent = task.request_date;
                    issueBox.textContent = task.Issue;
                    statusSelect.value = task.current_status;
                    costInput.value = task.Cost || '';
                    
                    loader.style.display = 'none';
                    taskContent.style.display = 'block';

                } catch (error) {
                    loader.style.display = 'none';
                    messageContainer.className = 'message error';
                    messageContainer.textContent = error.message;
                    messageContainer.style.display = 'block';
                }
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault(); 
                
                button.disabled = true;
                button.textContent = 'Updating...';
                messageContainer.style.display = 'none';

                const formData = new FormData(form);

                try {
                    const response = await fetch('updateTaskFetch.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    messageContainer.className = 'message'; 

                    if (response.ok) {
                        messageContainer.classList.add('success');
                        messageContainer.textContent = result.message;
                    } else {
                        messageContainer.classList.add('error');
                        messageContainer.textContent = result.message || 'An error occurred.';
                    }
                    messageContainer.style.display = 'block';

                } catch (error) {
                    messageContainer.className = 'message error';
                    messageContainer.textContent = 'A network error occurred. Please try again.';
                    messageContainer.style.display = 'block';
                } finally {
                    button.disabled = false;
                    button.textContent = 'Update Task';
                }
            });
            
            loadTaskDetails();
        });
    </script>
</body>
</html>