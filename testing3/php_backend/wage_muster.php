<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['org_email'])) {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['org_id'];
$org_name = $_SESSION['org_name'];

// Get available attendance files
$attendance_files = [];
$attendance_dir = 'attendance_reports/';
if (is_dir($attendance_dir)) {
    $files = scandir($attendance_dir);
    foreach ($files as $file) {
        if (strpos($file, $org_name . '_') === 0 && strpos($file, '_attendance.xlsx')) {
            $attendance_files[] = $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wage Muster Generator - <?php echo htmlspecialchars($org_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
            margin-top: 30px;
        }
        .header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #1a237e;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3949ab 0%, #283593 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
        }
        .file-upload {
            border: 3px dashed #bbdefb;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            background: #f8fdff;
            cursor: pointer;
        }
        .file-upload:hover {
            border-color: #3949ab;
            background: #e8f4fc;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 4px solid #3949ab;
        }
        .back-btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header text-center">
            <h1><i class="fas fa-calculator"></i> Wage Muster Generator</h1>
            <p class="mb-0">Generate wage muster with PF, ESIC, Bonus, and Professional Tax calculations</p>
            <p><strong>Organization:</strong> <?php echo htmlspecialchars($org_name); ?></p>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form action="wage_muster_generate.php" method="POST" enctype="multipart/form-data" id="wageForm">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Organization Name *</label>
                    <input type="text" class="form-control" name="org_name" value="<?php echo htmlspecialchars($org_name); ?>" required readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tender Number</label>
                    <input type="text" class="form-control" name="tender_number" placeholder="Enter tender number">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Month *</label>
                    <select class="form-select" name="month" required>
                        <option value="">Select Month</option>
                        <?php
                        $months = [
                            'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
                            'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
                        ];
                        $currentYear = date('Y');
                        foreach($months as $month) {
                            echo "<option value='{$month}-{$currentYear}'>{$month} {$currentYear}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contractor Name</label>
                    <input type="text" class="form-control" name="contractor_name" placeholder="Enter contractor name">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label">Work Location</label>
                    <input type="text" class="form-control" name="work_location" placeholder="Enter work location">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Select Attendance File *</label>
                <select class="form-select" name="attendance_file" id="attendanceFileSelect" required>
                    <option value="">Select an attendance file</option>
                    <?php foreach($attendance_files as $file): ?>
                        <option value="<?php echo htmlspecialchars($file); ?>">
                            <?php echo htmlspecialchars($file); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="upload_new">-- Upload New File --</option>
                </select>
            </div>

            <div class="mb-4" id="uploadSection" style="display: none;">
                <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                    <h5>Drag & Drop or Click to Upload Attendance Excel</h5>
                    <p class="text-muted">Supported formats: .xlsx, .xls</p>
                    <input type="file" class="form-control d-none" id="fileInput" name="attendance_file_upload" accept=".xlsx,.xls">
                    <button type="button" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-folder-open me-2"></i>Browse Files
                    </button>
                </div>
                <div id="fileInfo" class="mt-3" style="display: none;"></div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-info-circle"></i> Required Excel Format
                        </div>
                        <div class="card-body">
                            <p>Your Excel file must contain these columns (column names can vary):</p>
                            <ul>
                                <li><strong>SR.NO</strong> (or Serial No, ID)</li>
                                <li><strong>NAME OF EMPLOYEE</strong> (or Employee Name, Name)</li>
                                <li><strong>CATEGORY</strong> (Unskilled, Semi-Skilled, Skilled, High Skilled)</li>
                                <li><strong>WORKING DAYS</strong> (Total working days in month)</li>
                                <li><strong>ATTENDANCE</strong> (Days present)</li>
                                <li><strong>GENDER</strong> (Male/Female or M/F)</li>
                            </ul>
                            <a href="download_sample.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-2"></i>Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="generateBtn">
                    <i class="fas fa-calculator me-2"></i> Generate Wage Muster
                </button>
                <a href="Dashboard.php" class="btn btn-secondary back-btn">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </form>

        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-calculator"></i> Calculation Rules
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <h6><i class="fas fa-rupee-sign text-primary"></i> Daily Wage Rates</h6>
                                    <ul class="mb-0">
                                        <li>Unskilled: ₹805</li>
                                        <li>Semi-Skilled: ₹893</li>
                                        <li>Skilled: ₹981</li>
                                        <li>High Skilled: ₹1065</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card">
                                    <h6><i class="fas fa-percentage text-primary"></i> Statutory Deductions</h6>
                                    <ul class="mb-0">
                                        <li>EPF: 12% (on EDLI, max ₹15,000)</li>
                                        <li>ESIC: 0.75% (Unskilled only)</li>
                                        <li>Bonus: 8.33% (Unskilled only)</li>
                                        <li>Professional Tax: As per state rules</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Handle file selection
        document.getElementById('attendanceFileSelect').addEventListener('change', function() {
            const uploadSection = document.getElementById('uploadSection');
            if (this.value === 'upload_new') {
                uploadSection.style.display = 'block';
            } else {
                uploadSection.style.display = 'none';
            }
        });

        // Handle file upload
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileInfo = document.getElementById('fileInfo');
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                
                fileInfo.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file-excel me-2"></i>
                        <strong>Selected File:</strong> ${file.name}<br>
                        <strong>Size:</strong> ${fileSize} MB<br>
                        <strong>Type:</strong> ${file.type || 'Excel File'}
                    </div>
                `;
                fileInfo.style.display = 'block';
            }
        });

        // Form submission
        document.getElementById('wageForm').addEventListener('submit', function(e) {
            const generateBtn = document.getElementById('generateBtn');
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
            // Allow form to submit
            return true;
        });
    </script>
</body>
</html>