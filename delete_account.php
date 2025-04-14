
<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Handle account deletion confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Delete user from database
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Destroy session and redirect to login
        session_destroy();
        header("Location: login.html?account_deleted=true");
        exit();
    } else {
        // User canceled deletion
        header("Location: dashboard.php");
        exit();
    }
}

// Fetch user data for confirmation display
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="NMIMS Smart College Automation System">
    <meta name="author" content="NMIMS College">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">

    <title>Delete Account | NMIMS Smart College Automation</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-edu-meeting.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/lightbox.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <style>
        .delete-confirmation {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 30px;
            margin: 50px auto;
            max-width: 600px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .user-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
              <h2>Account Deletion</h2>
              <p>Confirm account deletion</p>
          </div>
              </div>
            </div>
          </div>
      </div>
  </section>
  <!-- ***** Main Banner Area End ***** -->

  <!-- ***** Delete Confirmation Area Start ***** -->
  <section class="meetings-page" id="meetings">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="delete-confirmation">
            <h3 class="text-center mb-4"><i class="fa fa-exclamation-triangle"></i> Confirm Account Deletion</h3>
            
            <div class="alert alert-danger">
                <strong>Warning!</strong> This action cannot be undone. All your data will be permanently deleted.
            </div>
            
            <div class="user-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>SAP ID:</strong> <?php echo htmlspecialchars($user['sap_id']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
            </div>
            
            <form method="POST">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                    <label class="form-check-label" for="confirm">
                        I understand that this action is irreversible and all my data will be permanently deleted
                    </label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                        <i class="fa fa-trash-alt"></i> Permanently Delete My Account
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
  <!-- ***** Delete Confirmation Area End ***** -->

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
