"""
Wage Calculation Module for Indian Payroll Compliance
Following Indian Labour Statutory Rules (PF, ESIC, Bonus)
"""

import math

# Daily Wage Rates by Category
DAILY_WAGES = {
    'UNSKILLED': 805,
    'SEMI-SKILLED': 893,
    'SKILLED': 981,
    'HIGH SKILLED': 1065
}

# Constants for Indian payroll calculations
BONUS_RATE = 8.33  # Percentage
EPF_RATE_EMPLOYEE = 12  # Percentage
EPF_WAGE_CEILING = 15000  # Wage ceiling for EPF
ESIC_RATE = 0.75  # Percentage

def calculate_professional_tax(earned_wage, gender):
    """
    Calculate Professional Tax based on gender and earned wage
    
    Args:
        earned_wage (float): Earned wage of employee
        gender (str): 'MALE' or 'FEMALE'
        
    Returns:
        float: Professional Tax amount
    """
    gender = gender.upper() if gender else 'MALE'
    
    if gender == 'MALE':
        if earned_wage < 7500:
            return 0
        elif 7500 <= earned_wage <= 10000:
            return 175
        else:  # earned_wage > 10000
            return 200
    elif gender == 'FEMALE':
        if earned_wage <= 25000:
            return 0
        else:  # earned_wage > 25000
            return 200
    else:
        # Default to male rules if gender not specified
        if earned_wage < 7500:
            return 0
        elif 7500 <= earned_wage <= 10000:
            return 175
        else:
            return 200

def calculate_edli(earned_wage):
    """
    Calculate EDLI value
    
    Args:
        earned_wage (float): Earned wage of employee
        
    Returns:
        float: EDLI value
    """
    if earned_wage >= 15000:
        return 15000
    else:
        return earned_wage

def calculate_wage_components(attendance_df):
    """
    Calculate all wage components for each employee
    
    Args:
        attendance_df (pandas.DataFrame): Attendance data with GENDER column
        
    Returns:
        list: List of dictionaries with wage calculations
    """
    wage_data = []
    
    for idx, row in attendance_df.iterrows():
        try:
            sr_no = int(row['SR.NO'])
            employee_name = str(row['NAME OF EMPLOYEE']).strip()
            category = str(row['CATEGORY']).strip().upper()
            working_days = float(row['WORKING DAYS'])
            attendance = float(row['ATTENDANCE'])
            gender = str(row['GENDER']).strip().upper() if 'GENDER' in row else 'MALE'
            
            # Skip if no attendance
            if attendance <= 0 or working_days <= 0:
                continue
            
            # Get daily wage based on category
            daily_wage = DAILY_WAGES.get(category, 805)  # Default to UNSKILLED
            
            # 1. Earned Wage = Attendance × Daily Wage
            earned_wage = round(attendance * daily_wage, 2)
            
            # 2. Daily Wage (DW) - already defined
            
            # 3. BONUS = Earned Wage × 8.33% (ONLY FOR UNSKILLED CATEGORY)
            if category == 'UNSKILLED':
                bonus = round(earned_wage * (BONUS_RATE / 100), 2)
            else:
                bonus = 0.00  # No bonus for other categories
            
            # 4. EDLI = min(Earned Wage, 15000)
            edli = calculate_edli(earned_wage)
            
            # 5. EPF (Employee Contribution) = EDLI × 12%
            epf = round(edli * (EPF_RATE_EMPLOYEE / 100), 2)
            
            # 6. ESIC - Apply ONLY for Unskilled category
            if category == 'UNSKILLED':
                esic = round(earned_wage * (ESIC_RATE / 100), 2)
            else:
                esic = 0.00
            
            # 7. Professional Tax (PT) based on gender and earned wage
            pt = calculate_professional_tax(earned_wage, gender)
            
            # 8. Net Pay = Earned Wage + Bonus - (EPF + ESIC + PT)
            total_deductions = epf + esic + pt
            net_pay = round(earned_wage + bonus - total_deductions, 2)
            
            # Ensure net pay is not negative
            net_pay = max(0, net_pay)
            
            # Create wage record
            wage_record = {
                'SR.NO': sr_no,
                'NAME OF EMPLOYEE': employee_name,
                'DESIGNATION': category,
                'ATTN': attendance,
                'DW': daily_wage,
                'EARN_WAGE': earned_wage,
                'EDLI': edli,
                'BONUS_8.33%': bonus,
                'EPF_12%': epf,
                'ESIC_0.75%': esic,
                'PT': pt,
                'NET_PAY': net_pay,
                'GENDER': gender,
                'WORKING_DAYS': working_days
            }
            
            wage_data.append(wage_record)
            
        except Exception as e:
            # Skip problematic rows but continue processing
            print(f"Error processing row {row.get('SR.NO', 'unknown')}: {str(e)}")
            continue
    
    # Sort by SR.NO
    wage_data.sort(key=lambda x: x['SR.NO'])
    
    return wage_data

def calculate_summary_statistics(wage_data):
    """
    Calculate summary statistics for the wage muster
    
    Args:
        wage_data (list): List of wage records
        
    Returns:
        dict: Summary statistics
    """
    if not wage_data:
        return {}
    
    total_earned_wage = sum(record['EARN_WAGE'] for record in wage_data)
    total_bonus = sum(record['BONUS_8.33%'] for record in wage_data)
    total_epf = sum(record['EPF_12%'] for record in wage_data)
    total_esic = sum(record['ESIC_0.75%'] for record in wage_data)
    total_pt = sum(record['PT'] for record in wage_data)
    total_net_pay = sum(record['NET_PAY'] for record in wage_data)
    
    return {
        'employee_count': len(wage_data),
        'total_earned_wage': round(total_earned_wage, 2),
        'total_bonus': round(total_bonus, 2),
        'total_epf': round(total_epf, 2),
        'total_esic': round(total_esic, 2),
        'total_pt': round(total_pt, 2),
        'total_net_pay': round(total_net_pay, 2),
        'average_net_pay': round(total_net_pay / len(wage_data), 2) if wage_data else 0
    }

def validate_calculations(wage_record):
    """
    Validate wage calculations for a single record
    
    Args:
        wage_record (dict): Wage record to validate
        
    Returns:
        tuple: (is_valid, error_message)
    """
    try:
        # Check for negative values
        for key, value in wage_record.items():
            if isinstance(value, (int, float)) and value < 0:
                return False, f"Negative value found for {key}: {value}"
        
        # Verify earned wage calculation
        expected_earned = round(wage_record['ATTN'] * wage_record['DW'], 2)
        if abs(wage_record['EARN_WAGE'] - expected_earned) > 0.01:
            return False, f"Earned wage calculation mismatch: {wage_record['EARN_WAGE']} vs {expected_earned}"
        
        # Verify bonus calculation (only for unskilled)
        if wage_record['DESIGNATION'] == 'UNSKILLED':
            expected_bonus = round(wage_record['EARN_WAGE'] * (BONUS_RATE / 100), 2)
            if abs(wage_record['BONUS_8.33%'] - expected_bonus) > 0.01:
                return False, f"Bonus calculation mismatch: {wage_record['BONUS_8.33%']} vs {expected_bonus}"
        else:
            if wage_record['BONUS_8.33%'] != 0:
                return False, f"Bonus should be 0 for {wage_record['DESIGNATION']} category"
        
        # Verify net pay calculation
        calculated_net = wage_record['EARN_WAGE'] + wage_record['BONUS_8.33%'] - (
            wage_record['EPF_12%'] + 
            wage_record['ESIC_0.75%'] + 
            wage_record['PT']
        )
        if abs(wage_record['NET_PAY'] - calculated_net) > 0.01:
            return False, f"Net pay calculation mismatch: {wage_record['NET_PAY']} vs {calculated_net}"
        
        return True, "Validation passed"
    
    except Exception as e:
        return False, f"Validation error: {str(e)}"