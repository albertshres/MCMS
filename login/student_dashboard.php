<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>alert('You need to login first.');</script>";
    echo "<meta http-equiv='refresh' content='2; url=index.php'>";
    exit;
}

// Include database connection
include('connect/connection.php');

// Check if session variables are set
if (!isset($_SESSION['session_email']) || !isset($_SESSION['session_password'])) {
    echo "<script>alert('Session variables are not set.');</script>";
    echo "<meta http-equiv='refresh' content='2; url=index.php'>";
    exit;
}

$email = trim($_SESSION['session_email']);
$password = trim($_SESSION['session_password']); // Assuming this is the registration_no for the query

// Use prepared statements for security
$stmt = $connect->prepare("SELECT * FROM registrationdetails WHERE email = ? AND registration_no = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

$userDetails = $result->fetch_assoc();

if (!$userDetails) {
    echo "<script>alert('No user details found.');</script>";
    exit;
}

// Prepare and execute SQL queries
try {
    // Login Status
    $loginStatusSql = "SELECT status FROM login WHERE email = ?";
    $loginStatusStmt = $connect->prepare($loginStatusSql);
    $loginStatusStmt->bind_param("s", $email);
    $loginStatusStmt->execute();
    $loginStatusResult = $loginStatusStmt->get_result();
    $loginStatusRow = $loginStatusResult->fetch_assoc();
    $loginStatus = $loginStatusRow['status'] ?? null;

    // Payment Status
    $paymentStatusSql = "SELECT status FROM payments WHERE TU_registration_number = ?";
    $paymentStatusStmt = $connect->prepare($paymentStatusSql);
    $paymentStatusStmt->bind_param("s", $password);
    $paymentStatusStmt->execute();
    $paymentStatusResult = $paymentStatusStmt->get_result();
    $paymentStatusRow = $paymentStatusResult->fetch_assoc();
    $paymentStatus = $paymentStatusRow['status'] ?? null;

    // Notification Count
    $notificationSql = "SELECT COUNT(*) AS notification_count FROM notifications WHERE tu_registration_number = ?";
    $notificationStatement = $connect->prepare($notificationSql);
    $notificationStatement->bind_param("s", $password);
    $notificationStatement->execute();
    $notificationResult = $notificationStatement->get_result();
    $notificationCountRow = $notificationResult->fetch_assoc();
    $notificationCount = (int)($notificationCountRow['notification_count'] ?? 0);

    $applicationStatus = ""; // Default status

    if ($loginStatus === '1') {
        $applicationStatus = "Initiated";
    }

    if ($loginStatus === '1' && $paymentStatus === "Completed") {
        $applicationStatus = "Pending";
    }

    if ($loginStatus === '1' && $paymentStatus === "Completed" && $notificationCount > 0) {
        $applicationStatus = "Verified";
    }

 //   echo "Final Application Status: " . htmlspecialchars($applicationStatus);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}


$profileImageSql = "SELECT file_path FROM user_profile_photos WHERE registration_no = ?";
$profileImageStmt = $connect->prepare($profileImageSql);
$profileImageStmt->bind_param("s", $password);
$profileImageStmt->execute();
$profileImageResult = $profileImageStmt->get_result();
$profileImageRow = $profileImageResult->fetch_assoc();

$profileImagePath = 'uploads/profile.png';
if ($profileImageRow && !empty($profileImageRow['file_path'])) {
    $profileImagePath = $profileImageRow['file_path'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . uniqid() . '_' . basename($_FILES["profile_image"]["name"]);

    if (!is_writable($target_dir)) {
        echo "<script>alert('Upload directory is not writable.');</script>";
        exit;
    }

    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check === false) {
        echo "<script>alert('File is not an image.');</script>";
        exit;
    }

    if ($_FILES["profile_image"]["size"] > 10 * 1024 * 1024) {
        echo "<script>alert('File is too large.');</script>";
        exit;
    }

    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Only JPG, JPEG, PNG, GIF files are allowed.');</script>";
        exit;
    }

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $profileImagePath = $target_file;

        $insert_stmt = $connect->prepare("INSERT INTO user_profile_photos (registration_no, file_name, file_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE file_name = VALUES(file_name), file_path = VALUES(file_path)");
        $file_name = basename($profileImagePath);
        $insert_stmt->bind_param("sss", $password, $file_name, $profileImagePath);
        if ($insert_stmt->execute()) {
            echo "<script>alert('Profile image has been set.');</script>";
        } else {
            echo "<script>alert('Error uploading file.');</script>";
        }
    } else {
        echo "<script>alert('Error uploading your file.');</script>";
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../style/student.css">
    <link rel="icon" href="Favicon.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <title>Student Profile</title>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 sidebar">
            <h3>Dashboard</h3>
            <div class="profile_img">
                <img src="<?php echo htmlspecialchars($profileImagePath); ?>" alt="Profile Image">
            </div>
            <div class="card-header bg-transparent border-0">
                <h4><?php echo htmlspecialchars($userDetails['firstname'] . " " . $userDetails['lastname']); ?></h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="student_dashboard.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="migration_form.php">Migration Certificate Form</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_notification.php">Messages<span class="badge-notification"><?php echo htmlspecialchars($notificationCount); ?></span></a>
                </li>
            </ul>
        </div>

        <div class="col-lg-9">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container">
                    <a class="navbar-brand" href="#">Student Profile</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ml-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container mt-4">
                <div class="row match-height">
                    <div class="col-lg-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent border-0">
                                <h3>General Information</h3>
                            </div>
                            <div class="card-body pt-0">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">First Name</th>
                                        <td width="2%">:</td>
                                        <td><?php echo htmlspecialchars($userDetails['firstname']); ?></td>
                                    </tr>
                                    <tr>
                                        <th width="30%">Last Name</th>
                                        <td width="2%">:</td>
                                        <td><?php echo htmlspecialchars($userDetails['lastname']); ?></td>
                                    </tr>
                                    <tr>
                                        <th width="30%">College Roll Number</th>
                                        <td width="2%">:</td>
                                        <td><?php echo htmlspecialchars($userDetails['college_rollno']); ?></td>
                                    </tr>
                                    <tr>
                                        <th width="30%">Email</th>
                                        <td width="2%">:</td>
                                        <td><?php echo htmlspecialchars($userDetails['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th width="30%">College Name</th>
                                        <td width="2%">:</td>
                                        <td><?php echo htmlspecialchars($userDetails['college_name']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
               <!--
                <div class="row card-container mt-4">
                    <div class="card shadow-sm application-status-card">
                        <div class="card-header bg-transparent border-0">
                            <h2>Application Status</h2>
                        </div>
                        <div class="card-body pt-0">
                            <p>Status: <span id="status"></?php echo htmlspecialchars($applicationStatus); ?></span></p>
                        </div>
                    </div>-->

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card shadow-sm notification-card">
                                 <div class="card-header bg-transparent border-0">
                                     <h2>Notification</h2>
                                 </div>
                                 <div class="card-body pt-0">
                                     <a class="nav-link" href="student_notification.php">View Notifications<span class="badge-notification"><?php echo htmlspecialchars($notificationCount); ?></span></a>
                                 </div>
                        </div>
                    </div>
                </div>
                    

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent border-0">
                                <h3 class="mb-0">Upload Profile Image</h3>
                            </div>
                            <div class="card-body pt-0">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="profile_image">Choose Image:</label>
                                        <input type="file" class="form-control-file" id="profile_image" name="profile_image" accept="image/*">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
</body>
</html>
