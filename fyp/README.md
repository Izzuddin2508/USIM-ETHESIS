# USIM Thesis Management System

A web-based platform designed for Universiti Sains Islam Malaysia (USIM) to streamline thesis submission, review, and tracking. Features include AI-powered similarity checking, file management, user authentication, and secure document handling.

## Features

- **AI-Powered Similarity Checking**: Advanced semantic similarity analysis using sentence transformers
- **Multi-format Support**: PDF, DOCX, and TXT file processing
- **Real-time Processing**: Automatic similarity checking on file selection
- **Role-based Access**: Student, Supervisor, and Admin dashboards
- **Secure File Management**: Organized file storage and retrieval

## System Requirements

### Server Requirements
- **Web Server**: Apache/Nginx with PHP support
- **PHP**: Version 7.4 or higher
- **Python**: Version 3.7 or higher
- **Database**: MySQL/MariaDB

### Python Dependencies
- Flask
- Flask-CORS
- PyPDF2
- python-docx
- sentence-transformers
- torch
- numpy

## Installation Guide

### 1. Clone the Repository
```bash
git clone <repository-url>
cd www
```

### 2. PHP Setup
Ensure your web server (Apache/Nginx) is configured to serve PHP files from the project directory.

### 3. Python Environment Setup

#### Option A: Using pip (Recommended)
```bash
# Create virtual environment (optional but recommended)
python -m venv thesis_env

# Activate virtual environment
# On Windows:
thesis_env\Scripts\activate
# On macOS/Linux:
source thesis_env/bin/activate

# Install required packages
pip install flask flask-cors PyPDF2 python-docx sentence-transformers torch numpy
```

#### Option B: Using requirements.txt
Create a `requirements.txt` file with the following content:
```txt
flask==2.3.3
flask-cors==4.0.0
PyPDF2==3.0.1
python-docx==0.8.11
sentence-transformers==2.2.2
torch>=1.9.0
numpy>=1.21.0
```

Then install:
```bash
pip install -r requirements.txt
```

### 4. Database Setup
1. Import the database schema:
```sql
mysql -u your_username -p your_database < fyp_database.sql
```

2. Update database configuration in `Configuration.php`:
```php
$servername = "localhost";
$username = "your_db_username";
$password = "your_db_password";
$dbname = "your_database_name";
```

### 5. Directory Permissions
Ensure the uploads directory has write permissions:
```bash
# On Windows (PowerShell as Administrator):
icacls "uploads" /grant Everyone:F

# On macOS/Linux:
chmod 755 uploads/
chmod 755 uploads/thesis/
```

### 6. Start the Python API Server
```bash
# Navigate to the web-test directory
cd web-test

# Start the similarity checking API
python simple_app.py
```

The API server will start on `http://localhost:5000`

### 7. Configure Web Server
Ensure your web server is running and serving the PHP files. The main entry points are:
- `index.php` - Main landing page
- `Admin-login.php` - Admin login
- `Login-page.php` - Student/Supervisor login
- `Dashboard.php` - Student dashboard with similarity checking

## Usage

### For Students
1. Access the student login page
2. Log in with your credentials
3. Navigate to the thesis submission section
4. Select your thesis file (PDF, DOCX, or TXT)
5. The system will automatically check similarity with existing theses
6. Review the similarity percentage and submit your thesis

### For Administrators
1. Access the admin login page
2. Log in with admin credentials
3. Manage users, review submissions, and monitor system activity

## API Endpoints

### Similarity Checking API
- **URL**: `http://localhost:5000/api/check_similarity`
- **Method**: POST
- **Parameters**: 
  - `file`: The thesis file to check
  - `existing_files`: JSON array of existing file paths
- **Response**: JSON with similarity percentage

### Health Check
- **URL**: `http://localhost:5000/health`
- **Method**: GET
- **Response**: Server status

## Troubleshooting

### Common Issues

1. **Python server not starting**
   - Check if all dependencies are installed
   - Verify Python version (3.7+)
   - Check port 5000 is not in use

2. **Similarity checking not working**
   - Ensure Python server is running on localhost:5000
   - Check file permissions for uploads directory
   - Verify CORS is properly configured

3. **File upload issues**
   - Check directory permissions
   - Verify file size limits in PHP configuration
   - Ensure supported file formats (PDF, DOCX, TXT)

### Error Messages
- **"Model loading failed"**: Install sentence-transformers package
- **"CORS error"**: Check Flask-CORS installation and configuration
- **"File not found"**: Verify uploads directory structure

## Development

### Project Structure
```
www/
├── Dashboard.php              # Student dashboard
├── Admin-dashboard.php        # Admin interface
├── similarity_checker.php     # PHP API proxy
├── web-test/
│   ├── simple_app.py         # Python similarity API
│   └── templates/
├── uploads/
│   └── thesis/               # Thesis file storage
└── img/                      # Static assets
```

### Adding New Features
1. For PHP backend: Modify relevant PHP files
2. For Python API: Update `simple_app.py`
3. For frontend: Modify JavaScript in dashboard files

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## Support
For technical support or questions, please contact the development team.