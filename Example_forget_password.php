<?php

include_once 'config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $sql = "SELECT * FROM Users WHERE Email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user){
        $token = bin2hex(random_bytes(32));
        $expiry_time = date("Y-m-d H:i:s", strtotime("+ 5 minutes"));

        $update_sql = "UPDATE Users SET ResetToken = :token,
                                       TokenExpiry = :expiry WHERE Email = :email";
        $update = $pdo->prepare($update_sql);
        $update->bindParam(':token', $token);
        $update->bindParam(':expiry', $expiry_time);
        $update->bindParam(':email', $email);
        $update->execute();

        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'smtp.example.com';                     // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'your_email@example.com';                     // SMTP username
            $mail->Password   = 'your_password';                               // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom('your_email@example.com', 'HAckER');
            $mail->addAddress($email);     // Add a recipient

            $resetLink = $base_url . "/reset_password.php?token=" . $token;

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'You have been hacked';
            $mail->Body    = "<p>Dear,User</p>
                              <p>To save your pc. Click the link below to proceed:</p>
                              <p><a href='$resetLink'>$resetLink</a></p>
                              <p>This link will expire in 5 minutes.</p>
                              <p>If you dont click the link, Well DONE!.</p>
                              <p>See You,<br>HAckER</p>";
            $mail->AltBody = "Dear User, To save your pc. Click the link below to proceed: $resetLink This link will expire in 5 minutes. If you dont click the link, Well DONE! See You, HAckER";

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }else{
        http_response_code(404);
        echo json_encode(["message" => "Email Not Found"]);
    }
}

?>