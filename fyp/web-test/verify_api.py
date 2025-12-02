#!/usr/bin/env python3
"""
Verification script for the Thesis Similarity Checker API
Tests the API's ability to access thesis files from the uploads folder
"""

import os
import requests
import json
from pathlib import Path

def test_health():
    """Test the health endpoint"""
    print("=" * 60)
    print("Testing Health Endpoint")
    print("=" * 60)
    try:
        response = requests.get('http://localhost:5000/health', timeout=5)
        if response.status_code == 200:
            data = response.json()
            print("✅ API is running!")
            print(f"Upload Path: {data.get('upload_path')}")
            print(f"Upload Dir Exists: {data.get('upload_dir_exists')}")
            return True
        else:
            print(f"❌ API returned status code: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Cannot connect to API. Make sure it's running on localhost:5000")
        return False
    except Exception as e:
        print(f"❌ Error: {e}")
        return False

def check_thesis_folder():
    """Check if thesis folder exists and has files"""
    print("\n" + "=" * 60)
    print("Checking Thesis Folder")
    print("=" * 60)
    
    # Current script is in c:\laragon\fyp\web-test\
    script_dir = os.path.dirname(os.path.abspath(__file__))
    thesis_dir = os.path.normpath(os.path.join(script_dir, '..', 'uploads', 'thesis'))
    
    print(f"Looking for thesis folder at: {thesis_dir}")
    
    if os.path.exists(thesis_dir):
        print("✅ Thesis folder exists!")
        files = os.listdir(thesis_dir)
        if files:
            print(f"📁 Found {len(files)} file(s):")
            for f in files:
                file_path = os.path.join(thesis_dir, f)
                file_size = os.path.getsize(file_path) / 1024  # Size in KB
                print(f"   - {f} ({file_size:.1f} KB)")
            return True
        else:
            print("⚠️  Thesis folder is empty (no existing files to compare)")
            return False
    else:
        print(f"❌ Thesis folder not found at {thesis_dir}")
        print(f"Creating the folder...")
        try:
            os.makedirs(thesis_dir, exist_ok=True)
            print(f"✅ Created folder: {thesis_dir}")
            return False  # Empty but now exists
        except Exception as e:
            print(f"❌ Failed to create folder: {e}")
            return False

def test_api_configuration():
    """Verify API configuration"""
    print("\n" + "=" * 60)
    print("API Configuration Check")
    print("=" * 60)
    
    # Check if Flask modules are available
    modules_to_check = ['flask', 'flask_cors', 'PyPDF2', 'docx', 'sentence_transformers']
    
    print("Checking required Python packages:")
    all_installed = True
    for module in modules_to_check:
        try:
            __import__(module)
            print(f"✅ {module}")
        except ImportError:
            print(f"❌ {module} - NOT INSTALLED")
            all_installed = False
    
    if not all_installed:
        print("\n⚠️  Some packages are missing. Install them with:")
        print("   pip install -r requirements.txt")
    
    return all_installed

def main():
    """Run all tests"""
    print("\n")
    print("╔" + "=" * 58 + "╗")
    print("║" + " " * 10 + "THESIS SIMILARITY API - VERIFICATION SCRIPT" + " " * 5 + "║")
    print("╚" + "=" * 58 + "╝")
    
    # Test 1: Configuration
    config_ok = test_api_configuration()
    
    # Test 2: Folder structure
    folder_ok = check_thesis_folder()
    
    # Test 3: Health endpoint (requires API running)
    health_ok = test_health()
    
    # Summary
    print("\n" + "=" * 60)
    print("VERIFICATION SUMMARY")
    print("=" * 60)
    print(f"Configuration: {'✅ PASS' if config_ok else '⚠️  FAIL - Missing packages'}")
    print(f"Thesis Folder: {'✅ PASS' if folder_ok else '⚠️  EMPTY - No files yet'}")
    print(f"API Running: {'✅ PASS' if health_ok else '❌ FAIL - API not running'}")
    
    print("\n" + "=" * 60)
    print("NEXT STEPS")
    print("=" * 60)
    
    if not config_ok:
        print("1. Install dependencies:")
        print("   pip install -r requirements.txt")
    
    if health_ok:
        print("✅ API is running and ready!")
        print("\nThe API will now:")
        print("  • Read thesis files from uploads/thesis/ folder")
        print("  • Compare uploaded thesis with existing files")
        print("  • Calculate similarity percentage")
        print("  • Return results to Dashboard.php")
    else:
        print("2. Start the Flask API:")
        print("   python simple_app.py")
    
    print("\nFor debugging, check the console output of the Flask API for:")
    print("  • File path resolution logs")
    print("  • Text extraction status")
    print("  • Similarity calculation results")
    print("\n")

if __name__ == '__main__':
    main()
