<?php
session_start();
include("connect.php"); 

// Check if organization is logged in
if (!isset($_SESSION['org_id'])) {
    echo "<script>
            alert('Please login as an organization first!');
            window.location.href = 'org_login.php';
          </script>";
    exit();
}

$org_id = $_SESSION['org_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name    = trim($_POST["name"]);
    $email   = trim($_POST["email"]);
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Invalid email format!');
                window.location.href = 'contactUs.html';
              </script>";
        exit();
    }

    $query = "INSERT INTO contact_messages (org_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $org_id, $name, $email, $subject, $message);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Message sent successfully!');
                window.location.href = 'Dashboard.php';
              </script>";
    } else {
        echo "<script>
                alert('Failed to send message!');
                window.location.href = 'contactUs.html';
              </script>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<script>
            alert('Invalid request!');
            window.location.href = 'contactus.html';
          </script>";
}
?>