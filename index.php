<?php
session_start();

$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // SECURED QUERY (NO SQL INJECTION)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // CHECK PASSWORD (PLAIN TEXT VERSION - for now)
        if ($user["password"] === $password) {

            // SET SESSION
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            // INSERT ACTIVITY LOG
            $action = "User logged in";
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $logStmt->bind_param("is", $user["user_id"], $action);
            $logStmt->execute();

            // REDIRECT BASED ON ROLE
        if ($user["role"] === "admin") {
    header("Location: admin_dashboard.php"); // or keep admin landing page
    exit();
      } elseif ($user["role"] === "manager") {
    header("Location: manager_dashboard.php");
    exit();
    } else {
    header("Location: staff_dashboard.php");
    exit();
}

        } else {
            $error = "Wrong password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login - TilapiaDetect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #2ec4b6, #0a2540);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.login-card {
    background-color: #f4f9f9;
    padding: 30px;
    border-radius: 12px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
.login-card h2 {
    color: #0a2540;
    margin-bottom: 20px;
    text-align: center;
}
.error-msg {
    color: red;
    font-weight: bold;
    text-align: center;
    margin-top: 10px;
}
</style>
</head>

<body>

<div class="login-card">
<h2>TilapiaDetect Login</h2>

<form method="POST">
<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" class="btn btn-primary w-100">Login</button>

<?php if (!empty($error)) : ?>
<p class="error-msg"><?php echo $error; ?></p>
<?php endif; ?>

</form>

<hr>

<div class="text-center">
<a href="register.php">Create Account</a>
</div>

</div>

</body>
</html>