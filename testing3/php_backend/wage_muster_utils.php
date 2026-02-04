<?php
// Wage Muster Utility Functions

// Daily Wage Rates
define('DAILY_WAGES', [
    'UNSKILLED' => 805,
    'SEMI-SKILLED' => 893,
    'SKILLED' => 981,
    'HIGH SKILLED' => 1065
]);

// Statutory rates
define('BONUS_RATE', 8.33); // Percentage
define('EPF_RATE', 12); // Percentage
define('ESIC_RATE', 0.75); // Percentage
define('EPF_WAGE_CEILING', 15000);

function calculateProfessionalTax($earned_wage, $gender) {
    $gender = strtoupper($gender);
    
    if ($gender === 'MALE' || $gender === 'M' || $gender === 'पुरुष') {
        if ($earned_wage < 7500) return 0;
        elseif ($earned_wage <= 10000) return 175;
        else return 200;
    } elseif ($gender === 'FEMALE' || $gender === 'F' || $gender === 'महिला' || $gender === 'स्त्री') {
        if ($earned_wage <= 25000) return 0;
        else return 200;
    } else {
        // Default to male rules
        if ($earned_wage < 7500) return 0;
        elseif ($earned_wage <= 10000) return 175;
        else return 200;
    }
}

function calculateEDLI($earned_wage) {
    return min($earned_wage, EPF_WAGE_CEILING);
}

function validateExcelFile($file_path) {
    // Simple validation - check if file exists and is readable
    if (!file_exists($file_path)) {
        return ['valid' => false, 'error' => 'File not found'];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'])) {
        return ['valid' => false, 'error' => 'Only Excel files are allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

function readAttendanceData($file_path) {
    // For PHP, we'll use PHPExcel or PhpSpreadsheet
    // For simplicity, we'll assume CSV or use simple Excel reading
    // In production, use PhpSpreadsheet library
    
    $data = [];
    
    // Try to read as CSV first
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        $headers = fgetcsv($handle);
        
        // Map column names
        $column_map = [];
        foreach ($headers as $index => $header) {
            $normalized = normalizeColumnName($header);
            
            if (strpos($normalized, 'SR') !== false || strpos($normalized, 'SERIAL') !== false || strpos($normalized, 'ID') !== false) {
                $column_map['sr_no'] = $index;
            } elseif (strpos($normalized, 'NAME') !== false) {
                $column_map['name'] = $index;
            } elseif (strpos($normalized, 'CATEGORY') !== false || strpos($normalized, 'DESIGNATION') !== false) {
                $column_map['category'] = $index;
            } elseif (strpos($normalized, 'WORKING DAYS') !== false || strpos($normalized, 'TOTAL DAYS') !== false) {
                $column_map['working_days'] = $index;
            } elseif (strpos($normalized, 'ATTENDANCE') !== false || strpos($normalized, 'ATN') !== false || strpos($normalized, 'PRESENT') !== false) {
                $column_map['attendance'] = $index;
            } elseif (strpos($normalized, 'GENDER') !== false || strpos($normalized, 'SEX') !== false) {
                $column_map['gender'] = $index;
            }
        }
        
        // Check required columns
        $required = ['sr_no', 'name', 'category', 'working_days', 'attendance'];
        foreach ($required as $col) {
            if (!isset($column_map[$col])) {
                throw new Exception("Missing required column: " . strtoupper(str_replace('_', ' ', $col)));
            }
        }
        
        $row_num = 1;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_num++;
            
            // Skip empty rows
            if (empty(array_filter($row))) continue;
            
            $record = [
                'sr_no' => isset($column_map['sr_no']) ? trim($row[$column_map['sr_no']]) : '',
                'name' => isset($column_map['name']) ? trim($row[$column_map['name']]) : '',
                'category' => isset($column_map['category']) ? strtoupper(trim($row[$column_map['category']])) : 'UNSKILLED',
                'working_days' => isset($column_map['working_days']) ? floatval($row[$column_map['working_days']]) : 0,
                'attendance' => isset($column_map['attendance']) ? floatval($row[$column_map['attendance']]) : 0,
                'gender' => isset($column_map['gender']) ? strtoupper(trim($row[$column_map['gender']])) : 'MALE'
            ];
            
            // Validate data
            if (!is_numeric($record['sr_no']) || $record['sr_no'] <= 0) continue;
            if (empty($record['name'])) continue;
            if ($record['working_days'] <= 0) continue;
            if ($record['attendance'] < 0 || $record['attendance'] > $record['working_days']) {
                $record['attendance'] = min($record['attendance'], $record['working_days']);
            }
            
            // Standardize category
            $record['category'] = standardizeCategory($record['category']);
            
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
}

function normalizeColumnName($name) {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', ' ', $name));
}

function standardizeCategory($category) {
    $category = strtoupper(trim($category));
    
    if (strpos($category, 'UNSKILLED') !== false || $category === 'U') {
        return 'UNSKILLED';
    } elseif (strpos($category, 'SEMI') !== false || $category === 'SS') {
        return 'SEMI-SKILLED';
    } elseif (strpos($category, 'SKILLED') !== false || $category === 'S') {
        return 'SKILLED';
    } elseif (strpos($category, 'HIGH') !== false || $category === 'HS') {
        return 'HIGH SKILLED';
    } else {
        return 'UNSKILLED'; // Default
    }
}

function calculateWageComponents($attendance_data) {
    $wage_data = [];
    $stats = [
        'total_employees' => 0,
        'unskilled_count' => 0,
        'total_earned_wage' => 0,
        'total_bonus' => 0,
        'total_epf' => 0,
        'total_esic' => 0,
        'total_pt' => 0,
        'total_net_pay' => 0
    ];
    
    foreach ($attendance_data as $record) {
        // Get daily wage
        $daily_wage = DAILY_WAGES[$record['category']] ?? DAILY_WAGES['UNSKILLED'];
        
        // Calculate earned wage
        $earned_wage = round($record['attendance'] * $daily_wage, 2);
        
        // Calculate EDLI
        $edli = calculateEDLI($earned_wage);
        
        // Calculate bonus (only for unskilled)
        $bonus = 0;
        if ($record['category'] === 'UNSKILLED') {
            $bonus = round($earned_wage * (BONUS_RATE / 100), 2);
            $stats['unskilled_count']++;
        }
        
        // Calculate EPF
        $epf = round($edli * (EPF_RATE / 100), 2);
        
        // Calculate ESIC (only for unskilled)
        $esic = 0;
        if ($record['category'] === 'UNSKILLED') {
            $esic = round($earned_wage * (ESIC_RATE / 100), 2);
        }
        
        // Calculate Professional Tax
        $pt = calculateProfessionalTax($earned_wage, $record['gender']);
        
        // Calculate net pay
        $net_pay = round($earned_wage + $bonus - $epf - $esic - $pt, 2);
        
        $wage_record = [
            'SR.NO' => $record['sr_no'],
            'NAME OF EMPLOYEE' => $record['name'],
            'DESIGNATION' => $record['category'],
            'ATTN' => $record['attendance'],
            'DW' => $daily_wage,
            'EARN_WAGE' => $earned_wage,
            'EDLI' => $edli,
            'BONUS' => $bonus,
            'EPF' => $epf,
            'ESIC' => $esic,
            'PT' => $pt,
            'NET_PAY' => $net_pay,
            'GENDER' => $record['gender'],
            'WORKING_DAYS' => $record['working_days']
        ];
        
        $wage_data[] = $wage_record;
        
        // Update stats
        $stats['total_employees']++;
        $stats['total_earned_wage'] += $earned_wage;
        $stats['total_bonus'] += $bonus;
        $stats['total_epf'] += $epf;
        $stats['total_esic'] += $esic;
        $stats['total_pt'] += $pt;
        $stats['total_net_pay'] += $net_pay;
    }
    
    return ['wage_data' => $wage_data, 'stats' => $stats];
}

function generateWageMuster($attendance_file, $org_name, $tender_number, $month, $contractor_name, $work_location) {
    // Validate file
    $validation = validateExcelFile($attendance_file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    // Read attendance data
    $attendance_data = readAttendanceData($attendance_file);
    if (empty($attendance_data)) {
        return ['success' => false, 'error' => 'No valid attendance data found'];
    }
    
    // Calculate wage components
    $result = calculateWageComponents($attendance_data);
    $wage_data = $result['wage_data'];
    $stats = $result['stats'];
    
    // Create output directory
    $output_dir = 'generated_wage_musters/';
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0777, true);
    }
    
    // Generate filename
    $filename = 'Wage_Muster_' . $org_name . '_' . str_replace('-', '_', $month) . '_' . date('Ymd_His') . '.csv';
    $output_path = $output_dir . $filename;
    
    // Create CSV file
    $csv_content = "WAGE MUSTER FORM-B\n";
    $csv_content .= strtoupper($org_name) . "\n";
    $csv_content .= "Tender No: {$tender_number} | Month: {$month}\n";
    $csv_content .= "Contractor: {$contractor_name} | Location: {$work_location}\n";
    $csv_content .= "Generated on: " . date('d-M-Y H:i:s') . "\n\n";
    
    // Headers
    $headers = ['SR.NO', 'NAME OF EMPLOYEE', 'DESIGNATION', 'Working Days', 'ATTN', 'DW', 
                'EARN WAGE', 'EDLI', 'BONUS @8.33', 'EPF@ 12%', 'ESIC- 0.75%', 'PT', 'NET PAY', 'SIGN'];
    $csv_content .= implode(',', $headers) . "\n";
    
    // Data rows
    foreach ($wage_data as $record) {
        $row = [
            $record['SR.NO'],
            '"' . $record['NAME OF EMPLOYEE'] . '"',
            $record['DESIGNATION'],
            $record['WORKING_DAYS'],
            $record['ATTN'],
            $record['DW'],
            $record['EARN_WAGE'],
            $record['EDLI'],
            $record['BONUS'],
            $record['EPF'],
            $record['ESIC'],
            $record['PT'],
            $record['NET_PAY'],
            '' // SIGN column
        ];
        $csv_content .= implode(',', $row) . "\n";
    }
    
    // Add totals row
    $csv_content .= "\nTOTAL,,,,,,";
    $csv_content .= $stats['total_earned_wage'] . ",";
    $csv_content .= ","; // EDLI total
    $csv_content .= $stats['total_bonus'] . ",";
    $csv_content .= $stats['total_epf'] . ",";
    $csv_content .= $stats['total_esic'] . ",";
    $csv_content .= $stats['total_pt'] . ",";
    $csv_content .= $stats['total_net_pay'] . "\n";
    
    // Add statistics
    $csv_content .= "\nSTATISTICS:\n";
    $csv_content .= "Total Employees: " . $stats['total_employees'] . "\n";
    $csv_content .= "Unskilled Employees: " . $stats['unskilled_count'] . "\n";
    $csv_content .= "Total Earned Wage: " . $stats['total_earned_wage'] . "\n";
    $csv_content .= "Total Bonus: " . $stats['total_bonus'] . "\n";
    $csv_content .= "Total EPF: " . $stats['total_epf'] . "\n";
    $csv_content .= "Total ESIC: " . $stats['total_esic'] . "\n";
    $csv_content .= "Total Professional Tax: " . $stats['total_pt'] . "\n";
    $csv_content .= "Total Net Payable: " . $stats['total_net_pay'] . "\n";
    $csv_content .= "Average Net Pay: " . round($stats['total_net_pay'] / max(1, $stats['total_employees']), 2) . "\n";
    
    // Save file
    if (file_put_contents($output_path, $csv_content)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $output_path,
            'stats' => $stats
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to save wage muster file'];
    }
}
?>