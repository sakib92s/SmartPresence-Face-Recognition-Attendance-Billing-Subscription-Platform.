<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store form data in session
    $_SESSION['reg_data'] = [
        'org_name' => $_POST['OrgName'],
        'org_email' => $_POST['OrgEmail'],
        'org_pass1' => $_POST['OrgPass1'],
        'mobile' => $_POST['MobNo']
    ];

    $org_name = $_POST['OrgName'];
    $org_email = $_POST['OrgEmail'];
    $org_pass1 = $_POST['OrgPass1'];
    $org_pass2 = $_POST['OrgPass2'];
    $mobile = $_POST['MobNo'];

    $file_name = $_FILES['myfile']['name'];
    $tmp_name = $_FILES['myfile']['tmp_name'];
    $error_code = $_FILES['myfile']['error'];

    if ($error_code !== 0) {
        echo "<script>alert('File Upload Error Code: $error_code'); window.history.back();</script>";
    } else {
        $upload_folder = "uploads/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);
        }

        $check_email = mysqli_query($conn, "SELECT * FROM organization_register WHERE org_email = '$org_email'");
        $check_mobile = mysqli_query($conn, "SELECT * FROM organization_register WHERE mobile_number = '$mobile'");

        if (mysqli_num_rows($check_email) > 0) {
            echo "<script>alert('Email already registered!'); window.history.back();</script>";
        } elseif (mysqli_num_rows($check_mobile) > 0) {
            echo "<script>alert('Mobile number already registered!'); window.history.back();</script>";
        } elseif ($org_pass1 !== $org_pass2) {
            echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        } else {
            // Store file in temporary location
            $temp_upload_folder = "temp_uploads/";
            if (!is_dir($temp_upload_folder)) {
                mkdir($temp_upload_folder, 0777, true);
            }
            
            $temp_target_path = $temp_upload_folder . basename($file_name);
            if (move_uploaded_file($tmp_name, $temp_target_path)) {
                // Store file info in session instead of saving to DB
                $_SESSION['reg_data']['certificate_filename'] = $file_name;
                $_SESSION['reg_data']['temp_file_path'] = $temp_target_path;
                
                // Generate and send OTP
                $otp = rand(100000, 999999);
                $_SESSION['OTP'] = $otp;
                $_SESSION['MobNo'] = $mobile;
                $_SESSION['reg_data']['org_name'] = $org_name;
                $_SESSION['reg_data']['org_email'] = $org_email;
                $_SESSION['reg_data']['org_pass1'] = $org_pass1;
                $_SESSION['reg_data']['mobile'] = $mobile;

                $API = "6fd6b030c6afec018415662d0db43f9d"; 
                $URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$mobile&OTP=$otp";

                $curl = curl_init($URL);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);

                echo "<script>alert('OTP sent successfully to your mobile'); window.location.href='otp.php';</script>";
                exit();
            } else {
                echo "<script>alert('File upload failed.'); window.history.back();</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Registration | Face Recognition Presence System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #1abc9c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary), #1a2530);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* Background Animation Elements */
        .background-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 15s infinite ease-in-out;
        }
        
        .square {
            position: absolute;
            background: rgba(52, 152, 219, 0.05);
            animation: rotate 20s infinite linear;
        }
        
        .triangle {
            position: absolute;
            width: 0;
            height: 0;
            border-style: solid;
            border-color: transparent transparent rgba(26, 188, 156, 0.05) transparent;
            animation: float 18s infinite ease-in-out reverse;
        }
        
        /* Registration Container */
        .register-container {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .register-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .register-header p {
            color: #bdc3c7;
            font-size: 1rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            font-size: 16px;
            color: var(--light);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.2);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #bdc3c7;
            cursor: pointer;
            font-size: 18px;
        }
        
        .toggle-password:hover {
            color: var(--accent);
        }
        
        .file-info {
            font-size: 0.85rem;
            color: #bdc3c7;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .btn-register {
            background: linear-gradient(to right, var(--accent), var(--secondary));
            color: white;
            border: none;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            letter-spacing: 0.5px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .btn-register::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-register:hover::after {
            left: 100%;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #bdc3c7;
        }
        
        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            position: relative;
        }
        
        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s;
        }
        
        .login-link a:hover {
            color: var(--secondary);
        }
        
        .login-link a:hover::after {
            width: 100%;
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0);
            }
            25% {
                transform: translate(10px, 15px);
            }
            50% {
                transform: translate(-5px, 20px);
            }
            75% {
                transform: translate(15px, -10px);
            }
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Footer */
        footer {
            text-align: center;
            padding: 2rem 0 1rem;
            color: #95a5a6;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .register-container {
                padding: 1.5rem;
            }
            
            .register-header h2 {
                font-size: 1.8rem;
            }
            
            .form-control {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Elements -->
    <div class="background-elements">
        <div class="circle" style="width: 300px; height: 300px; top: 10%; left: 5%;"></div>
        <div class="circle" style="width: 150px; height: 150px; top: 70%; left: 80%;"></div>
        <div class="square" style="width: 200px; height: 200px; top: 20%; left: 85%;"></div>
        <div class="square" style="width: 120px; height: 120px; top: 65%; left: 10%;"></div>
        <div class="triangle" style="border-width: 0 100px 170px 100px; top: 40%; left: 25%;"></div>
        <div class="triangle" style="border-width: 0 70px 120px 70px; top: 80%; left: 70%;"></div>
    </div>
    
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Organization Registration</h2>
            <p>Register your organization to start using our Face Recognition Presence System</p>
        </div>
        
        <form method="post" action="register.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="OrgName">Organization Name</label>
                <input type="text" id="OrgName" name="OrgName" class="form-control" placeholder="Enter organization name" required>
            </div>
            
            <div class="form-group">
                <label for="OrgEmail">Email Address</label>
                <input type="email" id="OrgEmail" name="OrgEmail" class="form-control" placeholder="Enter organization email" required>
            </div>
            
            <div class="form-group">
                <label for="OrgPass1">Password</label>
                <div class="password-container">
                    <input type="password" id="OrgPass1" name="OrgPass1" class="form-control" placeholder="Create a password" required>
                    <span class="toggle-password" onclick="togglePassword('OrgPass1')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="OrgPass2">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="OrgPass2" name="OrgPass2" class="form-control" placeholder="Confirm your password" required>
                    <span class="toggle-password" onclick="togglePassword('OrgPass2')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="MobNo">Mobile Number</label>
                <input type="number" id="MobNo" name="MobNo" class="form-control" placeholder="Enter mobile number" required>
            </div>
            
            <div class="form-group">
                <label for="myfile">Organization Certificate</label>
                <input type="file" id="myfile" name="myfile" class="form-control" required>
                <div class="file-info">Upload your registration certificate (PDF, JPG, PNG)</div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-paper-plane"></i> SEND OTP
            </button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.html">Sign In</a></p>
        </div>
    </div>
    
    <footer>
        <p>Face Recognition Presence System | TYBBA(CA) Sem V Project</p>
        <p>© 2025 Developed by Sakib Shaikh & Swapnil Salunke</p>
    </footer>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = passwordField.nextElementSibling.querySelector('i');
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Create additional background elements
        function createBackgroundElements() {
            const container = document.querySelector('.background-elements');
            const types = ['circle', 'square', 'triangle'];
            const colors = [
                'rgba(26, 188, 156, 0.05)',
                'rgba(52, 152, 219, 0.05)',
                'rgba(155, 89, 182, 0.05)',
                'rgba(231, 76, 60, 0.05)'
            ];
            
            for (let i = 0; i < 8; i++) {
                const type = types[Math.floor(Math.random() * types.length)];
                const element = document.createElement('div');
                element.className = type;
                
                // Random size
                const size = Math.random() * 150 + 50;
                
                // Random position
                const top = Math.random() * 100;
                const left = Math.random() * 100;
                
                // Random animation duration
                const duration = Math.random() * 20 + 10;
                
                // Set styles
                element.style.width = `${size}px`;
                element.style.height = type === 'triangle' ? '0' : `${size}px`;
                
                if (type === 'triangle') {
                    const borderWidth = size / 2;
                    element.style.borderWidth = `0 ${borderWidth}px ${size}px ${borderWidth}px`;
                    element.style.borderColor = `transparent transparent ${colors[Math.floor(Math.random() * colors.length)]} transparent`;
                } else {
                    element.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                }
                
                element.style.top = `${top}%`;
                element.style.left = `${left}%`;
                element.style.animationDuration = `${duration}s`;
                
                // Random delay
                element.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(element);
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createBackgroundElements();
        });
    </script>
</body>
</html>