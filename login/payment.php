<?php
session_start();

// Include database connection script
include('connect/connection.php');


if (isset($_SESSION['TU_registration_number'])) {
    $TU_registration_number = $_SESSION['TU_registration_number'];

    // Prepare the SQL statement
    $sql = $connect->prepare("SELECT * FROM studentdetails WHERE TU_registration_number = ?");
    if ($sql) {
        // Bind the parameter and execute the query
        $sql->bind_param("s", $TU_registration_number);
        $sql->execute();
        $result = $sql->get_result();
        $data = $result->fetch_assoc();

        // Check if data is retrieved
        if ($data) {
            // Prepare PayPal payment form
            ?>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Proceed to Payment</title>
                <link rel="stylesheet" href="../style/payment.css">
            </head>
            <body>
                <div class="container">
                    <h2>Application successful! Proceed to payment</h2>
                    <form method="post" action="https://www.sandbox.paypal.com/cgi-bin/webscr">
                        <input type="hidden" name="business" value="sb-n47uev30512622@business.example.com">
                        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($data['payment_amount']); ?>">
                        <input type="hidden" name="currency_code" value="USD">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="return" value="http://localhost/MCMS/login/success.php">
                        <input type="hidden" name="cancel_return" value="http://localhost/MCMS/login/cancel.php">
                        <!-- Pass TU registration number to success.php -->
                        <input type="hidden" name="TU_registration_number" value="<?php echo htmlspecialchars($data['TU_registration_number']); ?>">
                        <button type="submit" class="btn btn-primary" name="submit">Proceed to payment</button>
                    </form>
                </div>
            </body>
            </html>
            <?php
        } else {
            echo "No student data found.";
        }
    } else {
        echo "Error preparing statement: " . $connect->error;
    }
} else {
    echo "TU registration number is not set in the session.";
}
?>
