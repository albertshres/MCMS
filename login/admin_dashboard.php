<?php
require_once('connect/connection.php');
session_start();

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Fetch all student details from the studentdetails table
$studentSql = "SELECT firstname, lastname, email, TU_registration_number FROM studentdetails";
$studentResult = $connect->query($studentSql);

if (!$studentResult) {
    die("Query failed: " . $connect->error);
}

while ($student = $studentResult->fetch_assoc()) {
    $tuRegistrationNumber = $student['TU_registration_number'];
    
    // Check if the student already exists in the admin_dashboard_details table
    $checkSql = "SELECT COUNT(*) AS count FROM admin_dashboard_details WHERE tu_registration_number = ?";
    $checkStatement = $connect->prepare($checkSql);
    if (!$checkStatement) {
        die("Preparation failed: " . $connect->error);
    }
    $checkStatement->bind_param("s", $tuRegistrationNumber);
    $checkStatement->execute();
    $checkResult = $checkStatement->get_result()->fetch_assoc();
    
    if ($checkResult['count'] == 0) {
        $studentName = $student['firstname'] . ' ' . $student['lastname'];
        $submissionStatus = (!empty($student['email']) && !empty($student['TU_registration_number'])) ? "Submitted" : "Pending";
        $paymentStatus = isset($_SESSION['status']) ? $_SESSION['status'] : "Pending";
        
        // Insert new student details into the admin_dashboard_details table
        $insertSql = "INSERT INTO admin_dashboard_details (student_name, tu_registration_number, submission_status, payment_status) 
                      VALUES (?, ?, ?, ?)";
        $insertStatement = $connect->prepare($insertSql);
        if (!$insertStatement) {
            die("Preparation failed: " . $connect->error);
        }
        $insertStatement->bind_param("ssss", $studentName, $tuRegistrationNumber, $submissionStatus, $paymentStatus);
        $insertStatement->execute();
    }
}

// Fetch all existing students from the admin_dashboard_details table
$existingStudentSql = "SELECT student_name, tu_registration_number, submission_status, payment_status FROM admin_dashboard_details";
$existingStudentResult = $connect->query($existingStudentSql);

if (!$existingStudentResult) {
    die("Query failed: " . $connect->error);
}

$existingStudents = $existingStudentResult->fetch_all(MYSQLI_ASSOC);

// Close the database connection
$connect->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style/admin.css">
    <script>
        function displayAlert() {
            <?php if (isset($_SESSION['success_message'])) { ?>
                alert("<?php echo $_SESSION['success_message']; ?>");
                <?php unset($_SESSION['success_message']); ?>
            <?php } elseif (isset($_SESSION['error_message'])) { ?>
                alert("<?php echo $_SESSION['error_message']; ?>");
                <?php unset($_SESSION['error_message']); ?>
            <?php } ?>
        }
    </script>
</head>
<body onload="displayAlert()">
    <div id="sidebar">
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">Logout</a></li>
            </ul>
        </nav>
    </div>
    <div id="content">
        <div class="dashboard-container">
            <div class="header">
                <h1 class="text-center">Admin Dashboard</h1>
            </div>
            <div class="content">
                <table id="students" class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>TU Registration Number</th>
                            <th>Submission Status</th>
                            <th>Payment Status</th>
                            <th>Notification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existingStudents as $student) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['tu_registration_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['submission_status']); ?></td>
                                <td><?php echo htmlspecialchars($student['payment_status']); ?></td>
                                <td>
                                    <form method="POST" action="send_notification.php">
                                        <button type="submit" name="send_notification" class="btn btn-primary">Send Notification</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
