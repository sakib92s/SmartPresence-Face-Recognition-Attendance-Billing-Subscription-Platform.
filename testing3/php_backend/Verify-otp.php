<?php
session_start();
include("connect.php");
if (!isset($_SESSION['reg_data']) || !isset($_SESSION['OTP'])) {
    echo "<script>alert('Invalid request. Please start registration again.'); window.location.href='register.php';</script>";
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . 
                   $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
if (hash_equals((string)$_SESSION['OTP'], (string)$entered_otp)) {
        $org_name = $_SESSION['reg_data']['org_name'];
        $org_email = $_SESSION['reg_data']['org_email'];
        $org_pass1 = $_SESSION['reg_data']['org_pass1'];
        $mobile = $_SESSION['reg_data']['mobile'];
        $file_name = $_SESSION['reg_data']['certificate_filename'];
        $temp_file_path = $_SESSION['reg_data']['temp_file_path'];
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            echo "<script>alert('Invalid file type. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG'); window.location.href='register.php';</script>";
            exit();
        }
        $upload_folder = "uploads/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0755, true);
        }   
        $target_path = $upload_folder . basename($file_name);
        if (file_exists($target_path)) {
            $file_name = time() . '_' . $file_name;
            $target_path = $upload_folder . $file_name;
        }
        if (rename($temp_file_path, $target_path)) {
            // $hashed_password = password_hash($org_pass1, PASSWORD_DEFAULT);      
            $stmt = $conn->prepare("INSERT INTO organization_register 
                (org_name, org_email, org_password, certificate_filename, mobile_number, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())");         
            $stmt->bind_param("sssss", $org_name, $org_email, $org_pass1, $file_name, $mobile);          
            if ($stmt->execute()) {
                unset($_SESSION['reg_data']);
                unset($_SESSION['OTP']);
                unset($_SESSION['mobile']);
                echo "<script>alert('Registration successful! You can now login.'); window.location.href='login.html';</script>";
                exit();
            } else {
                error_log("Database insertion failed: " . $stmt->error);
                echo "<script>alert('Registration failed. Please try again.'); window.location.href='register.php';</script>";
            }            
            $stmt->close();
        } else {
            echo "<script>alert('File upload failed.'); window.location.href='register.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid OTP. Please try again.'); window.history.back();</script>";
    }
}
?>