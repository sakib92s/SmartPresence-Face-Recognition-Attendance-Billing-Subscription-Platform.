"""
Simple script to run the Flask wage muster generator
"""
import os
import sys
import time

# Add current directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

try:
    from app import app
    
    if __name__ == '__main__':
        # Create required folders
        for folder in ['uploads', 'generated', 'templates']:
            folder_path = os.path.join(os.path.dirname(__file__), folder)
            if not os.path.exists(folder_path):
                os.makedirs(folder_path)
        
        # Clean up old files before starting
        app.cleanup_old_files()
        
        print("=" * 60)
        print("Wage Muster Generator - Flask Server")
        print("=" * 60)
        print(f"URL: http://127.0.0.1:5000")
        print(f"Press Ctrl+C to stop the server")
        print("=" * 60)
        
        app.run(debug=False, host='127.0.0.1', port=5000, use_reloader=False)
        
except Exception as e:
    print(f"Error starting Flask server: {e}")
    print("Make sure all required packages are installed:")
    print("pip install flask pandas openpyxl")
    input("Press Enter to exit...")