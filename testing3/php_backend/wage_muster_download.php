<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['org_email'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filepath = 'generated_wage_musters/' . $filename;
    
    if (file_exists($filepath)) {
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Read file
        readfile($filepath);
        exit;
    } else {
        $_SESSION['error_message'] = "File not found!";
        header("Location: wage_muster.php");
        exit();
    }
} else {
    header("Location: wage_muster.php");
    exit();
}
?>