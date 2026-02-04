<?php
session_start();
include("connect.php");

if (isset($_SESSION['mobile']) && isset($_SESSION['pending_employee'])) {
  
    $otp = rand(100000, 999999);
    $_SESSION['OTP'] = $otp;
    $mobile = $_SESSION['mobile'];

 
    $API = "your-api-key"; 
    $URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$mobile&OTP=$otp";

    $curl = curl_init($URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

?>
