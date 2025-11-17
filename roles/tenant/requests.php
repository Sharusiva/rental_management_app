<?php
include('../../includes/auth.php');

if ($_SESSION['role'] !== 'tenant') {
    header('Location: ../../dashboard.php'); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Maintenance Request</title>
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
    </style>
</head>
<body>

    <div class="container">
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>

        <h2>Submit Maintenance Request</h2>
        <p>Please describe the issue in detail. A staff member will be assigned to it shortly.</p>
        
        <div id="message-container" class="message"></div>

        <form method="POST" id="request-form">
            <div class="form-group">
                <label for="issue">Describe the issue</label>
                <textarea name="issue" id="issue-textarea" rows="5" placeholder="E.g., The kitchen sink is leaking under the cabinet." required></textarea>
            </div>
            <button type="submit" id="submit-button" class="btn-submit">Submit Request</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('request-form');
            const button = document.getElementById('submit-button');
            const messageContainer = document.getElementById('message-container');
            const textarea = document.getElementById('issue-textarea');

            form.addEventListener('submit', async function(e) {
                e.preventDefault(); 
                
                button.disabled = true;
                button.textContent = 'Submitting...';
                messageContainer.style.display = 'none';

                const formData = new FormData(form);

                try {
                    const response = await fetch('requestsFetch.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    messageContainer.className = 'message'; 

                    if (response.ok) {
                        messageContainer.classList.add('success');
                        messageContainer.textContent = result.message;
                        textarea.value = ''; 
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
                    button.textContent = 'Submit Request';
                }
            });
        });
    </script>

</body>
</html>