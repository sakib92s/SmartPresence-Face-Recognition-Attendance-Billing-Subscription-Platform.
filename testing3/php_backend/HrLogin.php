<?php
session_start();
include("connect.php"); // Connects to your MySQL database

// Get form data
$org_email = $_POST['OrgEmail'];
$org_pass1 = $_POST['OrgPass1'];

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare SQL to prevent SQL injection (recommended)
$sql = "SELECT * FROM organization_register WHERE org_email = ? AND org_password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $org_email, $org_pass1);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows > 0) {
    $_SESSION['OrgEmail'] = $org_email;

    echo '<script>
        alert("Hr Login Successful!");
        window.location = "employeeForm.html";
    </script>';
} else {
    echo '<script>
        alert("Invalid Email or Password!");
        window.location = "HrLogin.html";
    </script>';
}

// Close DB connection
$stmt->close();
$conn->close();
?>