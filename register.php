<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $sapId = $_POST['sap_id']; // New (matches the HTML name)
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $department = $_POST['department'];

    // Validate inputs
    if (empty($fullname) || empty($email) || empty($sapId) || empty($password) || empty($confirmPassword) || empty($department)) {
        die("All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }

    if (strlen($password) < 8) {
        die("Password must be at least 8 characters long.");
    }

    // Check if email or SAP ID already exists
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ? OR sap_id = ?");
    $stmt->execute([$email, $sapId]);
    if ($stmt->rowCount() > 0) {
        die("Email or SAP ID already registered.");
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO teachers (fullname, email, sap_id, password, department) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$fullname, $email, $sapId, $hashedPassword, $department]);

    if ($stmt->rowCount() > 0) {
        header("Location: login.html?registration=success");
        exit();
    } else {
        die("Registration failed. Please try again.");
    }
}
?>