<?php
session_start();
require_once 'connect.php';
require_once 'wage_muster_utils.php';

// Check if user is logged in
if (!isset($_SESSION['org_email'])) {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['org_id'];
$org_name = $_SESSION['org_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $org_name_input = $_POST['org_name'];
        $tender_number = $_POST['tender_number'] ?? '';
        $month = $_POST['month'];
        $contractor_name = $_POST['contractor_name'] ?? '';
        $work_location = $_POST['work_location'] ?? '';
        
        // Handle file upload or selection
        $attendance_file = '';
        
        if (isset($_POST['attendance_file']) && $_POST['attendance_file'] !== 'upload_new') {
            // Use existing file
            $attendance_file = 'attendance_reports/' . $_POST['attendance_file'];
            
            if (!file_exists($attendance_file)) {
                throw new Exception("Selected attendance file not found!");
            }
        } elseif (isset($_FILES['attendance_file_upload']) && $_FILES['attendance_file_upload']['error'] === UPLOAD_ERR_OK) {
            // Handle new file upload
            $upload_dir = 'temp_uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = uniqid() . '_' . basename($_FILES['attendance_file_upload']['name']);
            $upload_path = $upload_dir . $file_name;
            
            // Check file type
            $file_type = strtolower(pathinfo($upload_path, PATHINFO_EXTENSION));
            if (!in_array($file_type, ['xlsx', 'xls'])) {
                throw new Exception("Only Excel files (.xlsx, .xls) are allowed!");
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['attendance_file_upload']['tmp_name'], $upload_path)) {
                $attendance_file = $upload_path;
            } else {
                throw new Exception("Failed to upload file!");
            }
        } else {
            throw new Exception("Please select or upload an attendance file!");
        }
        
        // Generate wage muster
        $result = generateWageMuster($attendance_file, $org_name_input, $tender_number, 
                                    $month, $contractor_name, $work_location);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Wage Muster generated successfully!";
            $_SESSION['generated_file'] = $result['filename'];
            $_SESSION['wage_stats'] = $result['stats'];
            
            // Clean up uploaded temp file if used
            if (strpos($attendance_file, 'temp_uploads/') === 0) {
                unlink($attendance_file);
            }
            
            header("Location: wage_muster.php");
            exit();
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: wage_muster.php");
        exit();
    }
} else {
    header("Location: wage_muster.php");
    exit();
}