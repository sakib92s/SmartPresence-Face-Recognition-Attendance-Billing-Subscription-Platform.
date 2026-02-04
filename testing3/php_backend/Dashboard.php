<?php
session_start();
if (!isset($_SESSION['org_id'])) {
    header("Location: login.html");
    echo '<script>
        alert("You must log in first!");
        window.location="login.html";
        </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Recognition Presence System | Dashboard</title>
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
        
        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            z-index: 10;
            position: relative;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            text-align: center;
            padding: 2rem 0;
            margin-bottom: 1rem;
        }
        
        .logo {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
            animation: pulse 2s infinite;
        }
        
        .dashboard-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Navbar */
        .navbar {
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            color: var(--light);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-links a:hover {
            background: rgba(26, 188, 156, 0.2);
            transform: translateY(-2px);
        }
        
        .nav-links a i {
            font-size: 1.1rem;
        }
        
        .search-section {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        
        #searchInput {
            padding: 8px 15px;
            border-radius: 50px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            width: 250px;
            transition: all 0.3s ease;
        }
        
        #searchInput:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--accent);
            background: rgba(255, 255, 255, 0.15);
        }
        
        #suggestionBox {
            display: none;
            position: absolute;
            top: 45px;
            right: 0;
            background: rgba(44, 62, 80, 0.95);
            border-radius: 10px;
            padding: 10px;
            width: 250px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .suggestion-item {
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .suggestion-item:hover {
            background: rgba(26, 188, 156, 0.3);
        }
        
        .suggestion-item i {
            color: var(--accent);
        }
        
        .logout-btn {
            padding: 8px 20px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
            transform: translateY(-2px);
        }
        
        /* Main content */
        .dashboard-content {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            padding: 3rem 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
        }
        
        .dashboard-content h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--accent);
        }
        
        .dashboard-content p {
            max-width: 600px;
            margin: 0 auto 2rem;
            color: #bdc3c7;
            line-height: 1.6;
        }
        
        .add-employee-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(26, 188, 156, 0.4);
            position: relative;
            overflow: hidden;
            border: none;
            cursor: pointer;
        }
        
        .add-employee-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(26, 188, 156, 0.6);
        }
        
        .add-employee-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .add-employee-btn:hover::after {
            left: 100%;
        }
        
        /* Stats Section */
        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            padding: 1.5rem;
            flex: 1;
            min-width: 200px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .stat-title {
            font-size: 1.1rem;
            color: #bdc3c7;
        }
        
        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
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
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .search-section {
                width: 100%;
                justify-content: center;
            }
            
            #searchInput {
                width: 100%;
            }
            
            .dashboard-title {
                font-size: 2rem;
            }
            
            .stat-item {
                min-width: 100%;
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
    
    <div class="container">
        <header class="dashboard-header">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="dashboard-title">Employee Dashboard</h1>
            <p class="tagline">Manage your workforce efficiently with our face recognition system</p>
        </header>
        
        <!-- Navbar -->
        <div class="navbar">
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="about.html"><i class="fas fa-info-circle"></i> About</a>
                <a href="contactus.html"><i class="fas fa-envelope"></i> Contact Us</a>
            </div>
            
            <div class="search-section">
                <input type="text" id="searchInput" placeholder="Search employee..." autocomplete="off" onkeyup="searchEmployee()">
                <div id="suggestionBox"></div>
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">142</div>
                <div class="stat-title">Total Employees</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number">127</div>
                <div class="stat-title">Present Today</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-number">15</div>
                <div class="stat-title">Late Arrivals</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-number">8</div>
                <div class="stat-title">Absent Today</div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="dashboard-content">
            <h2>Employee Management System</h2>
            <p>Welcome to your dashboard. Here you can manage your employees, track attendance, and view reports. 
               Add new employees to the system using the button below.</p>
            <a href="HrLogin.html" class="add-employee-btn">
                <i class="fas fa-user-plus"></i> Add New Employee
            </a>
        </div>
    </div>
    
    <footer>
        <p>Face Recognition Presence System | Employee Dashboard</p>
        <p>© 2023 Developed by Sakib Shaikh & Swapnil Salunke</p>
    </footer>
    
    <script>
        // Real-time employee search with AJAX
        function searchEmployee() {
            const input = document.getElementById("searchInput");
            const query = input.value;
            const box = document.getElementById("suggestionBox");
            
            if (query.length === 0) {
                box.style.display = "none";
                return;
            }
            
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "search_employee.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onload = function() {
                if (this.status === 200) {
                    box.innerHTML = this.responseText;
                    box.style.display = "block";
                    
                    // Add click handlers to suggestion items
                    const items = box.querySelectorAll('.suggestion-item');
                    items.forEach(item => {
                        item.addEventListener('click', function() {
                            input.value = this.textContent.split(' - ')[0];
                            box.style.display = "none";
                        });
                    });
                } else {
                    box.style.display = "none";
                }
            };
            
            xhr.send("name=" + encodeURIComponent(query));
        }
        
        // Close suggestion box when clicking outside
        document.addEventListener('click', function(event) {
            const box = document.getElementById('suggestionBox');
            const input = document.getElementById('searchInput');
            
            if (event.target !== input && event.target !== box && !box.contains(event.target)) {
                box.style.display = 'none';
            }
        });
        
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