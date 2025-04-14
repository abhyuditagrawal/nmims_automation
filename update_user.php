<?php
include("config.php");
session_start();

if (isset($_POST['id'], $_POST['username'], $_POST['email'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    $sql = "UPDATE users SET username='$username', email='$email' WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        header("Location: login.php?status=updated");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
