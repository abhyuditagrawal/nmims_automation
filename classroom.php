<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Handle logout if requested
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.html");
    exit();
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

    <title>Classroom Selection | NMIMS Smart College Automation</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-edu-meeting.css">
    <link rel="stylesheet" href="assets/css/owl.css">
    <link rel="stylesheet" href="assets/css/lightbox.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <style>
        .classroom-section {
            position: relative;
            z-index: 1;
        }

        .classroom-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .floor-section {
            margin-bottom: 30px;
        }

        .floor-title {
            color: #9c0202;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #9c0202;
        }

        .classroom-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }

        .classroom-btn {
            background-color: #9c0202;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 0;
            text-align: center;
            transition: all 0.3s;
            font-weight: 500;
        }

        .classroom-btn:hover {
            background-color: #e6911a;
            transform: translateY(-3px);
        }

        .computer-lab {
            background-color: #2a5a8a;
        }

        .computer-lab:hover {
            background-color: #3a7aba;
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
                      <li class="scroll-to-section"><a href="classroom.php" class="active">Classrooms</a></li>
                      <li><a href="index.html#about">About</a></li>
                      <li><a href="index.html#contact">Contact</a></li>
                      <li><a href="dashboard.php"><i class="fa fa-user"></i> My Profile</a></li> <!-- Added this line -->
                      <li><a href="classroom.php?logout=true" class="login-trigger">Logout</a></li>
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
              <h2>Classroom Selection</h2>
              <p>Please select your classroom to access device controls</p>
          </div>
              </div>
            </div>
          </div>
      </div>
  </section>
  <!-- ***** Main Banner Area End ***** -->

  <!-- ***** Classroom Selection Area Start ***** -->
  <section class="classroom-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="classroom-container">
            <div class="user-panel" style="background: rgba(255, 255, 255, 0.95); border-radius: 15px; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="color: #9c0202; margin-bottom: 10px;"><i class="fa fa-user"></i> User Information</h4>
                    <p style="margin-bottom: 5px;"><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                    <p style="margin-bottom: 5px;"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($_SESSION['department']); ?></p>
                </div>
                <div>
                    <a href="classroom.php?logout=true" class="btn btn-danger" style="background-color: #9c0202; border: none; padding: 10px 20px; font-size: 16px;">
                        <i class="fa fa-sign-out"></i> Logout
                    </a>
                </div>
            </div>

            <?php for ($floor = 1; $floor <= 8; $floor++): ?>
            <div class="floor-section">
                <h3 class="floor-title"><?php echo $floor; ?>st Floor</h3>
                <div class="classroom-grid">
                    <?php for ($room = 1; $room <= 8; $room++): ?>
                        <a href="devices.php?classroom=cr<?php echo $floor . sprintf('%02d', $room); ?>" class="btn classroom-btn">
                            CR<?php echo $floor . sprintf('%02d', $room); ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="devices.php?classroom=cl<?php echo $floor . '01'; ?>" class="btn classroom-btn computer-lab">
                        CL<?php echo $floor . '01'; ?>
                    </a>
                    <a href="devices.php?classroom=cl<?php echo $floor . '02'; ?>" class="btn classroom-btn computer-lab">
                        CL<?php echo $floor . '02'; ?>
                    </a>
                </div>
            </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- ***** Classroom Selection Area End ***** -->

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