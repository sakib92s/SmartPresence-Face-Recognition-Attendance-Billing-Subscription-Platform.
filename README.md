SmartPresence-Face-Recognition-Attendance-Billing-Subscription-Platform
📘 Project Overview

SmartPresence-Face-Recognition-Attendance-Billing-Subscription-Platform is an advanced hybrid system combining a PHP-based Web Portal and a Python-based Face Recognition System.

It allows organizations to manage employees, record attendance using a webcam, generate wage muster reports, and maintain payroll-ready records, with all data securely stored in a MySQL database.

🧩 System Modules

The system works in two main parts:

🌐 Web Portal (HTML, CSS, JS, PHP, MySQL)
→ Organization registration, login, HR panel, employee management, wage muster interface

🧠 Python GUI (Tkinter + OpenCV)
→ Face registration, model training, and automated attendance marking

⚙️ Project Working Guide
🏁 1. Start from start.html

The entry point of the system is start.html.

New organizations can register

Existing organizations can log in directly

🏢 2. Organization Registration

On the registration page, fill in:

Organization Name

Email Address

Mobile Number

Password

When the mobile number is entered, an OTP is sent for verification.

📱 Important:
To send OTP messages, you need to purchase an SMS API from Renflair SMS Gateway.

After OTP verification, organization details are saved in the MySQL database.

🔐 3. Organization Login

Login using your registered email and password.

After successful login, you will be redirected to the main dashboard.

🧑‍💼 4. Add New Employee (HR Panel)

From the dashboard:

Click on Add New Employee

HR login page will appear

After HR login, Employee Registration Form opens

Enter:

Employee ID

Name

Department

Mobile Number (for OTP)

Email, etc.

Employee OTP verification is done via SMS API.

After verification, employee details are stored in the database.

🔎 5. Search & Manage Employees

From the dashboard, HR can:

Search employees by Name, ID, or Department

View employee details

Edit or remove employees

🧠 Python Section (Face Recognition System)
🐍 6. Run the Python Script

Open the project folder and run:

python main.py


A Tkinter login window will appear.

🔑 7. Python Login (Organization Credentials)

Login using the same email and password used on the web portal.

This ensures only registered organizations can access the attendance system.

🧍‍♂️ 8. Add Employee (Face Capture)

After login, click Add Employee.

Enter:

Employee ID (must match database)

Employee Name (must match database)

Click Capture Image.

Webcam opens

System captures 30 face images automatically

Images are stored in the images/ folder

Model is trained automatically after capture

⏰ 9. Take Attendance

Click Take Attendance from the main Tkinter window.

Webcam starts real-time face scanning

When a face is recognized:

Attendance is marked automatically

Excel file is updated with:

Employee ID

Name

Date

Time Stamp

Voice feedback confirms attendance

Attendance file is stored in:

php_backend/attendance_reports/

📅 10. Joining Record

When a new employee is added:

A separate Excel joining record file is created

This maintains a log of all employee entries

🧾 11. Wage Muster Generation (NEW)

After attendance is recorded, HR/Admin can generate Wage Muster Reports.

From the dashboard:

Open Wage Muster section

Select or upload the attendance Excel file

Click Generate Wage Muster

The system automatically:

Reads attendance data

Calculates total working days & present days

Calculates wage and salary components

Generates a formatted Wage Muster Excel file

Generated file is stored in:

php_backend/wage_muster_generator/generated/


HR can download the Wage Muster report from the web portal for payroll use.

🧾 Output Files Generated

Attendance Excel File → Daily attendance records

Employee Joining File → Joining log of employees

Wage Muster Excel File → Salary & wage calculation report

💡 Important Notes

Ensure webcam is connected and proper lighting is available

OTP feature requires a valid Renflair SMS Gateway API key

Python and PHP must use the same MySQL database

XAMPP (Apache + MySQL) must be running before Python app

Retrain the model after adding new employees

Wage Muster can only be generated after attendance files exist

🔧 Tech Stack Summary
Component	Technology
Frontend	HTML5, CSS3, JavaScript, Bootstrap
Backend (Web)	PHP, MySQL
Backend (AI)	Python 3.8+, OpenCV, Dlib
Database	MySQL
GUI	Tkinter
Voice Output	pyttsx3
Reports	Pandas, OpenPyXL, ReportLab
Wage Muster	Python, Pandas, OpenPyXL
OTP Service	Renflair SMS Gateway API
Local Server	XAMPP / WAMP
🧩 Summary of Workflow

Organization registers & verifies via OTP → stored in database

HR adds employees with OTP verification → employees stored in database

Python GUI login → face registration & training

Attendance marked via face recognition

Attendance Excel generated

➡️ HR generates Wage Muster from attendance file

➡️ Wage Muster Excel report generated for payroll

All data stored securely for HR, attendance & payroll use
