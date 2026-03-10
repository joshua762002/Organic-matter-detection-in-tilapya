<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";

/* ================= ADD USER ================= */
if (isset($_POST["add_user"])) {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $full_name = trim($_POST["full_name"]);
    $role = isset($_POST["role"]) ? strtolower(trim($_POST["role"])) : "staff";

    // Validate role: only admin, staff, manager allowed
    if (!in_array($role, ["admin", "staff", "manager"])) {
        $role = "staff";
    }

    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {

        $stmt = $conn->prepare("INSERT INTO users (username,password,full_name,role)
                                VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $username, $password, $full_name, $role);
        $stmt->execute();

        header("Location: manage_users.php?msg=added");
        exit();
    }

    header("Location: manage_users.php?msg=exists");
    exit();
}

/* ================= DELETE USER ================= */
if (isset($_GET["delete"])) {

    $delete_id = intval($_GET["delete"]);

    if ($delete_id != $_SESSION["user_id"]) {

        try {

            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();

            header("Location: manage_users.php?msg=deleted");
            exit();

        } catch (mysqli_sql_exception $e) {

            header("Location: manage_users.php?msg=has_records");
            exit();
        }
    }

    header("Location: manage_users.php");
    exit();
}

/* ================= UPDATE USER ================= */
if (isset($_POST["update_user"])) {

    $user_id = intval($_POST["user_id"]);
    $username = $_POST["username"];
    $full_name = $_POST["full_name"];
    $role = $_POST["role"];

    // Validate role
    if (!in_array($role, ["admin", "staff", "manager"])) {
        $role = "staff";
    }

    $stmt = $conn->prepare("UPDATE users 
                            SET username=?, full_name=?, role=?
                            WHERE user_id=?");
    $stmt->bind_param("sssi", $username, $full_name, $role, $user_id);
    $stmt->execute();

    header("Location: manage_users.php?msg=updated");
    exit();
}

/* ================= MESSAGE DISPLAY ================= */
if (isset($_GET["msg"])) {

    if ($_GET["msg"] == "deleted") {
        $message = "User deleted successfully.";
    }

    if ($_GET["msg"] == "has_records") {
        $message = "Cannot delete this user because there are existing detection records.";
    }

    if ($_GET["msg"] == "added") {
        $message = "User added successfully.";
    }

    if ($_GET["msg"] == "updated") {
        $message = "User updated successfully.";
    }

    if ($_GET["msg"] == "exists") {
        $message = "Username already exists.";
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f9f9;">

<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">
<span class="navbar-brand">User Management</span>
<a href="admin_dashboard.php" class="btn btn-light btn-sm">Back</a>
</div>
</nav>

<div class="container mt-4">

<?php if (!empty($message)) : ?>
<div class="alert alert-warning">
<?php echo $message; ?>
</div>
<?php endif; ?>

<!-- Add New User -->
<div class="card mb-4 shadow">
<div class="card-header bg-success text-white">
Add New User
</div>

<div class="card-body">
<form method="POST" class="row g-3">

<div class="col-md-3">
<input type="text" name="username" class="form-control"
placeholder="Username" required>
</div>

<div class="col-md-3">
<input type="password" name="password" class="form-control"
placeholder="Password" required>
</div>

<div class="col-md-3">
<input type="text" name="full_name" class="form-control"
placeholder="Full Name">
</div>

<div class="col-md-2">
<select name="role" class="form-select">
<option value="staff">Staff</option>
<option value="manager">Manager</option>
<option value="admin">Admin</option>
</select>
</div>

<div class="col-md-1">
<button type="submit" name="add_user"
class="btn btn-success w-100">
Add
</button>
</div>

</form>
</div>
</div>

<!-- All Users Table -->
<div class="card shadow">
<div class="card-header bg-primary text-white">
All Registered Users
</div>

<div class="card-body">
<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Username</th>
<th>Full Name</th>
<th>Role</th>
<th>Created</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php while($row = $users->fetch_assoc()) { ?>
<tr>
<form method="POST">
<td><?php echo $row["user_id"]; ?></td>

<td>
<input type="text" name="username"
value="<?php echo $row["username"]; ?>"
class="form-control form-control-sm" required>
</td>

<td>
<input type="text" name="full_name"
value="<?php echo $row["full_name"]; ?>"
class="form-control form-control-sm">
</td>

<td>
<select name="role" class="form-select form-select-sm">
<option value="admin" <?php if($row["role"]=="admin") echo "selected"; ?>>Admin</option>
<option value="manager" <?php if($row["role"]=="manager") echo "selected"; ?>>Manager</option>
<option value="staff" <?php if($row["role"]=="staff") echo "selected"; ?>>Staff</option>
</select>
</td>

<td><?php echo $row["created_at"]; ?></td>

<td>
<input type="hidden" name="user_id"
value="<?php echo $row["user_id"]; ?>">

<button type="submit"
name="update_user"
class="btn btn-sm btn-success mb-1">
Update
</button>

<?php if($row["user_id"] != $_SESSION["user_id"]) { ?>
<a href="manage_users.php?delete=<?php echo $row["user_id"]; ?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this user?');">
Delete
</a>
<?php } ?>

</td>
</form>
</tr>
<?php } ?>
</tbody>

</table>
</div>
</div>

</div>
</body>
</html>