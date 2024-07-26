<?php
session_start();
include('connect/connection.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST["login"])){
    $email = mysqli_real_escape_string($connect, trim($_POST['email']));
    $password = trim($_POST['password']);

    // Check for admin login
    if ($email === 'admin@gmail.com' && $password === 'admin') {
        $_SESSION['loggedin'] = true;
        $_SESSION['session_email'] = $email;
        echo "<script>alert('Admin login successful');</script>";
        header("Location: admin_dashboard.php");
        exit;
    }

    $sql = mysqli_query($connect, "SELECT * FROM login WHERE email = '$email'");
    $count = mysqli_num_rows($sql);

    if($count > 0){
        $fetch = mysqli_fetch_assoc($sql);
        $hashpassword = $fetch["password"];

        if($fetch["status"] == 0){
            // Generate a new OTP and send it to the user
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['session_email'] = $email;

            require 'vendor/autoload.php';
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
             
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('samridhirajkarnikar22@gmail.com', 'OTP Verification');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Email Verification Code";
                $mail->Body = "<p>Dear user,</p><h3>Your verification OTP code is $otp</h3><br><br><p>With regards,</p><b>Administrator</b>";

                $mail->send();
                echo "<script>
                    alert('Please verify your email account. OTP sent to $email');
                    window.location.replace('verification.php');
                </script>";
            } catch (Exception $e) {
                echo "<script>alert('Failed to send OTP. Mailer Error: {$mail->ErrorInfo}');</script>";
            }
        } else if(password_verify($password, $hashpassword)){
            $_SESSION['loggedin'] = true;
            $_SESSION['session_email'] = $email;
            $_SESSION['session_password'] = $password;
            echo "<script>alert('Login successfully');</script>";
            header("Location: student_dashboard.php");
            exit;
        } else {
            echo "<script>alert('Email or password invalid, please try again.');</script>";
        }
    } else {
        echo "<script>alert('Email not found, please register first.');</script>";
    }
}
?>




<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="../style/style.css">

    <link rel="icon" href="Favicon.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">

    <title>Login Form</title>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light navbar-laravel">
    <div class="container">
        <a class="navbar-brand" href="#">Login Form</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php" style="font-weight:bold; color:black; text-decoration:underline">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Register</a>
                </li>
            </ul>

        </div>
    </div>
</nav>

<main class="login-form">
    <div class="cotainer">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Login</div>
                    <div class="card-body">
                        <form action="#" method="POST" name="login">
                            <div class="form-group row">
                                <label for="email_address" class="col-md-4 col-form-label text-md-right">E-Mail Address</label>
                                <div class="col-md-6">
                                    <input type="text" id="email_address" class="form-control" name="email" required autofocus>
                                </div>
                            </div>
<!--                        <div class="form-group row">
    <label for="registration_number" class="col-md-4 col-form-label text-md-right">Registration Number</label>
    <div class="col-md-6">
        <input type="text" id="registration_number" class="form-control" name="registration_number" required>
    </div>
</div>-->

                            <div class="form-group row">
                                <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>
                                <div class="col-md-6">
                                    <input type="password" id="password" class="form-control" name="password" required>
                                   <br> <i class="bi bi-eye-slash" id="togglePassword"></i></br>
                                </div>
                            </div>

        

                            <div class="form-group row">
                                <div class="col-md-6 offset-md-4">
                                    <div class="checkbox">
                                        <label>
                         
                                            <input type="checkbox" name="remember"> Remember Me 
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 offset-md-4">
                                <input type="submit" value="Login" name="login">
                                <a href="recover_psw.php" class="btn btn-link">
                                    Forgot Your Password?
                                </a>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

</main>
</body>
</html>
<script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    toggle.addEventListener('click', function(){
        if(password.type === "password"){
            password.type = 'text';
        }else{
            password.type = 'password';
        }
        this.classList.toggle('bi-eye');
    });
</script>
