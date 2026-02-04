<?php
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="sample_attendance_template.csv"');

echo "SR.NO,NAME OF EMPLOYEE,CATEGORY,WORKING DAYS,ATTENDANCE,GENDER\n";
echo "1,RAJESH KUMAR,UNSKILLED,26,24,MALE\n";
echo "2,PRIYA SHARMA,SEMI-SKILLED,26,26,FEMALE\n";
echo "3,AMIT SINGH,SKILLED,26,25,MALE\n";
echo "4,SUNITA DEVI,HIGH SKILLED,26,24,FEMALE\n";
echo "5,VIJAY PATEL,UNSKILLED,26,22,MALE\n";
?>