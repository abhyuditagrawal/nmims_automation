<?php
include("config.php");
session_start();

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM users WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        session_destroy();
        header("Location: index.html");
    } else {
        echo "Error deleting: " . mysqli_error($conn);
    }
}
?>
