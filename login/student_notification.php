<?php
session_start(); // Start the session
require_once('connect/connection.php'); // Include the connection file

// Initialize variables
$student = null;
$notifications = [];
//$_SESSION['TU_registration_number'] = $TU_registration_number;
// Check if the registration number is stored in the session
if (isset($_SESSION['session_password'])) {
    $registration_number = $_SESSION['session_password'];

    // Fetch student details from the studentdetails table based on the provided registration number
    $sql = "SELECT * FROM studentdetails WHERE TU_registration_number = ? LIMIT 1";
    $statement = $connect->prepare($sql);
    $statement->bind_param("s", $registration_number);
    $statement->execute();
    $result = $statement->get_result();
    $student = $result->fetch_assoc();

    // Fetch notifications for the logged-in student from the notifications table
    $notificationSql = "SELECT * FROM notifications WHERE tu_registration_number = ?";
    $notificationStatement = $connect->prepare($notificationSql);
    $notificationStatement->bind_param("s", $registration_number);
    $notificationStatement->execute();
    $result = $notificationStatement->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Notifications</title>
    <link rel="stylesheet" href="../style/notification.css">
</head>
<body>
    <div id="sidebar">
        <nav>
            <ul>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">Logout</a></li>
            </ul>
        </nav>
    </div>
    <div class="dashboard-container">
        <div class="header">
            <h1>Migration Certificate Dashboard</h1>
        </div>
        <div class="content">
            <div class="notifications-section">
                <h2>Notifications</h2>
                <ul id="notificationsList">
                    <?php if (!empty($notifications)) { ?>
                        <?php foreach ($notifications as $notification) { ?>
                            <li><?php echo htmlspecialchars($notification['notification_message'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php } ?>
                    <?php } else { ?>
                        <li>No notifications found.</li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
