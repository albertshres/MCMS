<?php
// Include database connection script
require_once('connect/connection.php');

session_start();

// Initialize $data
$data = null;

// Fetch TU registration number from student_details table
if (isset($_SESSION['session_password'])) {
    $registration_number = $_SESSION['session_password'];

    // Fetch student details from the studentdetails table based on the provided registration number
    $sql = "SELECT TU_registration_number, payment_amount FROM studentdetails WHERE TU_registration_number = ? LIMIT 1";
    $statement = $connect->prepare($sql);
    if ($statement) {
        $statement->bind_param('s', $registration_number);
        $statement->execute();
        $result = $statement->get_result();
        $data = $result->fetch_assoc();
        $statement->close();

    } else {
        echo "Error preparing statement: " . $connect->error;
    }
} else {
    echo "Session 'session_password' is not set.";
}

// Check if TU registration number is retrieved
if ($data && isset($data['TU_registration_number'])) {
    // Assign TU registration number to session
    $_SESSION['session_password'] = $data['TU_registration_number'];
} else {
    // Handle the case where TU registration number is not found
    $_SESSION['session_password'] = ""; // Assign a default value or handle as needed
    echo "No student data found or TU registration number is missing.";
}

if (!empty($_GET)) {
    $required_params = array('payer_id', 'payer_email', 'first_name', 'last_name', 'amt', 'cc', 'st');
    $params_set = true;
    foreach ($required_params as $param) {
        if (!isset($_GET[$param])) {
            $params_set = false;
            break;
        }
    }
    if ($params_set) {
        $_SESSION['payer_id'] = $_GET['payer_id'];
        $_SESSION['payer_email'] = $_GET['payer_email'];
        $_SESSION['payer_name'] = $_GET['first_name'] . ' ' . $_GET['last_name'];
        $_SESSION['amount'] = $_GET['amt'];
        $_SESSION['currency'] = $_GET['cc'];
        $_SESSION['status'] = $_GET['st'];

        date_default_timezone_set('Asia/Kathmandu');

        $sql = "INSERT INTO payments (payment_id, payer_id, payer_name, payer_email, item_id, item_name, currency, amount, status, TU_registration_number, created_at)
                VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?)";

        $statement = $connect->prepare($sql);

        if ($statement) {
            $txn_id = $_SESSION['txn_id'] ?? null;
            $product = $_SESSION['product'] ?? '';
            $created_at = date('Y-m-d H:i:s');

            $statement->bind_param('ssssssssss', $txn_id, $_SESSION['payer_id'], $_SESSION['payer_name'], $_SESSION['payer_email'], $product, $_SESSION['currency'], $_SESSION['amount'], $_SESSION['status'], $_SESSION['session_password'], $created_at);

            if ($statement->execute()) {
                // Set the application status in the session
                $_SESSION['status'] = $_GET['st'];
                header("Location: success.php?status={$_SESSION['status']}&TU_registration_number={$_SESSION['session_password']}");
                exit; // Terminate script after redirection
            }
            $statement->close();
        }
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Success</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../style/success.css">
</head>
<body>
<div class="container mt-3">
    <div class="alert alert-success">
        <strong>Success!</strong> Payment has been successful
    </div>

    <table class="table table-bordered">
        <tbody>
        <tr>
            <td>Transaction Id</td>
            <td><?php echo isset($_SESSION['payer_id']) ? htmlspecialchars($_SESSION['payer_id']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <td>Full Name</td>
            <td><?php echo isset($_SESSION['payer_name']) ? htmlspecialchars($_SESSION['payer_name']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <td>Amount</td>
            <td><?php echo isset($_SESSION['amount']) ? htmlspecialchars($_SESSION['amount']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <td>Payment Status</td>
            <td><?php echo isset($_SESSION['status']) ? htmlspecialchars($_SESSION['status']) : 'N/A'; ?></td>
        </tr>
        <tr>
            <td>TU registration number</td>
            <td><?php echo isset($_SESSION['session_password']) ? htmlspecialchars($_SESSION['session_password']) : 'N/A'; ?></td>
        </tr>
        </tbody>
    </table>

    <div class="card-footer text-center pt-0 px-lg-2 px-1">
        <p class="mb-2 text-sm mx-auto">
            <a href="student_dashboard.php" class="text-primary text-gradient font-weight-bold">Dashboard</a>
        </p>
    </div>
</div>
</body>
</html>
