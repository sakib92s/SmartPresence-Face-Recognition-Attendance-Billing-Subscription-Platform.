<?php
session_start();

if (!isset($_SESSION['mobile'])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification | Face Recognition System</title>
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
    
    /* OTP Container */
    .otp-container {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 3rem;
      width: 90%;
      max-width: 500px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
      text-align: center;
      position: relative;
      overflow: hidden;
      z-index: 10;
    }
    
    .otp-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to right, var(--accent), var(--secondary));
    }
    
    .otp-header {
      margin-bottom: 2rem;
    }
    
    .otp-icon {
      font-size: 3.5rem;
      color: var(--accent);
      margin-bottom: 1rem;
      animation: pulse 2s infinite;
    }
    
    .otp-title {
      font-size: 2.2rem;
      margin-bottom: 0.5rem;
      background: linear-gradient(to right, var(--accent), var(--secondary));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    
    .otp-subtitle {
      color: #bdc3c7;
      font-size: 1.1rem;
      margin-top: 0.5rem;
    }
    
    .mobile-number {
      font-weight: bold;
      color: var(--accent);
      font-size: 1.3rem;
      letter-spacing: 1px;
      margin-top: 5px;
      display: inline-block;
      background: rgba(26, 188, 156, 0.1);
      padding: 5px 15px;
      border-radius: 30px;
    }
    
    .otp-form {
      margin-top: 2rem;
    }
    
    .form-group {
      margin-bottom: 1.8rem;
      position: relative;
    }
    
    .otp-input {
      width: 100%;
      padding: 15px 20px;
      border-radius: 50px;
      border: none;
      background: rgba(255, 255, 255, 0.12);
      color: var(--light);
      font-size: 1.2rem;
      text-align: center;
      letter-spacing: 10px;
      transition: all 0.3s ease;
      outline: none;
    }
    
    .otp-input:focus {
      box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.4);
      background: rgba(255, 255, 255, 0.15);
    }
    
    .verify-btn {
      display: inline-block;
      padding: 14px 40px;
      background: linear-gradient(to right, var(--accent), var(--secondary));
      color: white;
      text-decoration: none;
      border-radius: 50px;
      font-weight: bold;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(26, 188, 156, 0.4);
      border: none;
      cursor: pointer;
      width: 100%;
      max-width: 300px;
      position: relative;
      overflow: hidden;
    }
    
    .verify-btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(26, 188, 156, 0.6);
    }
    
    .verify-btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
    }
    
    .verify-btn:hover::after {
      left: 100%;
    }
    
    .resend-section {
      margin-top: 1.5rem;
      color: #bdc3c7;
    }
    
    .resend-link {
      color: var(--accent);
      text-decoration: none;
      margin-left: 5px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .resend-link:hover {
      text-decoration: underline;
    }
    
    /* Footer */
    footer {
      position: absolute;
      bottom: 0;
      width: 100%;
      text-align: center;
      padding: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: #95a5a6;
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
    
    @keyframes pulse {
      0% {
        transform: scale(1);
        opacity: 1;
      }
      50% {
        transform: scale(1.05);
        opacity: 0.8;
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .fade-in {
      animation: fadeIn 0.6s ease-out forwards;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .otp-container {
        padding: 2rem 1.5rem;
        width: 95%;
      }
      
      .otp-title {
        font-size: 1.8rem;
      }
      
      .otp-input {
        padding: 12px 15px;
        font-size: 1.1rem;
      }
      
      .verify-btn {
        padding: 12px 30px;
      }
    }
    
    @media (max-width: 480px) {
      .otp-icon {
        font-size: 3rem;
      }
      
      .otp-title {
        font-size: 1.6rem;
      }
      
      .mobile-number {
        font-size: 1.1rem;
      }
    }
  </style>
</head>
<body>
  <div class="background-elements">
    <div class="circle" style="width: 250px; height: 250px; top: 15%; left: 10%;"></div>
    <div class="circle" style="width: 120px; height: 120px; top: 75%; left: 85%;"></div>
    <div class="square" style="width: 180px; height: 180px; top: 25%; left: 80%;"></div>
    <div class="square" style="width: 100px; height: 100px; top: 70%; left: 15%;"></div>
    <div class="triangle" style="border-width: 0 90px 155px 90px; top: 45%; left: 30%;"></div>
    <div class="triangle" style="border-width: 0 60px 105px 60px; top: 85%; left: 65%;"></div>
  </div>
  
  <!-- OTP Verification Container -->
  <div class="otp-container fade-in">
    <div class="otp-header">
      <div class="otp-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h1 class="otp-title">OTP Verification</h1>
      <p class="otp-subtitle">Enter the verification code sent to</p>
      <div class="mobile-number">
        <i class="fas fa-mobile-alt"></i> <?php echo isset($_SESSION['mobile']) ? htmlspecialchars($_SESSION['mobile']) : 'your mobile'; ?>
      </div>
    </div>
    
    <form method="post" action="Employee-Verify-otp.php" class="otp-form">
      <div class="form-group">
        <input type="text" name="otp_input" class="otp-input" placeholder="Enter 6-digit OTP" maxlength="6" required autofocus>
      </div>
      
      <button type="submit" class="verify-btn">
        <i class="fas fa-check-circle"></i> Verify OTP
      </button>
      
      <div class="resend-section">
        Didn't receive the code? 
        <a href="#" class="resend-link">Resend OTP</a>
      </div>
    </form>
  </div>
  
  <footer>
    <p>Face Recognition Presence System | OTP Verification</p>
    <p>© 2025 Developed by Sakib Shaikh & Swapnil Salunke</p>
  </footer>
  
  <script>
    // Focus on OTP input
    document.querySelector('.otp-input').focus();
    
    // Background elements creation
    function createBackgroundElements() {
      const container = document.querySelector('.background-elements');
      const types = ['circle', 'square', 'triangle'];
      const colors = [
        'rgba(26, 188, 156, 0.05)',
        'rgba(52, 152, 219, 0.05)',
        'rgba(155, 89, 182, 0.05)',
        'rgba(231, 76, 60, 0.05)'
      ];
      
      for (let i = 0; i < 6; i++) {
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
      
      // Auto move to next input (simulated)
      const otpInput = document.querySelector('.otp-input');
      otpInput.addEventListener('input', function() {
        if (this.value.length === 6) {
          this.blur();
        }
      });
      
      // Resend OTP functionality
      document.querySelector('.resend-link').addEventListener('click', function(e) {
        e.preventDefault();
        this.textContent = 'Sending...';
        setTimeout(() => {
          this.textContent = 'Resend OTP';
          alert('New OTP has been sent to your mobile!');
        }, 1500);
      });
    });
  </script>
</body>
</html>