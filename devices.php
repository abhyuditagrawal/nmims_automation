<?php
session_start();

// Include database configuration
require_once 'config.php'; // ADDED: Include database connection

function sendToFlaskServer($classroom, $device, $action, $value) {
    $url = 'http://localhost:5000/log_command';
    $data = [
        'classroom' => $classroom,
        'device' => $device,
        'action' => $action,
        'value' => $value
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result !== false;
}

if (isset($_GET['get_commands'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'command_log' => $_SESSION['command_log'] ?? []
    ]);
    exit();
}

if (isset($_POST['clear_commands'])) {
    $_SESSION['command_log'] = [];
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}

$currentClassroom = isset($_GET['classroom']) ? htmlspecialchars($_GET['classroom']) : null;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.html");
    exit();
}

// Initialize device states for all classrooms
if (!isset($_SESSION['device_states'])) {
    $_SESSION['device_states'] = [];
}

// Initialize attendance for all classrooms
if (!isset($_SESSION['attendance'])) {
    $_SESSION['attendance'] = [];
}

// Initialize device states for current classroom if not exists
if ($currentClassroom && !isset($_SESSION['device_states'][$currentClassroom])) {
    $_SESSION['device_states'][$currentClassroom] = [
        'lights' => ['power' => false, 'color' => '#ffffff', 'brightness' => 50],
        'ac' => ['power' => false, 'temp' => 24, 'fan' => 2],
        'cameras' => ['power' => false, 'recording' => false],
        'smartboard' => ['power' => false, 'brightness' => 70, 'slide' => 1],
        'wifi' => ['enabled' => false, 'password' => 'nmims123', 'range' => 75],
        'speakers' => ['power' => false, 'volume' => 50]
    ];
}

if (!isset($_SESSION['command_log'])) {
    $_SESSION['command_log'] = [];
}

if (isset($_POST['submit_attendance']) && $currentClassroom) {
    $_SESSION['attendance'][$currentClassroom] = [
        'batch' => $_POST['batch'],
        'present_rollnos' => $_POST['present_rollnos'] ?? [],
        'date' => date('Y-m-d')
    ];
    
    // ADDED: Store attendance in database
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO automation_logs (
                user_id, classroom, type, batch, present_rollnos
            ) VALUES (?, ?, 'attendance', ?, ?)
        ");
        $present_rollnos = !empty($_POST['present_rollnos']) ? implode(',', $_POST['present_rollnos']) : '';
        $stmt->execute([
            $_SESSION['user_id'],
            $currentClassroom,
            $_POST['batch'],
            $present_rollnos
        ]);
    } catch (PDOException $e) {
        // Log error silently to avoid disrupting user experience
        error_log("Attendance save error: " . $e->getMessage());
    }
    
    header("Location: devices.php?classroom=" . urlencode($currentClassroom));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentClassroom) {
    foreach ($_POST as $key => $value) {
        if (preg_match('/(.+)_power$/', $key, $matches)) {
            $device = $matches[1];
            $_SESSION['device_states'][$currentClassroom][$device]['power'] = ($value === 'on');
            logCommand($currentClassroom, $device, 'power', ($value === 'on') ? 'ON' : 'OFF');
        }
        elseif ($key === 'wifi_enable') {
            $_SESSION['device_states'][$currentClassroom]['wifi']['enabled'] = ($value === 'enable');
            logCommand($currentClassroom, 'wifi', 'status', ($value === 'enable') ? 'ENABLED' : 'DISABLED');
        }
        elseif ($key === 'cameras_recording') {
            $_SESSION['device_states'][$currentClassroom]['cameras']['recording'] = ($value === 'start');
            logCommand($currentClassroom, 'cameras', 'recording', ($value === 'start') ? 'STARTED' : 'STOPPED');
        }
        elseif ($key === 'smartboard_slide') {
            $current = $_SESSION['device_states'][$currentClassroom]['smartboard']['slide'];
            if ($value === 'next') {
                $_SESSION['device_states'][$currentClassroom]['smartboard']['slide'] = $current + 1;
            } elseif ($value === 'prev' && $current > 1) {
                $_SESSION['device_states'][$currentClassroom]['smartboard']['slide'] = $current - 1;
            }
            logCommand($currentClassroom, 'smartboard', 'slide', $_SESSION['device_states'][$currentClassroom]['smartboard']['slide']);
        }
        else {
            foreach ($_SESSION['device_states'][$currentClassroom] as $device => $settings) {
                foreach ($settings as $setting => $val) {
                    if ($key === $device.'_'.$setting) {
                        $_SESSION['device_states'][$currentClassroom][$device][$setting] = $value;
                        logCommand($currentClassroom, $device, $setting, $value);
                    }
                }
            }
        }
    }
    
    $_SESSION['command_log'] = array_slice($_SESSION['command_log'], 0, 20);
    header("Location: devices.php?classroom=" . urlencode($currentClassroom));
    exit();
}

function logCommand($classroom, $device, $action, $value) {
    global $conn; // ADDED: Access database connection
    
    $timestamp = date('H:i:s');
    $logEntry = [
        'timestamp' => $timestamp,
        'classroom' => $classroom,
        'device' => $device,
        'action' => $action,
        'value' => $value
    ];
    $_SESSION['command_log'][] = $logEntry;
    
    // ADDED: Store command in database
    try {
        $stmt = $conn->prepare("
            INSERT INTO automation_logs (
                user_id, classroom, type, device, action, value
            ) VALUES (?, ?, 'command', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $classroom,
            $device,
            $action,
            $value
        ]);
    } catch (PDOException $e) {
        // Log error silently to avoid disrupting user experience
        error_log("Command log error: " . $e->getMessage());
    }
    
    $url = 'http://localhost:5000/log_command';
    $data = [
        'classroom' => $classroom,
        'device' => $device,
        'action' => $action,
        'value' => $value
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];
    
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

$deviceStates = $currentClassroom ? ($_SESSION['device_states'][$currentClassroom] ?? []) : [];
$classroomAttendance = $currentClassroom ? ($_SESSION['attendance'][$currentClassroom] ?? null) : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="NMIMS Smart College Automation System">
    <meta name="author" content="NMIMS College">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">

    <title>Device Control | NMIMS Smart College Automation</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-edu-meeting.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/lightbox.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #9c0202;
            --secondary-color: #e6911a;
        }
        
        .device-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .device-card-body {
            flex: 1;
        }
        
        .device-card:hover {
            transform: translateY(-5px);
        }
        
        .device-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .btn-device {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-device:hover {
            background-color: #7a0101;
            color: white;
        }
        
        .btn-device.active {
            background-color: var(--secondary-color);
        }
        
        .status-indicator {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-on {
            background-color: #28a745;
        }
        
        .status-off {
            background-color: #dc3545;
        }
        
        .control-group {
            margin: 15px 0;
        }
        
        .slider-container {
            display: flex;
            align-items: center;
        }
        
        .slider-value {
            margin-left: 10px;
            min-width: 30px;
            text-align: center;
        }
        
        .disabled-control {
            opacity: 0.5;
            pointer-events: none;
        }
        
        #command-panel {
            background: white;
            border: 1px solid var(--primary-color);
            border-radius: 10px;
            padding: 15px;
            margin-top: 30px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .log-entry {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .user-panel {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .device-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Equal height row */
        .equal-height-row {
            display: flex;
            flex-wrap: wrap;
        }
        
        .equal-height-row > [class*='col-'] {
            display: flex;
            flex-direction: column;
        }
        
        /* Attendance styles */
        #attendance-panel {
            background: white;
            border: 1px solid var(--primary-color);
            border-radius: 10px;
            padding: 15px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .attendance-header {
            background-color: #9c0202;
            color: white;
            padding: 10px 15px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 15px;
        }
        
        .rollno-checkbox {
            display: inline-block;
            width: 60px;
            margin: 5px;
        }
        
        .batch-selector {
            margin-bottom: 20px;
        }
        
        .batch-btn {
            margin-right: 5px;
        }
        
        .attendance-summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>

<body>

  <!-- Sub Header -->
  <div class="sub-header">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 col-sm-8">
          <div class="left-content">
            <p>Welcome to NMIMS Smart College Automation System</p>
          </div>
        </div>
        <div class="col-lg-4 col-sm-4">
          
        </div>
      </div>
    </div>
  </div>

  <!-- ***** Header Area Start ***** -->
  <header class="header-area header-sticky">
      <div class="container">
          <div class="row">
              <div class="col-12">
                  <nav class="main-nav">
                      <!-- ***** Logo Start ***** -->
                      <a href="index.html" class="logo">
                          NMIMS Automation
                      </a>
                      <!-- ***** Logo End ***** -->
                      <!-- ***** Menu Start ***** -->
                      <ul class="nav">
                          <li><a href="index.html">Home</a></li>
                          <li><a href="index.html#features">Features</a></li>
                          <li><a href="classroom.php">Classrooms</a></li>
                          <li><a href="index.html#about">About</a></li>
                          <li><a href="index.html#contact">Contact</a></li>
                          <li><a href="devices.php?logout=true" class="login-trigger">Logout</a></li>
                      </ul>        
                      <a class='menu-trigger'>
                          <span>Menu</span>
                      </a>
                      <!-- ***** Menu End ***** -->
                  </nav>
              </div>
          </div>
      </div>
  </header>
  <!-- ***** Header Area End ***** -->

  <!-- ***** Main Banner Area Start ***** -->
  <section class="section main-banner" id="top" data-section="section1">
      <video autoplay muted loop id="bg-video">
          <source src="assets/videos/nmims.mp4" type="video/mp4" />
      </video>

      <div class="video-overlay header-text">
          <div class="container">
            <div class="row">
              <div class="col-lg-12">
                <div class="caption">
              <h6>Hello <?php echo htmlspecialchars($_SESSION['fullname']); ?></h6>
              <h2>Device Control Panel</h2>
              <p>Manage classroom devices and systems</p>
          </div>
              </div>
            </div>
          </div>
      </div>
  </section>
  <!-- ***** Main Banner Area End ***** -->

  <!-- ***** Device Control Area Start ***** -->
  <section class="device-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <!-- User Panel -->
          <div class="user-panel">
            <div class="row">
              <div class="col-md-6">
                <h4><i class="fas fa-user-circle"></i> User Information</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></p>
                <?php if ($currentClassroom): ?>
                <p><strong>Classroom:</strong> <?php echo htmlspecialchars($currentClassroom); ?></p>
                <?php endif; ?>
              </div>
              <div class="col-md-6 text-md-end">
                <?php if ($currentClassroom): ?>
                <a href="classroom.php" class="btn btn-primary">
                  <i class="fas fa-exchange-alt"></i> Change Classroom
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($currentClassroom): ?>
          <!-- Device Controls -->
          <form method="POST" id="device-control-form">
            <div class="row equal-height-row">
              <!-- Smart Lights Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>Smart Lights</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="lights_power" value="on" 
                        class="btn btn-device <?= $deviceStates['lights']['power'] ? 'active' : '' ?>">
                         ON
                      </button>
                      <button type="submit" name="lights_power" value="off" 
                        class="btn btn-device <?= !$deviceStates['lights']['power'] ? 'active' : '' ?>">
                         OFF
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['lights']['power'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['lights']['power'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['lights']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Color:</label>
                      <input type="color" name="lights_color" class="form-control form-control-color" 
                        value="<?= $deviceStates['lights']['color'] ?>" 
                        <?= !$deviceStates['lights']['power'] ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['lights']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Brightness:</label>
                      <div class="slider-container">
                        <input type="range" name="lights_brightness" class="form-range" min="0" max="100" 
                          value="<?= $deviceStates['lights']['brightness'] ?>" 
                          oninput="this.nextElementSibling.textContent = this.value + '%'" 
                          <?= !$deviceStates['lights']['power'] ? 'disabled' : '' ?>>
                        <span class="slider-value"><?= $deviceStates['lights']['brightness'] ?>%</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Air Conditioner Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>Air Conditioner</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="ac_power" value="on" 
                        class="btn btn-device <?= $deviceStates['ac']['power'] ? 'active' : '' ?>">
                         ON
                      </button>
                      <button type="submit" name="ac_power" value="off" 
                        class="btn btn-device <?= !$deviceStates['ac']['power'] ? 'active' : '' ?>">
                         OFF
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['ac']['power'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['ac']['power'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['ac']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Temperature: 
                        <span id="ac-temp-value"><?= $deviceStates['ac']['temp'] ?></span>°C
                      </label>
                      <input type="range" name="ac_temp" class="form-range" min="16" max="30" 
                        value="<?= $deviceStates['ac']['temp'] ?>" 
                        oninput="document.getElementById('ac-temp-value').textContent = this.value" 
                        <?= !$deviceStates['ac']['power'] ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['ac']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Fan Speed:</label>
                      <div class="btn-group w-100">
                        <button type="submit" name="ac_fan" value="1" 
                          class="btn btn-sm <?= $deviceStates['ac']['fan'] == 1 ? 'btn-success' : 'btn-outline-secondary' ?>" 
                          <?= !$deviceStates['ac']['power'] ? 'disabled' : '' ?>>
                          Low
                        </button>
                        <button type="submit" name="ac_fan" value="2" 
                          class="btn btn-sm <?= $deviceStates['ac']['fan'] == 2 ? 'btn-success' : 'btn-outline-secondary' ?>" 
                          <?= !$deviceStates['ac']['power'] ? 'disabled' : '' ?>>
                          Medium
                        </button>
                        <button type="submit" name="ac_fan" value="3" 
                          class="btn btn-sm <?= $deviceStates['ac']['fan'] == 3 ? 'btn-success' : 'btn-outline-secondary' ?>" 
                          <?= !$deviceStates['ac']['power'] ? 'disabled' : '' ?>>
                          High
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Security Cameras Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>Security Cameras</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="cameras_power" value="on" 
                        class="btn btn-device <?= $deviceStates['cameras']['power'] ? 'active' : '' ?>">
                         ON
                      </button>
                      <button type="submit" name="cameras_power" value="off" 
                        class="btn btn-device <?= !$deviceStates['cameras']['power'] ? 'active' : '' ?>">
                         OFF
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['cameras']['power'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['cameras']['power'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group text-center <?= !$deviceStates['cameras']['power'] ? 'disabled-control' : '' ?>">
                      <button type="submit" name="cameras_recording" value="start" 
                        class="btn btn-device <?= $deviceStates['cameras']['recording'] ? 'active' : '' ?>" 
                        <?= !$deviceStates['cameras']['power'] ? 'disabled' : '' ?>>
                         Start Recording
                      </button>
                      <button type="submit" name="cameras_recording" value="stop" 
                        class="btn btn-device <?= !$deviceStates['cameras']['recording'] ? 'active' : '' ?>" 
                        <?= !$deviceStates['cameras']['power'] ? 'disabled' : '' ?>>
                         Stop Recording
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['cameras']['recording'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Recording: <?= $deviceStates['cameras']['recording'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Smart Board Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>Smart Board</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="smartboard_power" value="on" 
                        class="btn btn-device <?= $deviceStates['smartboard']['power'] ? 'active' : '' ?>">
                         ON
                      </button>
                      <button type="submit" name="smartboard_power" value="off" 
                        class="btn btn-device <?= !$deviceStates['smartboard']['power'] ? 'active' : '' ?>">
                         OFF
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['smartboard']['power'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['smartboard']['power'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['smartboard']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Screen Brightness:</label>
                      <div class="slider-container">
                        <input type="range" name="smartboard_brightness" class="form-range" min="0" max="100" 
                          value="<?= $deviceStates['smartboard']['brightness'] ?>" 
                          oninput="this.nextElementSibling.textContent = this.value + '%'" 
                          <?= !$deviceStates['smartboard']['power'] ? 'disabled' : '' ?>>
                        <span class="slider-value"><?= $deviceStates['smartboard']['brightness'] ?>%</span>
                      </div>
                    </div>
                    
                    <div class="control-group text-center <?= !$deviceStates['smartboard']['power'] ? 'disabled-control' : '' ?>">
                      <button type="submit" name="smartboard_slide" value="prev" 
                        class="btn btn-device" <?= !$deviceStates['smartboard']['power'] ? 'disabled' : '' ?>>
                         Previous
                      </button>
                      <span class="mx-2">Slide <?= $deviceStates['smartboard']['slide'] ?></span>
                      <button type="submit" name="smartboard_slide" value="next" 
                        class="btn btn-device" <?= !$deviceStates['smartboard']['power'] ? 'disabled' : '' ?>>
                         Next
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- WiFi System Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>WiFi System</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="wifi_enable" value="enable" 
                        class="btn btn-device <?= $deviceStates['wifi']['enabled'] ? 'active' : '' ?>">
                         Enable
                      </button>
                      <button type="submit" name="wifi_enable" value="disable" 
                        class="btn btn-device <?= !$deviceStates['wifi']['enabled'] ? 'active' : '' ?>">
                         Disable
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['wifi']['enabled'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['wifi']['enabled'] ? 'ENABLED' : 'DISABLED' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['wifi']['enabled'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Password:</label>
                      <div class="input-group">
                        <input type="password" name="wifi_password" class="form-control" 
                          value="<?= $deviceStates['wifi']['password'] ?>" 
                          <?= !$deviceStates['wifi']['enabled'] ? 'disabled' : '' ?>>
                        <button class="btn btn-outline-secondary" type="button" 
                          onclick="togglePasswordVisibility(this)" 
                          <?= !$deviceStates['wifi']['enabled'] ? 'disabled' : '' ?>>
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['wifi']['enabled'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Range:</label>
                      <div class="slider-container">
                        <input type="range" name="wifi_range" class="form-range" min="0" max="100" 
                          value="<?= $deviceStates['wifi']['range'] ?>" 
                          oninput="this.nextElementSibling.textContent = this.value + '%'" 
                          <?= !$deviceStates['wifi']['enabled'] ? 'disabled' : '' ?>>
                        <span class="slider-value"><?= $deviceStates['wifi']['range'] ?>%</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Speakers Card -->
              <div class="col-lg-4 col-md-6">
                <div class="device-card">
                  <div class="device-card-body">
                    <div class="text-center">
                      <h3>Audio Speakers</h3>
                    </div>
                    
                    <div class="control-group text-center">
                      <button type="submit" name="speakers_power" value="on" 
                        class="btn btn-device <?= $deviceStates['speakers']['power'] ? 'active' : '' ?>">
                         ON
                      </button>
                      <button type="submit" name="speakers_power" value="off" 
                        class="btn btn-device <?= !$deviceStates['speakers']['power'] ? 'active' : '' ?>">
                         OFF
                      </button>
                      <div class="mt-2">
                        <span class="status-indicator <?= $deviceStates['speakers']['power'] ? 'status-on' : 'status-off' ?>"></span>
                        <span>Status: <?= $deviceStates['speakers']['power'] ? 'ON' : 'OFF' ?></span>
                      </div>
                    </div>
                    
                    <div class="control-group <?= !$deviceStates['speakers']['power'] ? 'disabled-control' : '' ?>">
                      <label class="form-label">Volume:</label>
                      <div class="slider-container">
                        <input type="range" name="speakers_volume" class="form-range" min="0" max="100" 
                          value="<?= $deviceStates['speakers']['volume'] ?>" 
                          oninput="this.nextElementSibling.textContent = this.value + '%'" 
                          <?= !$deviceStates['speakers']['power'] ? 'disabled' : '' ?>>
                        <span class="slider-value"><?= $deviceStates['speakers']['volume'] ?>%</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>

          <!-- Command Log Panel -->
          <div id="command-panel">
            <div class="panel-header d-flex justify-content-between align-items-center" style="background-color: #9c0202; color: white; padding: 10px 15px; border-radius: 8px 8px 0 0;">
              <h5 class="mb-0 text-white">
                 Command Log
              </h5>
              <button id="clear-log" class="btn btn-sm btn-light">
                 Clear
              </button>
            </div>
            <div id="command-log" class="p-3"></div>
          </div>

          <!-- Attendance Panel -->
          <div id="attendance-panel">
            <div class="attendance-header">
              <h5 class="mb-0 text-white">
                <i class="fas fa-clipboard-list"></i> Attendance Management
              </h5>
            </div>
            
            <form method="POST" id="attendance-form">
              <input type="hidden" name="classroom" value="<?= $currentClassroom ?>">
              <div class="batch-selector">
                <label class="form-label">Select Batch:</label>
                <div class="btn-group" role="group">
                  <button type="button" class="btn btn-outline-primary batch-btn active" data-batch="all">All</button>
                  <button type="button" class="btn btn-outline-primary batch-btn" data-batch="a">Batch A</button>
                  <button type="button" class="btn btn-outline-primary batch-btn" data-batch="b">Batch B</button>
                  <button type="button" class="btn btn-outline-primary batch-btn" data-batch="c">Batch C</button>
                  <button type="button" class="btn btn-outline-primary batch-btn" data-batch="d">Batch D</button>
                  <button type="button" class="btn btn-outline-primary batch-btn" data-batch="e">Batch E</button>
                </div>
                <input type="hidden" name="batch" id="selected-batch" value="all">
              </div>
              
              <div class="rollno-container">
                <label class="form-label">Mark Present Students (Roll Numbers 61-121):</label>
                <div class="rollno-grid">
                  <?php for ($i = 61; $i <= 121; $i++): ?>
                    <div class="form-check form-check-inline rollno-checkbox batch-all">
                      <input class="form-check-input" type="checkbox" name="present_rollnos[]" id="rollno-<?= $i ?>" value="<?= $i ?>"
                        <?= ($classroomAttendance && in_array($i, $classroomAttendance['present_rollnos'] ?? [])) ? 'checked' : '' ?>>
                      <label class="form-check-label" for="rollno-<?= $i ?>"><?= $i ?></label>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
              
              <div class="text-center mt-3">
                <button type="submit" name="submit_attendance" class="btn btn-primary">
                   Save Attendance
                </button>
              </div>
            </form>
            
            <?php if ($classroomAttendance): ?>
              <div class="attendance-summary">
                <h6>Last Saved Attendance (<?= $classroomAttendance['date'] ?>)</h6>
                <p><strong>Batch:</strong> <?= strtoupper($classroomAttendance['batch']) ?></p>
                <p><strong>Present Students:</strong> 
                  <?php 
                    if (!empty($classroomAttendance['present_rollnos'])) {
                        echo implode(', ', $classroomAttendance['present_rollnos']);
                    } else {
                        echo 'None';
                    }
                  ?>
                </p>
                <p><strong>Total Present:</strong> <?= count($classroomAttendance['present_rollnos'] ?? []) ?></p>
              </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="alert alert-warning">
            Please select a classroom from the <a href="classroom.php">classroom selection page</a> to control devices.
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  <!-- ***** Device Control Area End ***** -->

  <!-- ***** Footer Start ***** -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <p>Copyright © 2025 NMIMS College. All Rights Reserved.
          <br>Designed by C077,C075,C080</p>
        </div>
      </div>
    </div>
  </footer>

    <!-- Scripts -->
  <!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <script src="assets/js/isotope.min.js"></script>
  <script src="assets/js/owl-carousel.js"></script>
  <script src="assets/js/lightbox.js"></script>
  <script src="assets/js/tabs.js"></script>
  <script src="assets/js/video.js"></script>
  <script src="assets/js/slick-slider.js"></script>
  <script src="assets/js/custom.js"></script>
  
  <!-- Main Script -->
  <script>
    $(document).ready(function() {
      updateCommandLog();
      setInterval(updateCommandLog, 2000);
      
      $('#clear-log').click(function() {
        if (confirm('Are you sure you want to clear the command log?')) {
          $.post('devices.php', { clear_commands: true }, function() {
            updateCommandLog();
          });
        }
      });
      
      window.togglePasswordVisibility = function(btn) {
        const input = $(btn).prev('input');
        const icon = $(btn).find('i');
        
        if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      };
      
      $('input[type="range"]').each(function() {
        $(this).next('.slider-value').text($(this).val() + '%');
      });

      // Attendance batch selection functionality
      $('.batch-btn').click(function() {
        $('.batch-btn').removeClass('active');
        $(this).addClass('active');
        const selectedBatch = $(this).data('batch');
        $('#selected-batch').val(selectedBatch);
        
        // Show/hide roll numbers based on batch selection
        if (selectedBatch === 'all') {
          $('.rollno-checkbox').show();
        } else {
          $('.rollno-checkbox').hide();
          $('.batch-' + selectedBatch).show();
        }
      });

      // Initialize batch groups for roll numbers
      const batches = ['a', 'b', 'c', 'd', 'e'];
      batches.forEach(batch => {
        const start = 61 + (batch.charCodeAt(0) - 'a'.charCodeAt(0)) * 12;
        const end = Math.min(start + 11, 121);
        
        for (let i = start; i <= end; i++) {
          $(`#rollno-${i}`).parent('.rollno-checkbox').addClass(`batch-${batch}`);
        }
      });

      // Check if there's saved attendance for this classroom and pre-select checkboxes
      <?php if (isset($_SESSION['attendance'][$currentClassroom])): ?>
        $('#selected-batch').val('<?= $_SESSION['attendance'][$currentClassroom]['batch'] ?>');
        $('.batch-btn[data-batch="<?= $_SESSION['attendance'][$currentClassroom]['batch'] ?>"]').click();
        <?php foreach ($_SESSION['attendance'][$currentClassroom]['present_rollnos'] as $rollno): ?>
          $(`#rollno-<?= $rollno ?>`).prop('checked', true);
        <?php endforeach; ?>
      <?php endif; ?>
    });
    
    function updateCommandLog() {
      $.ajax({
        url: 'devices.php?get_commands=1',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
          if (data.status === 'success' && data.command_log) {
            const $log = $('#command-log');
            $log.empty();
            
            data.command_log.forEach(entry => {
              $log.append(`
                <div class="log-entry mb-2">
                  <small class="text-muted">[${entry.timestamp}]</small>
                  <div>
                    <span class="badge bg-primary">${entry.classroom}</span>
                    <span class="badge bg-secondary">${entry.device}</span>
                    ${entry.action}: 
                    <strong class="text-success">${entry.value}</strong>
                  </div>
                </div>
              `);
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Error loading command log:', error);
        }
      });
    }
  </script>

  <!-- Flask Real-time Script -->
  <script>
    const flaskEventSource = new EventSource('http://localhost:5000/stream');
    
    flaskEventSource.onmessage = function(e) {
      const data = JSON.parse(e.data);
      if (data.command) {
        updateCommandLog();
      }
    };
  </script>
</body>
</html>