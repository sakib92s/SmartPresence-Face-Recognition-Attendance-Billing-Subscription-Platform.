"""
Main Flask Application for Wage Muster Generator
Senior Python Full-Stack Engineer - Indian Payroll Compliance Expert
"""

import os
import uuid
from datetime import datetime
from flask import Flask, render_template, request, send_file, jsonify
import pandas as pd
from werkzeug.utils import secure_filename

# Import utility modules
from utils.excel_reader import validate_attendance_file, read_attendance_data
from utils.wage_calculator import calculate_wage_components
from utils.excel_writer import generate_wage_muster_excel

app = Flask(__name__)
app.config['SECRET_KEY'] = 'wage-muster-secret-key-2025'
app.config['UPLOAD_FOLDER'] = 'uploads'
app.config['GENERATED_FOLDER'] = 'generated'
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

# Allowed file extensions
ALLOWED_EXTENSIONS = {'xlsx', 'xls'}

def allowed_file(filename):
    """Check if file has allowed extension"""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def ensure_folders():
    """Ensure required folders exist"""
    folders = ['uploads', 'generated', 'templates']
    for folder in folders:
        folder_path = os.path.join(os.path.dirname(__file__), folder)
        if not os.path.exists(folder_path):
            os.makedirs(folder_path)

@app.route('/')
def index():
    """Render main input form"""
    return render_template('index.html')

@app.route('/generate', methods=['POST'])
def generate_wage_muster():
    """Generate wage muster from uploaded file"""
    try:
        # Get form data
        org_name = request.form.get('org_name', '').strip()
        tender_number = request.form.get('tender_number', '').strip()
        month = request.form.get('month', '').strip()
        contractor_name = request.form.get('contractor_name', '').strip()
        work_location = request.form.get('work_location', '').strip()
        
        # Validate required fields
        if not all([org_name, tender_number, month, contractor_name, work_location]):
            return render_template('error.html', 
                                 error="सभी फ़ील्ड भरें!")
        
        # Check if file was uploaded
        if 'attendance_file' not in request.files:
            return render_template('error.html', 
                                 error="फ़ाइल अपलोड करें!")
        
        file = request.files['attendance_file']
        
        if file.filename == '':
            return render_template('error.html', 
                                 error="कोई फ़ाइल नहीं चुनी गई!")
        
        if not allowed_file(file.filename):
            return render_template('error.html', 
                                 error="केवल .xlsx और .xls फ़ाइलें स्वीकार हैं!")
        
        # Generate unique filename
        original_filename = secure_filename(file.filename)
        unique_id = str(uuid.uuid4())[:8]
        save_filename = f"{unique_id}_{original_filename}"
        upload_path = os.path.join(app.config['UPLOAD_FOLDER'], save_filename)
        
        # Save uploaded file
        file.save(upload_path)
        
        # Validate Excel file structure
        validation_result = validate_attendance_file(upload_path)
        if not validation_result['valid']:
            # Clean up invalid file
            if os.path.exists(upload_path):
                os.remove(upload_path)
            return render_template('error.html', 
                                 error=f"Excel फ़ाइल गलत फॉर्मेट: {validation_result['error']}")
        
        # Read attendance data
        attendance_df = read_attendance_data(upload_path)
        
        if attendance_df is None or attendance_df.empty:
            if os.path.exists(upload_path):
                os.remove(upload_path)
            return render_template('error.html', 
                                 error="Excel फ़ाइल में कोई डेटा नहीं मिला!")
        
        # Calculate wage components
        wage_data = calculate_wage_components(attendance_df)
        
        # Generate output filename
        output_filename = f"wage_muster_{month.replace('-', '_')}_{unique_id}.xlsx"
        output_path = os.path.join(app.config['GENERATED_FOLDER'], output_filename)
        
        # Generate wage muster Excel
        generate_wage_muster_excel(
            wage_data=wage_data,
            org_name=org_name,
            tender_number=tender_number,
            month=month,
            contractor_name=contractor_name,
            work_location=work_location,
            output_path=output_path
        )
        
        # Clean up uploaded file
        if os.path.exists(upload_path):
            os.remove(upload_path)
        
        unskilled_count = sum(1 for row in wage_data if row['DESIGNATION'] == 'UNSKILLED')
        total_bonus = sum(row['BONUS_8.33%'] for row in wage_data)
        total_esic = sum(row['ESIC_0.75%'] for row in wage_data)
        
        # Prepare metadata for display
        metadata = {
            'org_name': org_name,
            'tender_number': tender_number,
            'month': month,
            'contractor_name': contractor_name,
            'work_location': work_location,
            'employee_count': len(wage_data),
            'unskilled_count': unskilled_count,
            'total_net_pay': sum(row['NET_PAY'] for row in wage_data),
            'total_epf': sum(row['EPF_12%'] for row in wage_data),
            'total_esic': total_esic,
            'total_bonus': total_bonus,
            'output_filename': output_filename
        }
        
        return render_template('index.html', 
                             success=True, 
                             metadata=metadata,
                             download_file=output_filename)
    
    except Exception as e:
        # Clean up files in case of error
        if 'upload_path' in locals() and os.path.exists(upload_path):
            os.remove(upload_path)
        return render_template('error.html', 
                             error=f"त्रुटि: {str(e)}")

@app.route('/download/<filename>')
def download_file(filename):
    """Download generated wage muster file"""
    try:
        file_path = os.path.join(app.config['GENERATED_FOLDER'], filename)
        if os.path.exists(file_path):
            return send_file(file_path, 
                           as_attachment=True, 
                           download_name=f"Wage_Muster_{filename}")
        else:
            return render_template('error.html', 
                                 error="फ़ाइल नहीं मिली!")
    except Exception as e:
        return render_template('error.html', 
                             error=f"डाउनलोड त्रुटि: {str(e)}")

@app.route('/api/validate-excel', methods=['POST'])
def api_validate_excel():
    """API endpoint for client-side Excel validation"""
    if 'file' not in request.files:
        return jsonify({'valid': False, 'error': 'फ़ाइल अपलोड करें'})
    
    file = request.files['file']
    if file.filename == '':
        return jsonify({'valid': False, 'error': 'कोई फ़ाइल नहीं चुनी गई'})
    
    if not allowed_file(file.filename):
        return jsonify({'valid': False, 'error': 'केवल .xlsx और .xls फ़ाइलें'})
    
    # Save temporarily for validation
    temp_filename = f"temp_validation_{uuid.uuid4()}.xlsx"
    temp_path = os.path.join(app.config['UPLOAD_FOLDER'], temp_filename)
    file.save(temp_path)
    
    try:
        validation_result = validate_attendance_file(temp_path)
        
        # Count rows if valid
        row_count = 0
        if validation_result['valid']:
            df = pd.read_excel(temp_path)
            row_count = len(df)
        
        # Clean up temp file
        if os.path.exists(temp_path):
            os.remove(temp_path)
        
        return jsonify({
            'valid': validation_result['valid'],
            'error': validation_result.get('error', ''),
            'row_count': row_count
        })
    
    except Exception as e:
        # Clean up on error
        if os.path.exists(temp_path):
            os.remove(temp_path)
        return jsonify({'valid': False, 'error': str(e)})

@app.errorhandler(404)
def not_found_error(error):
    return render_template('error.html', error="पेज नहीं मिला!"), 404

@app.errorhandler(500)
def internal_error(error):
    return render_template('error.html', error="सर्वर त्रुटि!"), 500

def cleanup_old_files():
    """Clean up files older than 24 hours"""
    try:
        for folder_name in [app.config['UPLOAD_FOLDER'], app.config['GENERATED_FOLDER']]:
            folder_path = os.path.join(os.path.dirname(__file__), folder_name)
            if os.path.exists(folder_path):
                for filename in os.listdir(folder_path):
                    file_path = os.path.join(folder_path, filename)
                    # Skip .gitkeep files
                    if filename == '.gitkeep':
                        continue
                    # Remove files older than 24 hours
                    if os.path.isfile(file_path):
                        file_age = datetime.now().timestamp() - os.path.getmtime(file_path)
                        if file_age > 24 * 3600:  # 24 hours in seconds
                            os.remove(file_path)
    except:
        pass  # Silently fail cleanup

# Add cleanup function to app for easy access
app.cleanup_old_files = cleanup_old_files

if __name__ == '__main__':
    ensure_folders()
    cleanup_old_files()
    app.run(debug=True, host='127.0.0.1', port=5000)