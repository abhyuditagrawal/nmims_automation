<?php
session_start();
header('Content-Type: application/json');

// Initialize log if not exists
if (!isset($_SESSION['command_log'])) {
    $_SESSION['command_log'] = [];
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'success',
        'log' => array_values($_SESSION['command_log'])
    ]);
    exit();
}

// Handle POST requests (clearing log)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    $_SESSION['command_log'] = [];
    echo json_encode(['status' => 'success']);
    exit();
}

echo json_encode(['status' => 'error']);
?>