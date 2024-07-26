<?php
session_start();
require_once('connect/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $sql = "SELECT tu_registration_number FROM admin_dashboard_details WHERE submission_status = 'Submitted' AND payment_status = 'Completed'";
    $statement = $connect->query($sql);
    
    if ($statement) {
        $students = $statement->fetch_all(MYSQLI_ASSOC);

        if ($students) {
            $insertSql = "INSERT INTO notifications (tu_registration_number, notification_message) VALUES (?, ?)";
            $insertStatement = $connect->prepare($insertSql);

            if ($insertStatement) {
                foreach ($students as $student) {
                    $tuRegistrationNumber = $student['tu_registration_number'];
                    $notificationMessage = "Your migration certificate application has been successfully processed.";

                    $insertStatement->bind_param("ss", $tuRegistrationNumber, $notificationMessage);
                    $insertStatement->execute();
                }

                $_SESSION['success_message'] = "Notifications sent successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to prepare the notification statement.";
            }
        } else {
            $_SESSION['error_message'] = "No students with completed submission and payment status found.";
        }
    } else {
        $_SESSION['error_message'] = "Failed to execute the query.";
    }

    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>
