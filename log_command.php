<?php
session_start();
header('Content-Type: application/json');

// Initialize command log if not exists
if (!isset($_SESSION['command_log'])) {
    $_SESSION['command_log'] = [];
}

// Handle POST requests (command submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['device']) && isset($data['action']) && isset($data['value'])) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'device' => htmlspecialchars($data['device']),
            'action' => htmlspecialchars($data['action']),
            'value' => htmlspecialchars($data['value'])
        ];
        
        // Add to beginning of log array
        array_unshift($_SESSION['command_log'], $logEntry);
        
        // Keep only the last 20 entries
        $_SESSION['command_log'] = array_slice($_SESSION['command_log'], 0, 20);
        
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// Handle GET requests (log retrieval)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'success',
        'log' => $_SESSION['command_log']
    ]);
    exit();
}

echo json_encode(['status' => 'error']);