<?php
session_start();
include("connect.php");

// Check if organization is logged in
if (!isset($_SESSION['org_id'])) {
    echo "<script>alert('Please login as an organization first!'); window.location.href='org_login.php';</script>";
    exit();
}

$org_id = $_SESSION['org_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empname    = $_POST['empname'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];
    $repassword = $_POST['repassword'];
    $dob        = $_POST['dob'];
    $mobile     = $_POST['mobile'];
    $category   = $_POST['category'];

    // Check for duplicate email or mobile in employees table for this organization
    $check_email = mysqli_query($conn, "SELECT * FROM employees WHERE email = '$email' AND org_id = '$org_id'");
    $check_mobile = mysqli_query($conn, "SELECT * FROM employees WHERE mobile = '$mobile' AND org_id = '$org_id'");

    // Also check in employees_master for global uniqueness
    $check_master_email = mysqli_query($conn, "SELECT * FROM employees_master WHERE email = '$email'");
    $check_master_mobile = mysqli_query($conn, "SELECT * FROM employees_master WHERE mobile = '$mobile'");

    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>alert('Email already registered in your organization!'); window.history.back();</script>";
    } elseif (mysqli_num_rows($check_mobile) > 0) {
        echo "<script>alert('Mobile number already registered in your organization!'); window.history.back();</script>";
    } elseif (mysqli_num_rows($check_master_email) > 0) {
        echo "<script>alert('Email already registered in another organization!'); window.history.back();</script>";
    } elseif (mysqli_num_rows($check_master_mobile) > 0) {
        echo "<script>alert('Mobile number already registered in another organization!'); window.history.back();</script>";
    } elseif ($password !== $repassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
    } else {
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;

        if ($age < 18) {
            echo "<script>alert('Employee must be at least 18 years old!'); window.history.back();</script>";
            exit();
        }

        // File Upload to temporary directory
        $temp_dir = "temp_uploads/";
        if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
        
        $aadhar_name = $_FILES['aadhar']['name'];
        $aadhar_tmp = $_FILES['aadhar']['tmp_name'];
        $photo_name = $_FILES['photo']['name'];
        $photo_tmp = $_FILES['photo']['tmp_name'];

        // Generate unique filenames to avoid conflicts
        $aadhar_unique_name = uniqid() . '_' . $aadhar_name;
        $photo_unique_name = uniqid() . '_' . $photo_name;
        
        $aadhar_temp_target = $temp_dir . $aadhar_unique_name;
        $photo_temp_target = $temp_dir . $photo_unique_name;

        if (move_uploaded_file($aadhar_tmp, $aadhar_temp_target) && 
            move_uploaded_file($photo_tmp, $photo_temp_target)) {
            
            // Store data in session instead of database
            $_SESSION['pending_employee'] = [
                'org_id' => $org_id,
                'empname' => $empname,
                'email' => $email,
                'password' => $password,
                'dob' => $dob,
                'mobile' => $mobile,
                'category' => $category,
                'aadhar_temp_path' => $aadhar_temp_target,
                'photo_temp_path' => $photo_temp_target,
                'aadhar_name' => $aadhar_name,
                'photo_name' => $photo_name
            ];

            // Generate and send OTP
            $otp = rand(100000, 999999);
            $_SESSION['OTP'] = $otp;
            $_SESSION['mobile'] = $mobile;

            $API = "6fd6b030c6afec018415662d0db43f9d"; 
            $URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$mobile&OTP=$otp";

            $curl = curl_init($URL);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);

            echo "<script>alert('OTP sent to your mobile number.'); window.location.href='Employee-otp.php';</script>";
            exit();
        } else {
            echo "<script>alert('File upload failed.'); window.history.back();</script>";
        }
    }
}
?>