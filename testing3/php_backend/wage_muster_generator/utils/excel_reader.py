"""
Excel Reader Module for Attendance File Validation and Reading
Flexible column name detection for different organization formats
"""

import pandas as pd
import numpy as np
from openpyxl import load_workbook
import re

# Flexible column name mappings
COLUMN_MAPPINGS = {
    'SR.NO': ['SR.NO', 'SR NO', 'SR_NO', 'SERIAL NO', 'SERIAL_NO', 'S.NO', 'S NO', 'ID', 'EMPLOYEE ID', 'EMP ID'],
    'NAME OF EMPLOYEE': ['NAME OF EMPLOYEE', 'EMPLOYEE NAME', 'NAME', 'EMPLOYEE_NAME', 'EMP NAME', 'EMP_NAME', 'EMPLOYEE'],
    'CATEGORY': ['CATEGORY', 'DESIGNATION', 'TYPE', 'EMP TYPE', 'EMPLOYEE TYPE', 'SKILL LEVEL', 'GRADE'],
    'WORKING DAYS': ['WORKING DAYS', 'WORKING_DAYS', 'TOTAL DAYS', 'TOTAL WORKING DAYS', 'TOTAL_DAYS', 'DAYS IN MONTH', 'TOTAL'],
    'ATTENDANCE': ['ATTENDANCE', 'ATTN', 'DAYS PRESENT', 'DAYS_PRESENT', 'PRESENT DAYS', 'PRESENT_DAYS', 'ACTUAL DAYS', 'WORKED DAYS', 'DAYS WORKED'],
    'GENDER': ['GENDER', 'SEX', 'M/F', 'MALE/FEMALE', 'EMP GENDER']
}

REQUIRED_COLUMNS = ['SR.NO', 'NAME OF EMPLOYEE', 'CATEGORY', 'WORKING DAYS', 'ATTENDANCE', 'GENDER']

def normalize_column_name(col_name):
    """Normalize column name by removing special chars and converting to uppercase"""
    if pd.isna(col_name):
        return ""
    col_str = str(col_name).strip().upper()
    # Remove special characters
    col_str = re.sub(r'[^\w\s]', ' ', col_str)
    # Remove extra spaces
    col_str = re.sub(r'\s+', ' ', col_str).strip()
    return col_str

def map_columns(actual_columns):
    """Map actual column names to standard names"""
    column_map = {}
    mapped_columns = set()
    
    for actual_col in actual_columns:
        normalized_actual = normalize_column_name(actual_col)
        
        for std_col, possible_names in COLUMN_MAPPINGS.items():
            if std_col in mapped_columns:
                continue
                
            for possible in possible_names:
                if normalize_column_name(possible) == normalized_actual:
                    column_map[actual_col] = std_col
                    mapped_columns.add(std_col)
                    break
            if actual_col in column_map:
                break
    
    return column_map

def validate_attendance_file(file_path):
    """
    Validate the attendance Excel file structure and data
    
    Args:
        file_path (str): Path to the uploaded Excel file
        
    Returns:
        dict: {'valid': bool, 'error': str}
    """
    try:
        # Check if file exists
        import os
        if not os.path.exists(file_path):
            return {'valid': False, 'error': 'File not found'}
        
        # Try to read Excel file
        try:
            wb = load_workbook(file_path, data_only=True, read_only=True)
            sheet = wb.active
            
            # Get headers from first row
            headers = []
            for cell in sheet[1]:
                if cell.value:
                    headers.append(str(cell.value).strip())
            
            wb.close()
            
            # Check if we have enough columns
            if len(headers) < 3:
                return {'valid': False, 'error': 'File has too few columns'}
            
        except Exception as e:
            return {'valid': False, 'error': f'Invalid Excel file: {str(e)}'}
        
        # Validate data with pandas
        try:
            df = pd.read_excel(file_path)
            
            # Check for empty DataFrame
            if df.empty:
                return {'valid': False, 'error': 'Excel file is empty'}
            
            # Map columns to standard names
            column_map = map_columns(df.columns.tolist())
            
            # Check if we found required columns
            found_columns = set(column_map.values())
            missing_columns = []
            
            for req_col in REQUIRED_COLUMNS:
                if req_col not in found_columns:
                    missing_columns.append(req_col)
            
            if missing_columns:
                return {
                    'valid': False,
                    'error': f'Missing or unrecognized columns: {", ".join(missing_columns)}. Please ensure your Excel has these columns or their variations.'
                }
            
            # Rename columns for validation
            df_renamed = df.rename(columns=column_map)
            
            # Validate data types and constraints
            errors = []
            
            # Check SR.NO is numeric (ignore non-numeric rows like TOTAL)
            if 'SR.NO' in df_renamed.columns:
                # Convert to numeric, coerce errors to NaN
                df_renamed['SR.NO'] = pd.to_numeric(df_renamed['SR.NO'], errors='coerce')
                # Check only rows with valid SR.NO
                valid_rows = df_renamed[df_renamed['SR.NO'].notna()]
                if len(valid_rows) == 0:
                    errors.append('No valid employee records found in SR.NO column')
            
            # Check NAME OF EMPLOYEE is string
            if 'NAME OF EMPLOYEE' in df_renamed.columns:
                valid_rows = df_renamed[df_renamed['SR.NO'].notna()]
                for name in valid_rows['NAME OF EMPLOYEE']:
                    if not isinstance(name, str) and not pd.isna(name):
                        errors.append('NAME OF EMPLOYEE must contain text')
                        break
            
            # Check WORKING DAYS is numeric and positive
            if 'WORKING DAYS' in df_renamed.columns:
                valid_rows = df_renamed[df_renamed['SR.NO'].notna()]
                if not pd.api.types.is_numeric_dtype(valid_rows['WORKING DAYS']):
                    errors.append('WORKING DAYS must contain numbers')
                elif (valid_rows['WORKING DAYS'] <= 0).any():
                    errors.append('WORKING DAYS must be positive numbers')
            
            # Check ATTENDANCE is numeric and non-negative
            if 'ATTENDANCE' in df_renamed.columns:
                valid_rows = df_renamed[df_renamed['SR.NO'].notna()]
                if not pd.api.types.is_numeric_dtype(valid_rows['ATTENDANCE']):
                    errors.append('ATTENDANCE must contain numbers')
                elif (valid_rows['ATTENDANCE'] < 0).any():
                    errors.append('ATTENDANCE cannot be negative')
            
            # Check ATTENDANCE <= WORKING DAYS
            if all(col in df_renamed.columns for col in ['ATTENDANCE', 'WORKING DAYS']):
                valid_rows = df_renamed[df_renamed['SR.NO'].notna()]
                mask = valid_rows['ATTENDANCE'] > valid_rows['WORKING DAYS']
                if mask.any():
                    invalid_count = mask.sum()
                    errors.append(f'ATTENDANCE exceeds WORKING DAYS for {invalid_count} employees')
            
            # Check GENDER values - IMPORTANT FIX
            if 'GENDER' in df_renamed.columns:
                # Get only employee rows (exclude TOTAL row)
                employee_rows = df_renamed[df_renamed['SR.NO'].notna()].copy()
                
                if not employee_rows.empty:
                    # Convert GENDER to string, handle NaN
                    employee_rows['GENDER'] = employee_rows['GENDER'].astype(str)
                    
                    # Define valid gender values (more flexible)
                    valid_genders = ['MALE', 'FEMALE', 'M', 'F', 'पुरुष', 'महिला', 'स्त्री', 
                                    'MALE ', 'FEMALE ', 'M ', 'F ', 'MALE', 'FEMALE',
                                    'male', 'female', 'm', 'f']
                    
                    # Clean and validate each gender value
                    invalid_genders_found = []
                    for idx, row in employee_rows.iterrows():
                        if pd.isna(row['GENDER']):
                            continue
                        gender_val = str(row['GENDER']).strip().upper()
                        if gender_val not in valid_genders:
                            # Try to map common variations
                            if gender_val in ['MALE', 'M', 'पुरुष']:
                                continue
                            elif gender_val in ['FEMALE', 'F', 'महिला', 'स्त्री']:
                                continue
                            else:
                                invalid_genders_found.append(f"Row {row['SR.NO']}: '{row['GENDER']}'")
                    
                    if invalid_genders_found:
                        errors.append(f'Invalid gender values found: {", ".join(invalid_genders_found[:3])}. Use: Male/Female/M/F')
            
            if errors:
                return {'valid': False, 'error': '; '.join(errors)}
            
            return {'valid': True, 'error': '', 'column_map': column_map}
            
        except Exception as e:
            return {'valid': False, 'error': f'Data validation error: {str(e)}'}
    
    except Exception as e:
        return {'valid': False, 'error': f'Validation failed: {str(e)}'}
def read_attendance_data(file_path):
    """
    Read and clean attendance data from Excel file
    
    Args:
        file_path (str): Path to the uploaded Excel file
        
    Returns:
        pandas.DataFrame: Cleaned attendance data
    """
    try:
        # Read Excel file
        df = pd.read_excel(file_path)
        
        # Map columns to standard names
        column_map = map_columns(df.columns.tolist())
        
        # Rename columns
        df = df.rename(columns=column_map)
        
        # Ensure required columns exist after mapping
        for req_col in REQUIRED_COLUMNS:
            if req_col not in df.columns:
                raise ValueError(f'Required column {req_col} not found after mapping')
        
        # Clean data
        # Convert SR.NO to integer
        df['SR.NO'] = pd.to_numeric(df['SR.NO'], errors='coerce').fillna(0).astype(int)
        
        # Clean NAME OF EMPLOYEE
        df['NAME OF EMPLOYEE'] = df['NAME OF EMPLOYEE'].astype(str).str.strip()
        
        # Clean and standardize CATEGORY
        if 'CATEGORY' in df.columns:
            df['CATEGORY'] = df['CATEGORY'].astype(str).str.strip().str.upper()
            # Map common variations
            category_mapping = {
                'UNSKILLED': 'UNSKILLED',
                'UN-SKILLED': 'UNSKILLED',
                'UN SKILLED': 'UNSKILLED',
                'SEMI-SKILLED': 'SEMI-SKILLED',
                'SEMI SKILLED': 'SEMI-SKILLED',
                'SKILLED': 'SKILLED',
                'HIGH SKILLED': 'HIGH SKILLED',
                'HIGH-SKILLED': 'HIGH SKILLED'
            }
            df['CATEGORY'] = df['CATEGORY'].map(category_mapping).fillna('UNSKILLED')
        
        # Ensure WORKING DAYS and ATTENDANCE are numeric
        df['WORKING DAYS'] = pd.to_numeric(df['WORKING DAYS'], errors='coerce')
        df['ATTENDANCE'] = pd.to_numeric(df['ATTENDANCE'], errors='coerce')
        
        # Fill NaN values
        df['WORKING DAYS'] = df['WORKING DAYS'].fillna(0)
        df['ATTENDANCE'] = df['ATTENDANCE'].fillna(0)
        
        # Ensure ATTENDANCE doesn't exceed WORKING DAYS
        df['ATTENDANCE'] = df.apply(
            lambda row: min(row['ATTENDANCE'], row['WORKING DAYS']), 
            axis=1
        )
        
        # Standardize GENDER
        if 'GENDER' in df.columns:
            df['GENDER'] = df['GENDER'].astype(str).str.upper().str.strip()
            gender_mapping = {
                'MALE': 'MALE',
                'M': 'MALE',
                'पुरुष': 'MALE',
                'FEMALE': 'FEMALE',
                'F': 'FEMALE',
                'महिला': 'FEMALE',
                'स्त्री': 'FEMALE'
            }
            df['GENDER'] = df['GENDER'].map(gender_mapping).fillna('MALE')
        
        # Remove rows with invalid data
        df = df[
            (df['SR.NO'] > 0) &
            (df['NAME OF EMPLOYEE'].str.len() > 0) &
            (df['WORKING DAYS'] > 0)
        ].copy()
        
        # Reset index
        df.reset_index(drop=True, inplace=True)
        
        return df
    
    except Exception as e:
        raise Exception(f"Error reading attendance data: {str(e)}")