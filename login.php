<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username is email or SAP ID
    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM teachers WHERE sap_id = ?");
    }

    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Authentication successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['sap_id'] = $user['sap_id'];
        $_SESSION['department'] = $user['department'];
        
        header("Location: classroom.php");
        exit();
    } else {
        // Authentication failed
        header("Location: login.html?error=invalid_credentials");
        exit();
    }
}
?>