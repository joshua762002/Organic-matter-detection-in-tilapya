<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}


if ($_SESSION["role"] != "admin") {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");

$id = $_GET['id'];


if ($id == $_SESSION["user_id"]) {
    header("Location: manage_users.php");
    exit();
}

$conn->query("DELETE FROM users WHERE user_id = $id");

header("Location: manage_users.php");
exit();