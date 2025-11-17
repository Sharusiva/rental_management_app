<?php
include('../../includes/auth.php');
include('../../includes/db.php');

$role = $_SESSION['role'];

if ($role !== 'tenant') {
    header('Location: ../../dashboard.php'); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payment History (AJAX)</title>
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
        .search-form {
            background: #fdfdfd;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto auto; 
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            flex-grow: 1;
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
        .btn-submit, .btn-download {
            display: inline-block;
            padding: 10px 18px;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            height: 38px; 
        }
        .btn-submit {
            background-color: #0077cc;
        }
        .btn-download {
            background-color: #28a745; 
        }
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
        <a href="../../dashboard.php" class="back-link">⬅️ Back to Dashboard</a>

        <h2>My Payment History</h2>
        <p>Search and filter your complete payment record.</p>
        
        <form class="search-form" id="filter-form">
            <div class="form-group">
                <label for="status">Status (Category)</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="Paid">Paid</option>
                    <option value="Late">Late</option>
                    <option value="Pending">Pending</option>
                    <option value="Future">Future</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input type="date" name="date_from" id="date_from">
            </div>
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input type="date" name="date_to" id="date_to">
            </div>
            
            <button type="button" id="filter-button" class="btn-submit">Filter</button>
            <button type="button" id="download-button" class="btn-download">CSV</button>
        </form>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="history-table-body">
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px;">
                        Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const filterButton = document.getElementById('filter-button');
            const downloadButton = document.getElementById('download-button');
            const tableBody = document.getElementById('history-table-body');
            
            function getBadgeClass(status) {
                const s = status.toLowerCase();
                if (s === 'paid') return 'paid';
                if (s === 'late') return 'late';
                if (s === 'pending') return 'pending';
                return 'future'; 
            }

            function getFilterQueryString() {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                return params.toString();
            }

            async function fetchAndRenderTable() {
                const queryString = getFilterQueryString();
                const apiUrl = `paymentSearchFetch.php?${queryString}`;
                
                tableBody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px;">Loading...</td></tr>';

                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    const payments = await response.json();

                    tableBody.innerHTML = '';

                    if (payments.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px;">No payment history found matching your search.</td></tr>';
                        return;
                    }

                    payments.forEach(payment => {
                        const statusClass = getBadgeClass(payment.Status);
                        const amount = parseFloat(payment.Amount).toFixed(2);
                        
                        const row = `
                            <tr>
                                <td>${payment.DueDate}</td>
                                <td>$${amount}</td>
                                <td>
                                    <span class="badge ${statusClass}">
                                        ${payment.Status}
                                    </span>
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });

                } catch (error) {
                    console.error('Fetch error:', error);
                    tableBody.innerHTML = `<tr><td colspan="3" style="text-align: center; padding: 20px; color: red;">Error loading data.</td></tr>`;
                }
            }

            filterButton.addEventListener('click', function() {
                fetchAndRenderTable();
            });

            downloadButton.addEventListener('click', function() {
                const queryString = getFilterQueryString();
                const downloadUrl = `paymentSearchFetch.php?action=download_csv&${queryString}`;
                window.location.href = downloadUrl;
            });

            fetchAndRenderTable();
        });
    </script>
</body>
</html>