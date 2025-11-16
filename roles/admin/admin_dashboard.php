<?php
// IMPORTANT: NO session_start() here
// dashboard.php already includes auth + db connection

// Prevent direct access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

// ----------------------
// DELETE USER
// ----------------------
$delete_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int)$_POST['delete_user'];

    $stmt = $conn->prepare("DELETE FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $delete_msg = "User #$id deleted successfully.";
    } else {
        $delete_msg = "Error deleting user.";
    }
}

// ----------------------
// SEARCH + FILTER LOGIC
// ----------------------
$search      = $_GET['q'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT UserID, Email, Role FROM Users WHERE 1";

if ($search !== '') {
    $query .= " AND (Email LIKE '%$search%' OR UserID LIKE '%$search%')";
}

if ($role_filter !== '') {
    $query .= " AND Role = '$role_filter'";
}

$query .= " ORDER BY UserID ASC";

$users = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="../../assets/style.css">

<style>
.admin-box {
    max-width: 1100px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
}

.search-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search-row input, .search-row select {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}
th {
    background: #f1f5f9;
}

.btn-delete {
    background: #dc2626;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
}
.btn-delete:hover { background: #b91c1c; }

.btn-edit {
    background: #3b82f6;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
}
.btn-edit:hover { background: #1e40af; }

.msg {
    padding: 10px;
    background: #d1fae5;
    border-left: 4px solid #10b981;
    margin-bottom: 15px;
}

/* Modal styling */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 2000;
}
.modal-content {
    width: 450px;
    background: white;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    position: relative;
}
.close {
    position: absolute;
    top: 12px;
    right: 15px;
    font-size: 1.4rem;
    cursor: pointer;
}

.modal-form input, .modal-form select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    width: 100%;
}
.btn-primary {
    padding: 10px;
    background: #0077cc;
    border: none;
    color: white;
    border-radius: 8px;
    font-size: 15px;
}
.btn-primary:hover { background: #005fa3; }

.role-section {
    background: #f1f5ff;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
}
</style>
</head>

<body>

<div class="admin-box">
    <h1>Admin Dashboard</h1>
    <p>Manage all users — search, filter, edit, delete.</p>

    <?php if ($delete_msg): ?>
        <div class="msg"><?= $delete_msg; ?></div>
    <?php endif; ?>

    <form method="get">
        <div class="search-row">
            <input type="text" name="q" placeholder="Search Email or ID..." value="<?= htmlspecialchars($search) ?>">
            
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin"    <?= $role_filter==='admin'?'selected':'' ?>>Admin</option>
                <option value="tenant"   <?= $role_filter==='tenant'?'selected':'' ?>>Tenant</option>
                <option value="landlord" <?= $role_filter==='landlord'?'selected':'' ?>>Landlord</option>
                <option value="staff"    <?= $role_filter==='staff'?'selected':'' ?>>Staff</option>
            </select>

            <button class="btn-return">Search</button>
        </div>
    </form>

    <button class="btn-return" onclick="openCreateModal()">+ Create New User</button>

    <table>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>

        <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $u['UserID'] ?></td>
                <td><?= $u['Email'] ?></td>
                <td><?= ucfirst($u['Role']) ?></td>
                <td>
                    <button class="btn-edit"
                        onclick="openEditModal(<?= $u['UserID'] ?>, '<?= $u['Email'] ?>', '<?= $u['Role'] ?>')">
                        Edit
                    </button>

                    <form method="post" style="display:inline;" 
                        onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="delete_user" value="<?= $u['UserID'] ?>">
                        <button class="btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>

    </table>
</div>

<!-- CREATE USER MODAL -->
<div id="createModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeCreateModal()">&times;</span>
    <h2>Create User</h2>

    <form method="post" action="roles/admin/user_create.php" class="modal-form">
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Role:</label>
        <select name="role" id="createRoleSelect" onchange="toggleCreateRoleFields()" required>
            <option value="">Select</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>

        <div id="createRoleFields"></div>

        <button class="btn-primary">Create</button>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>

    <h2>Edit User</h2>

    <form method="post" action="roles/admin/user_edit.php" class="modal-form">
        <input type="hidden" id="editUserID" name="user_id">

        <label>Email:</label>
        <input type="email" id="editEmail" name="email" required>

        <label>New Password (optional):</label>
        <input type="password" name="password">

        <label>Role:</label>
        <select id="editRoleSelect" name="role" onchange="toggleEditRoleFields()" required>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>

        <div id="editRoleFields"></div>

        <button class="btn-primary">Save Changes</button>
    </form>
  </div>
</div>

<script>
function openCreateModal() { document.getElementById("createModal").style.display = "flex"; }
function closeCreateModal() { document.getElementById("createModal").style.display = "none"; }
function openEditModal(id, email, role) {
    document.getElementById("editModal").style.display = "flex";
    document.getElementById("editUserID").value = id;
    document.getElementById("editEmail").value = email;
    document.getElementById("editRoleSelect").value = role;
    toggleEditRoleFields();
}
function closeEditModal() { document.getElementById("editModal").style.display = "none"; }

function toggleCreateRoleFields() {
    const role = document.getElementById("createRoleSelect").value;
    document.getElementById("createRoleFields").innerHTML = getRoleFields(role);
}
function toggleEditRoleFields() {
    const role = document.getElementById("editRoleSelect").value;
    document.getElementById("editRoleFields").innerHTML = getRoleFields(role);
}

function getRoleFields(role) {
    switch (role) {
        case "tenant":
            return `
                <div class="role-section">
                    <label>Name:</label><input name="name">
                    <label>Phone:</label><input name="phone">
                    <label>Property ID:</label><input name="property_id" type="number">
                </div>`;
        case "landlord":
            return `
                <div class="role-section">
                    <label>Name:</label><input name="name">
                </div>`;
        case "staff":
            return `
                <div class="role-section">
                    <label>Name:</label><input name="name">
                    <label>Contact Info:</label><input name="contact">
                </div>`;
        default:
            return "";
    }
}
</script>

</body>
</html>
