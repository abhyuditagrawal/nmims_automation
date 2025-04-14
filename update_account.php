<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    
    // Validate inputs
    if (empty($fullname) || empty($email) || empty($department)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already in use by another account.";
        } else {
            // Update user in database
            $stmt = $conn->prepare("UPDATE teachers SET fullname = ?, email = ?, department = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $department, $_SESSION['user_id']]);
            
            // Update session data
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['department'] = $department;
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="NMIMS Smart College Automation System">
    <meta name="author" content="NMIMS College">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">

    <title>Update Profile | NMIMS Smart College Automation</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-edu-meeting.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/lightbox.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <style>
        .update-form {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 30px;
            margin: 50px auto;
            max-width: 600px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
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
                          <li><a href="dashboard.php"><i class="fa fa-user"></i> My Profile</a></li>
                          <li><a href="logout.php" class="login-trigger">Logout</a></li>
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
              <h2>Update Profile</h2>
              <p>Edit your account information</p>
          </div>
              </div>
            </div>
          </div>
      </div>
  </section>
  <!-- ***** Main Banner Area End ***** -->

  <!-- ***** Update Form Area Start ***** -->
  <section class="meetings-page" id="meetings">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="update-form">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" 
                           value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="sap_id" class="form-label">SAP ID</label>
                    <input type="text" class="form-control" id="sap_id" name="sap_id" 
                           value="<?php echo htmlspecialchars($user['sap_id']); ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department" required>
                        <option value="Computer Science" <?= $user['department'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                        <option value="Information Technology" <?= $user['department'] == 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                        <option value="Electronics and Telecommunication" <?= $user['department'] == 'Electronics and Telecommunication' ? 'selected' : '' ?>>Electronics and Telecommunication</option>
                        <option value="Mechanical" <?= $user['department'] == 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                        <option value="Civil" <?= $user['department'] == 'Civil' ? 'selected' : '' ?>>Civil</option>
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- ***** Update Form Area End ***** -->

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
</body>
</html>
