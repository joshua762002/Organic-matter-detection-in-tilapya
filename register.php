<?php
$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST["fullname"];
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Insert new staff account
    $conn->query("INSERT INTO users (username, password, full_name, role)
                  VALUES ('$username', '$password', '$fullname', 'staff')");

    // Redirect to login page
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Create Account - TilapiaDetect</title>
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
</style>
</head>

<body>

<div class="login-card">
<h2>Create Account</h2>

<form method="POST">

<div class="mb-3">
<label>Full Name</label>
<input type="text" name="fullname" class="form-control" required>
</div>

<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" class="btn btn-success w-100">Register</button>

</form>

<hr>

<div class="text-center">
<a href="login.php">Back to Login</a>
</div>

</div>

</body>
</html>