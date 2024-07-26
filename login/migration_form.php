<?php
session_start(); // Start session if not already started
include('connect/connection.php'); // Include database connection

// Initialize variables
$errors = [];
$firstname = "";
$lastname = "";
$email = "";
$current_address = "";
$TU_registration_number = "";
$college_name = "";
$college_roll_number = "";
$payment_amount = "";
$identity_verification = "";

// Check if session variables are set
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>alert('You need to login first.'); window.location.href='index.php';</script>";
    exit;
}

$email = trim($_SESSION['session_email']);
$password = trim($_SESSION['session_password']); // Assuming this is the registration_no for the query

// Use prepared statements for security
$stmt = $connect->prepare("SELECT * FROM registrationdetails WHERE email = ? AND registration_no = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($connect->error));
}

$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . htmlspecialchars($stmt->error));
}

$userDetails = $result->fetch_assoc();

// Check if user details are found
if (!$userDetails) {
    echo "<script>alert('No user details found for the provided email and registration number.'); window.location.href='index.php';</script>";
    exit;
}

// Populate form variables with user details
$firstname = $userDetails['firstname'];
$lastname = $userDetails['lastname'];
$college_roll_number = $userDetails['college_rollno'];
$college_name = $userDetails['college_name'];
$TU_registration_number = $userDetails['registration_no'];

$_SESSION['TU_registration_number'] = $TU_registration_number;

// Check if the form has been submitted
if (isset($_POST['submit'])) {
    // Retrieve data from the form and sanitize inputs
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $lastname = htmlspecialchars(trim($_POST['lastname']));
    $email = htmlspecialchars(trim($_POST['email']));
    $current_address = htmlspecialchars(trim($_POST['current_address']));
    $TU_registration_number = htmlspecialchars(trim($_POST['TU_registration_number']));
    $college_name = htmlspecialchars(trim($_POST['college_name']));
    $college_roll_number = htmlspecialchars(trim($_POST['college_roll_number']));
    $payment_amount = htmlspecialchars(trim($_POST['payment_amount']));
    $identity_verification = isset($_POST['identity_verification']) ? implode(', ', $_POST['identity_verification']) : '';

    // Validate input fields (you can add more validation as needed)
    if (empty($firstname)) {
        $errors['firstname'] = "First Name is required";
    }

    if (empty($lastname)) {
        $errors['lastname'] = "Last Name is required";
    }

    if (empty($email)) {
        $errors['email'] = "Email is required";
    }

    if (empty($current_address)) {
        $errors['current_address'] = "Address is required";
    }

    if (empty($TU_registration_number)) {
        $errors['TU_registration_number'] = "TU Registration Number is required";
    }

    if (empty($college_name)) {
        $errors['college_name'] = "College Name is required";
    }

    if (empty($college_roll_number)) {
        $errors['college_roll_number'] = "College Roll Number is required";
    }

    if (empty($payment_amount)) {
        $errors['payment_amount'] = "Payment Amount is required";
    }

    // File upload validation
    if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] != UPLOAD_ERR_OK) {
        $errors['file_upload'] = "File upload is required.";
    } else {
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $fileMimeType = $_FILES['file_upload']['type'];
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            $errors['file_upload'] = "Only PDF, JPEG, and PNG files are allowed.";
        }
    }


    if (empty($errors)) {

        $uploadDir = 'verification_datas/';
        $fileName = basename($_FILES['file_upload']['name']);
        $uploadFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadFilePath)) {
            // Insert data into the database
            $sql = "INSERT INTO studentdetails (firstname, lastname, email, current_address, TU_registration_number, college_name, college_roll_number, identity_verification, payment_amount, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $connect->prepare($sql);

            if ($stmt === false) {
                die('MySQLi prepare error: ' . htmlspecialchars($connect->error));
            }

            // Bind parameters
            $stmt->bind_param('ssssssisss', $firstname, $lastname, $email, $current_address, $TU_registration_number, $college_name, $college_roll_number, $identity_verification, $payment_amount, $uploadFilePath);

            $result = $stmt->execute();

            if ($result) {
                header("Location: payment.php");
                exit();
            } else {
                die("Execute failed: " . htmlspecialchars($stmt->error));
            }
            $stmt->close();
        } else {
            $errors['file_upload'] = "Error uploading the file.";
        }
    }
}

// Close connection
$connect->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Application Form</title>
    <link rel="stylesheet" href="../style/migration_form.css">
    <style>
        
body {
    font-family: Raleway, sans-serif;
    background-color: #f0f0f0;
    padding: 20px;
}

.container {
    max-width: 600px;
    background-color: #fff;
    padding: 30px;
    margin: 0 auto;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.box {
    margin-bottom: 20px;
}

.form-control {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-label {
    font-weight: bold;
}

.btn-primary {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.errors {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.errors p {
    margin: 0;
}
</style>
   
</head>

<body>
    <div class="container">
        <div class="box">
            <h2>Migration Certificate Application Form</h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if(!empty($errors)) { ?>
                    <div class="errors">
                        <?php foreach ($errors as $error) {
                            echo '<p>' . $error . '</p>';
                        } ?>
                    </div>
                <?php } ?>

                <div class="input-group mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    <?php if(isset($errors['firstname'])) { ?>
                        <p><?php echo $errors['firstname']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    <?php if(isset($errors['lastname'])) { ?>
                        <p><?php echo $errors['lastname']; ?></p>
                    <?php } ?>
                </div>
                
                <div class="input-group mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <?php if(isset($errors['email'])) { ?>
                        <p><?php echo $errors['email']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label">Current Address</label>
                    <input type="text" class="form-control" id="current_address" name="current_address" required>
                    <?php if(isset($errors['current_address'])) { ?>
                        <p><?php echo $errors['current_address']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label" for="TU_registration_number">TU Registration Number</label>
                    <input type="text" class="form-control" id="TU_registration_number" name="TU_registration_number" value="<?php echo htmlspecialchars($TU_registration_number); ?>" required>
                    <?php if(isset($errors['TU_registration_number'])) { ?>
                        <p><?php echo $errors['TU_registration_number']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label" for="college_name">College Name</label>
                    <input type="text" class="form-control" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college_name); ?>" required>
                    <?php if(isset($errors['college_name'])) { ?>
                        <p><?php echo $errors['college_name']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label" for="college_roll_number">College Roll Number</label>
                    <input type="number" class="form-control" id="college_roll_number" name="college_roll_number" value="<?php echo htmlspecialchars($college_roll_number); ?>" required>
                    <?php if(isset($errors['college_roll_number'])) { ?>
                        <p><?php echo $errors['college_roll_number']; ?></p>
                    <?php } ?>
                </div>

                <div class="input-group mb-3">
                    <label class="form-label" for="identity_verification">Select Verification Method</label>
                    <table>
                        <tr>
                            <td><input type="checkbox" id="passportCheckbox" name="identity_verification[]" value="passport"></td>
                            <td><label class="form-check-label" for="passportCheckbox">Passport</label></td>
                        </tr>
                        <tr>
                            <td><input class="form-check-input" type="checkbox" id="drivingLicenseCheckbox" name="identity_verification[]" value="driving_license"></td>
                            <td><label class="form-check-label" for="drivingLicenseCheckbox">Driving License</label></td>
                        </tr>
                    </table>
                </div>
<br>
                <div class="input-group input-group-outline mb-3">
                    <label class="form-label" for="file_upload">Upload Document</label>
                    <input type="file" class="form-control" id="file_upload" name="file_upload" required>
                    <?php if(isset($errors['file_upload'])) { ?>
                        <p><?php echo $errors['file_upload']; ?></p>
                    <?php } ?>
                </div>
                    </br>
                <div class="input-group mb-3">
                    <label class="form-label" for="payment_amount">Payment Amount</label>
                    <input type="number" class="form-control" id="payment_amount" name="payment_amount" required>
                    <?php if(isset($errors['payment_amount'])) { ?>
                        <p><?php echo $errors['payment_amount']; ?></p>
                    <?php } ?>
                </div>

                <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
