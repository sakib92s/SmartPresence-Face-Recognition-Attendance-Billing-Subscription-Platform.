<?php
session_start();
include("connect.php");

// Check if organization is logged in
if (!isset($_SESSION['org_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'Please login as an organization first']);
    exit();
}

$org_id = $_SESSION['org_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $name = trim($_POST["name"]);
    
    if (empty($name)) {
        echo '<div style="padding:5px; color:red;">Please enter a name to search</div>';
        exit();
    }

    // Search in employees table for the current organization
    $stmt = mysqli_prepare($conn, "SELECT emp_id, empname, email, category, mobile FROM employees WHERE org_id = ? AND empname LIKE CONCAT(?, '%') LIMIT 10");
    mysqli_stmt_bind_param($stmt, "is", $org_id, $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $safeName = htmlspecialchars($row['empname']);
            $safeCategory = htmlspecialchars($row['category']);
            $empId = $row['emp_id'];

            echo '<div class="suggestion-item" data-emp-id="' . $empId . '" style="padding:8px; cursor:pointer; border-bottom:1px solid #eee;" onclick="fetchEmployeeDetails(' . $empId . ')">';
            echo '<strong>' . $safeName . '</strong> (' . $safeCategory . ')';
            echo '<div style="font-size:12px; color:#666;">' . htmlspecialchars($row['email']) . ' | ' . htmlspecialchars($row['mobile']) . '</div>';
            echo '</div>';
        }
    } else {
        echo '<div style="padding:8px; color:#999;">No employees found with that name</div>';
    }

    mysqli_stmt_close($stmt);
} else {
    header("HTTP/1.1 400 Bad Request");
    echo '<div style="padding:5px; color:red;">Invalid request</div>';
}

mysqli_close($conn);
?>