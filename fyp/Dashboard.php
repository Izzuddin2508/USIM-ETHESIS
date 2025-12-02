<?php
session_start();
include 'Configuration.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login-page.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Initialize variables
$user_data = array();
$is_student = false;
$is_supervisor = false;
$submission_message = '';

// Handle thesis submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_thesis'])) {
    $title = $_POST['thesis_title'];
    $file = $_FILES['thesis_file'];
    
    // Validate file
    $allowed_types = ['pdf', 'doc', 'docx'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        $submission_message = '<div style="color: red;">Only PDF, DOC, and DOCX files are allowed!</div>';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        $submission_message = '<div style="color: red;">File size must be less than 10MB!</div>';
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/thesis/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $filename = $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Get supervisor number for this student
            $supervisor_query = "SELECT supervisor_number FROM student WHERE user_id = '$user_id'";
            $supervisor_result = mysqli_query($conn, $supervisor_query);
            $supervisor_data = mysqli_fetch_assoc($supervisor_result);
            $supervisor_number = $supervisor_data['supervisor_number'] ?? null;
            // Get student_number for the current user
            $student_number_query = "SELECT student_number FROM student WHERE user_id = '$user_id'";
            $student_number_result = mysqli_query($conn, $student_number_query);
            $student_data = mysqli_fetch_assoc($student_number_result);
            $student_number = $student_data['student_number'];
            
            // Check if this is a resubmission
            $is_resubmission = isset($_POST['is_resubmission']) && $_POST['is_resubmission'] == '1';
            $original_thesis_id = isset($_POST['original_thesis_id']) ? $_POST['original_thesis_id'] : null;
            
            if ($is_resubmission && $original_thesis_id) {
                // For resubmissions, add version tracking information
                $version_query = "SELECT COUNT(*) as version_count FROM thesis 
                                WHERE student_number = '$student_number' 
                                AND thesis_title = '" . mysqli_real_escape_string($conn, $title) . "'";
                $version_result = mysqli_query($conn, $version_query);
                $version_data = mysqli_fetch_assoc($version_result);
                $version_number = $version_data['version_count'] + 1;
                
                // Insert new version with version tracking
                $insert_query = "INSERT INTO thesis (thesis_title, thesis_path, student_number, supervisor_number, thesis_remark) 
                               VALUES ('" . mysqli_real_escape_string($conn, $title) . " (v$version_number)', 
                                       '$file_path', '$student_number', " . 
                                       ($supervisor_number ? "'$supervisor_number'" : 'NULL') . ", 
                                       'Resubmission of thesis ID: $original_thesis_id')";
                
                $success_message = "Revised thesis submitted successfully! (Version $version_number)";
            } else {
                // Regular new submission
                $insert_query = "INSERT INTO thesis (thesis_title, thesis_path, student_number, supervisor_number, thesis_remark) 
                               VALUES ('" . mysqli_real_escape_string($conn, $title) . "', '$file_path', '$student_number', " . 
                               ($supervisor_number ? "'$supervisor_number'" : 'NULL') . ", '')";
                
                $success_message = "Thesis submitted successfully!";
            }
            
            if (mysqli_query($conn, $insert_query)) {
                $submission_message = '<div style="color: green;">' . $success_message . '</div>';
            } else {
                $submission_message = '<div style="color: red;">Error submitting thesis: ' . mysqli_error($conn) . '</div>';
            }
        } else {
            $submission_message = '<div style="color: red;">Error uploading file!</div>';
        }
    }
}

// Handle profile update
$profile_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    if ($user_role == 'student') {
        $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
        $student_faculty = mysqli_real_escape_string($conn, $_POST['student_faculty']);
        $student_program = mysqli_real_escape_string($conn, $_POST['student_program']);
        $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
        
        // Update user table
        $update_user = "UPDATE user SET user_email='$user_email' WHERE user_id='$user_id'";
        $user_result = mysqli_query($conn, $update_user);
        
        // Update student table
        $update_student = "UPDATE student SET 
                          student_name='$student_name', 
                          student_faculty='$student_faculty', 
                          student_program='$student_program' 
                          WHERE user_id='$user_id'";
        $student_result = mysqli_query($conn, $update_student);
        
        if ($user_result && $student_result) {
            $profile_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">Profile updated successfully!</div>';
        } else {
            $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Error updating profile: ' . mysqli_error($conn) . '</div>';
        }
        
    } elseif ($user_role == 'supervisor') {
        $supervisor_name = mysqli_real_escape_string($conn, $_POST['supervisor_name']);
        $supervisor_faculty = mysqli_real_escape_string($conn, $_POST['supervisor_faculty']);
        $supervisor_department = mysqli_real_escape_string($conn, $_POST['supervisor_department']);
        $user_email = mysqli_real_escape_string($conn, $_POST['user_email']);
        
        // Update user table
        $update_user = "UPDATE user SET user_email='$user_email' WHERE user_id='$user_id'";
        $user_result = mysqli_query($conn, $update_user);
        
        // Update supervisor table
        $update_supervisor = "UPDATE supervisor SET 
                             supervisor_name='$supervisor_name', 
                             supervisor_faculty='$supervisor_faculty', 
                             supervisor_department='$supervisor_department' 
                             WHERE user_id='$user_id'";
        $supervisor_result = mysqli_query($conn, $update_supervisor);
        
        if ($user_result && $supervisor_result) {
            $profile_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">Profile updated successfully!</div>';
        } else {
            $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Error updating profile: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Hash the current password for comparison
    $hashed_current_password = md5($current_password);
    
    // Verify current password
    $verify_query = "SELECT user_password FROM user WHERE user_id='$user_id'";
    $verify_result = mysqli_query($conn, $verify_query);
    $password_data = mysqli_fetch_assoc($verify_result);
    
    if ($password_data['user_password'] !== $hashed_current_password) {
        $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Current password is incorrect!</div>';
    } elseif ($new_password !== $confirm_password) {
        $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">New passwords do not match!</div>';
    } elseif (strlen($new_password) < 6) {
        $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Password must be at least 6 characters long!</div>';
    } else {
        // Hash the new password before saving
        $hashed_new_password = md5($new_password);
        $update_password = "UPDATE user SET user_password='$hashed_new_password' WHERE user_id='$user_id'";
        if (mysqli_query($conn, $update_password)) {
            $profile_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">Password changed successfully!</div>';
        } else {
            $profile_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Error changing password: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Initialize student management message
$student_message = '';

// Handle adding student under supervision (for supervisors only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student']) && !$is_student) {
    $student_user_id = trim($_POST['student_user_id']);
    
    // First, check if the student_user_id exists in the user table and is a student
    $check_student_query = "SELECT u.user_id, u.user_role, s.student_name, s.student_faculty, s.student_program, s.student_number
                           FROM user u 
                           LEFT JOIN student s ON u.user_id = s.user_id 
                           WHERE u.user_id = '$student_user_id' AND u.user_role = 'student'";
    $check_result = mysqli_query($conn, $check_student_query);
    
    if (mysqli_num_rows($check_result) == 0) {
        $student_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Student with ID "' . htmlspecialchars($student_user_id) . '" not found or is not a student!</div>';
    } else {
        $student_data = mysqli_fetch_assoc($check_result);
        
        // Get current supervisor's supervisor_number
        $supervisor_query = "SELECT supervisor_number FROM supervisor WHERE user_id = '$user_id'";
        $supervisor_result = mysqli_query($conn, $supervisor_query);
        $supervisor_data = mysqli_fetch_assoc($supervisor_result);
        $supervisor_number = $supervisor_data['supervisor_number'];
        
        // Check if student is already assigned to this supervisor
        $check_assignment_query = "SELECT * FROM student WHERE user_id = '$student_user_id' AND supervisor_number = '$supervisor_number'";
        $assignment_result = mysqli_query($conn, $check_assignment_query);
        
        if (mysqli_num_rows($assignment_result) > 0) {
            $student_message = '<div style="color: orange; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin-bottom: 15px;">Student "' . htmlspecialchars($student_data['student_name']) . '" is already under your supervision!</div>';
        } else {
            // Update the student record to assign this supervisor
            $assign_query = "UPDATE student SET supervisor_number = '$supervisor_number' WHERE user_id = '$student_user_id'";
            
            if (mysqli_query($conn, $assign_query)) {
                $student_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">Student "' . htmlspecialchars($student_data['student_name']) . '" has been successfully assigned under your supervision!</div>';
            } else {
                $student_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">Error assigning student: ' . mysqli_error($conn) . '</div>';
            }
        }
    }
}

// Handle publication consent (for students only)
$publication_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_publication_consent']) && $user_role == 'student') {
    $thesis_id = $_POST['thesis_id'];
    $consent = isset($_POST['allow_publication']) ? 1 : 0;
    
    // Get thesis path before updating
    $thesis_path_query = "SELECT thesis_path, thesis_title FROM thesis WHERE thesis_id = '$thesis_id'";
    $thesis_path_result = mysqli_query($conn, $thesis_path_query);
    $thesis_data = mysqli_fetch_assoc($thesis_path_result);
    $old_thesis_path = $thesis_data['thesis_path'];
    $thesis_title = $thesis_data['thesis_title'];
    
    // If consent is given, move file to approved folder
    if ($consent == 1) {
        // Create approved directory if it doesn't exist
        $approved_dir = 'uploads/approved/';
        if (!file_exists($approved_dir)) {
            mkdir($approved_dir, 0777, true);
        }
        
        // Get filename from path
        $filename = basename($old_thesis_path);
        $new_thesis_path = $approved_dir . $filename;
        
        // Move file from thesis folder to approved folder
        if (file_exists($old_thesis_path)) {
            if (rename($old_thesis_path, $new_thesis_path)) {
                // File moved successfully, update database with new path
                $consent_query = "UPDATE thesis SET 
                                 allow_publication = '1',
                                 publication_consent = 'approved',
                                 consent_date = NOW(),
                                 thesis_path = '$new_thesis_path'
                                 WHERE thesis_id = '$thesis_id'";
                
                if (mysqli_query($conn, $consent_query)) {
                    $publication_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">✅ You have approved the publication of your thesis. The document has been moved to the approved archive.</div>';
                } else {
                    // Revert file move if database update fails
                    rename($new_thesis_path, $old_thesis_path);
                    $publication_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">❌ Error saving your consent: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                $publication_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">❌ Error moving file to approved folder. Please try again.</div>';
            }
        } else {
            $publication_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">❌ Thesis file not found. Please contact administrator.</div>';
        }
    } else {
        // Consent not given - just update database
        $consent_query = "UPDATE thesis SET 
                         allow_publication = '0',
                         publication_consent = 'declined',
                         consent_date = NOW()
                         WHERE thesis_id = '$thesis_id'";
        
        if (mysqli_query($conn, $consent_query)) {
            $publication_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">✅ You have declined the publication of your thesis. Your document will remain private.</div>';
        } else {
            $publication_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">❌ Error saving your consent: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Initialize review message
$review_message = '';

// Handle review submission (for supervisors only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review']) && $user_role == 'supervisor') {
    $thesis_id = $_POST['thesis_id'];
    $review_status = $_POST['review_status'];
    $reviewer_remarks = mysqli_real_escape_string($conn, $_POST['reviewer_remarks']);
    
    // Update thesis with review details
    $review_query = "UPDATE thesis SET 
                     review_status = '$review_status',
                     reviewer_remarks = '$reviewer_remarks',
                     review_date = NOW()
                     WHERE thesis_id = '$thesis_id'";
    
    if (mysqli_query($conn, $review_query)) {
        $status_text = match($review_status) {
            'approved' => 'approved ✅',
            'revision_required' => 'marked as requiring revision 📝',
            'failed' => 'marked as failed ❌',
            default => 'reviewed'
        };
        $review_message = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">✅ Review submitted successfully! The thesis has been ' . $status_text . '.</div>';
    } else {
        $review_message = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px;">❌ Error submitting review: ' . mysqli_error($conn) . '</div>';
    }
}

// Fetch user data based on role
if ($user_role == 'student') {
    $is_student = true;
    $query = "
        SELECT 
            s.student_name, 
            s.student_faculty, 
            s.student_program, 
            u.user_email,
            sup.supervisor_name
        FROM student s
        INNER JOIN user u ON s.user_id = u.user_id
        LEFT JOIN supervisor sup ON s.supervisor_number = sup.supervisor_number
        WHERE s.user_id = '$user_id'
    ";
} elseif ($user_role == 'supervisor') {
    $is_supervisor = true;
    $query = "
        SELECT 
            s.supervisor_name, 
            s.supervisor_faculty, 
            s.supervisor_department, 
            u.user_email
        FROM supervisor s
        INNER JOIN user u ON s.user_id = u.user_id
        WHERE s.user_id = '$user_id'
    ";
} else {
    echo "<h3>Invalid user role: $user_role</h3>";
    exit();
}

$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
} else {
    echo "<h3>No data found for user ID: $user_id</h3>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($user_role); ?> Dashboard - USIM eThesis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .logo-section {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo-section img {
            width: 80px;
            height: auto;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .logo-section h3 {
            font-size: 16px;
            font-weight: 300;
            opacity: 0.9;
        }

        .user-info {
            padding: 20px;
            background: rgba(255,255,255,0.1);
            margin: 0 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .user-info p {
            margin-bottom: 8px;
            font-size: 13px;
            opacity: 0.9;
        }

        .user-info strong {
            color: #fff;
        }

        /* Navigation Tabs */
        .nav-tabs {
            flex: 1;
            padding: 0 15px;
        }

        .nav-tab {
            display: block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 15px 20px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
        }

        .nav-tab:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-tab.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 500;
        }

        .nav-tab i {
            margin-right: 10px;
            width: 16px;
        }

        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            height: 100vh;
            overflow-y: auto;
            background: white;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #2a5298;
            font-size: 24px;
            font-weight: 300;
        }

        .header p {
            color: #6c757d;
            margin-top: 5px;
        }

        .content-area {
            padding: 30px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h3 {
            color: #2a5298;
            font-size: 18px;
            font-weight: 500;
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 2px rgba(42, 82, 152, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #2a5298;
            color: white;
        }

        .btn-primary:hover {
            background: #1e3c72;
        }

        /* Dummy Content Styles */
        .dummy-content {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .dummy-content h3 {
            margin-bottom: 15px;
            color: #495057;
        }

        .dummy-content p {
            margin-bottom: 20px;
        }

        .coming-soon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .feature-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .feature-item h4 {
            color: #2a5298;
            margin-bottom: 10px;
        }

        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #495057;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-revision {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>

    <script>
        // Force URL hash for reload safety
        if (!window.location.hash) {
            window.location = window.location + '#';
            window.location.reload();
        }

        // Tab functionality
        function showTab(tabName) {
            // Hide all content sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            if (event && event.target) {
                event.target.classList.add('active');
            }
            
            // Update URL to maintain section state (without refreshing)
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('section', tabName);
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, '', newUrl);
        }

        // Greeting message
        window.onload = function() {
            const hours = new Date().getHours();
            let greeting = "";

            if (hours < 12) greeting = "Good Morning";
            else if (hours < 18) greeting = "Good Afternoon";
            else greeting = "Good Evening";

            const userName = "<?php echo $is_student ? addslashes($user_data['student_name']) : addslashes($user_data['supervisor_name']); ?>";
            document.getElementById("greeting").innerText = greeting + ", " + userName + "!";
            
            // Check if there's a section parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeSection = urlParams.get('section');
            
            if (activeSection) {
                // Show the specified section
                showTab(activeSection);
                const targetTab = document.querySelector(`[onclick*="${activeSection}"]`);
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            } else {
                // Show first tab by default
                <?php if ($is_student): ?>
                showTab('submit-section');
                document.querySelector('[onclick*="submit-section"]').classList.add('active');
                <?php else: ?>
                showTab('review-section');
                document.querySelector('[onclick*="review-section"]').classList.add('active');
                <?php endif; ?>
            }
        };
    </script>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-section">
            <img src="img/Logo-usim.png" alt="USIM Logo">
            <h3>eThesis System</h3>
        </div>

        <div class="user-info">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
            <p><strong>Name:</strong> 
                <?php 
                if ($is_student) {
                    echo htmlspecialchars($user_data['student_name']);
                } else {
                    echo htmlspecialchars($user_data['supervisor_name']);
                }
                ?>
            </p>
            
            <?php if ($is_student): ?>
                <p><strong>Program:</strong> <?php echo htmlspecialchars($user_data['student_program']); ?></p>
                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($user_data['student_faculty']); ?></p>
                <p><strong>Supervisor:</strong> 
                    <?php echo !empty($user_data['supervisor_name']) ? htmlspecialchars($user_data['supervisor_name']) : 'Not Assigned'; ?>
                </p>
            <?php else: ?>
                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($user_data['supervisor_faculty']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($user_data['supervisor_department']); ?></p>
            <?php endif; ?>
        </div>

        <div class="nav-tabs">
            <?php if ($is_student): ?>
                <!-- Student Navigation -->
                <button class="nav-tab" onclick="showTab('submit-section')">
                    <i>📄</i> Submit
                </button>
                <button class="nav-tab" onclick="showTab('track-section')">
                    <i>📊</i> Track
                </button>
                <button class="nav-tab" onclick="showTab('database-section')">
                    <i>📚</i> Database
                </button>
                <button class="nav-tab" onclick="showTab('profile-section')">
                    <i>👤</i> Edit Profile
                </button>
            <?php else: ?>
                <!-- Supervisor Navigation -->
                <button class="nav-tab" onclick="showTab('review-section')">
                    <i>📝</i> Review
                </button>
                <button class="nav-tab" onclick="showTab('students-section')">
                    <i>👥</i> Students
                </button>
                <button class="nav-tab" onclick="showTab('database-section')">
                    <i>📚</i> Database
                </button>
                <button class="nav-tab" onclick="showTab('reports-section')">
                    <i>📈</i> Reports
                </button>
                <button class="nav-tab" onclick="showTab('profile-section')">
                    <i>👤</i> Edit Profile
                </button>
            <?php endif; ?>
        </div>

        <div class="logout-section">
            <form method="POST" action="logout.php">
                <button type="submit" name="logout" class="logout-btn">
                    🚪 Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1 id="greeting"></h1>
            <p>Welcome to your USIM eThesis <?php echo ucfirst($user_role); ?> Dashboard.</p>
        </div>

        <div class="content-area">
            <?php if ($is_student): ?>
                
                <!-- Submit Section -->
                <div id="submit-section" class="content-section">
                    <?php
                    // Check if student has any thesis requiring revision or failed
                    $check_latest_query = "SELECT thesis_id, thesis_title, review_status, reviewer_remarks 
                                         FROM thesis 
                                         WHERE student_number = (SELECT student_number FROM student WHERE user_id = '$user_id') 
                                         ORDER BY thesis_id DESC LIMIT 1";
                    $check_latest_result = mysqli_query($conn, $check_latest_query);
                    $latest_submission = mysqli_fetch_assoc($check_latest_result);
                    
                    $can_submit_new = true;
                    $needs_resubmission = false;
                    
                    if ($latest_submission) {
                        if (in_array($latest_submission['review_status'], ['revision_required', 'failed'])) {
                            $needs_resubmission = true;
                        } elseif ($latest_submission['review_status'] == 'pending') {
                            $can_submit_new = false; // Don't allow new submission if there's a pending one
                        }
                    }
                    ?>
                    
                    <?php if ($needs_resubmission): ?>
                    <!-- Resubmission Notice -->
                    <div class="card" style="border-left: 4px solid #17a2b8;">
                        <div class="card-header" style="background-color: #d1ecf1; color: #0c5460;">
                            <h3>📝 Resubmission Required</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Your latest thesis submission requires attention:</strong></p>
                            <p><strong>Title:</strong> <?php echo htmlspecialchars($latest_submission['thesis_title']); ?></p>
                            <p><strong>Status:</strong> 
                                <?php if($latest_submission['review_status'] == 'revision_required'): ?>
                                    <span class="status-badge status-revision">📝 Revision Required</span>
                                <?php else: ?>
                                    <span class="status-badge status-failed">❌ Failed</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($latest_submission['reviewer_remarks'])): ?>
                            <p><strong>Reviewer Comments:</strong></p>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                <?php echo nl2br(htmlspecialchars($latest_submission['reviewer_remarks'])); ?>
                            </div>
                            <?php endif; ?>
                            <p style="color: #0c5460; font-weight: 500;">Please submit a revised version of your thesis below. Your previous submission will be kept for reference.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($can_submit_new || $needs_resubmission): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo $needs_resubmission ? ' Submit Revised Thesis' : '📄 Thesis Submission'; ?></h3>
                        </div>
                        <div class="card-body">
                            <?php echo $submission_message; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data" id="thesisForm">
                                <div class="form-group">
                                    <label for="thesis_title">Thesis Title:</label>
                                    <input type="text" id="thesis_title" name="thesis_title" class="form-control" 
                                           placeholder="Enter your thesis title" 
                                           value="<?php echo $needs_resubmission ? htmlspecialchars($latest_submission['thesis_title']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="thesis_file">Thesis File:</label>
                                    <input type="file" id="thesis_file" name="thesis_file" class="form-control" 
                                           accept=".pdf,.doc,.docx" required onchange="handleFileChange()">
                                    <small style="color: #666;">Accepted formats: PDF, DOC, DOCX (Max size: 10MB)</small>
                                    
                                    <div style="background: #e9f7fe; padding: 10px; border-radius: 5px; margin-top: 10px; font-size: 12px;">
                                        <strong>🤖 Automatic AI Similarity Check</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px;">
 
                                        </ul>
                                    </div>
                                </div>

                                <!-- Similarity Results -->
                                <div id="similarityResults" style="display: none; margin: 20px 0;">
                                    <div id="similarityContent"></div>
                                </div>

                                <!-- Loading Indicator -->
                                <div id="loadingIndicator" style="display: none; text-align: center; margin: 20px 0;">
                                    <div style="color: #007bff;">
                                        <i class="fa fa-spinner fa-spin" style="font-size: 20px;"></i>
                                        <p style="margin-top: 10px;">Checking similarity automatically... Please wait.</p>
                                    </div>
                                </div>
                                
                                <?php if ($needs_resubmission): ?>
                                <input type="hidden" name="is_resubmission" value="1">
                                <input type="hidden" name="original_thesis_id" value="<?php echo $latest_submission['thesis_id']; ?>">
                                <?php endif; ?>
                                
                                <button type="submit" name="submit_thesis" id="submitBtn" class="btn btn-primary" disabled>
                                    <?php echo $needs_resubmission ? '📝 Submit Revised Thesis' : '📤 Submit Thesis'; ?>
                                </button>
                                <small id="submitHint" style="color: #666; margin-left: 10px;">Please select a file to begin automatic similarity check</small>
                            </form>
                        </div>
                    </div>
                    <?php elseif (!$can_submit_new): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📄 Thesis Submission</h3>
                        </div>
                        <div class="card-body">
                            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; text-align: center;">
                                <h4>⏳ Submission Under Review</h4>
                                <p>You have a thesis submission currently under review. Please wait for the review to complete before submitting a new thesis.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Submission History -->
                    <?php
                    $submissions_query = "SELECT * FROM thesis WHERE student_number = (SELECT student_number FROM student WHERE user_id = '$user_id') ORDER BY thesis_id DESC";
                    $submissions_result = mysqli_query($conn, $submissions_query);
                    if (mysqli_num_rows($submissions_result) > 0):
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 Submission History</h3>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Submitted</th>
                                        <th>File</th>
                                        <th>Status</th>
                                        <th>Review Date</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($submission = mysqli_fetch_assoc($submissions_result)): ?>
                                    <tr>
                                        <td style="min-width: 200px;">
                                            <strong><?php echo htmlspecialchars($submission['thesis_title']); ?></strong>
                                            <br><small style="color: #666;">ID: <?php echo $submission['thesis_id']; ?></small>
                                        </td>
                                        <td style="min-width: 120px;">
                                            <?php echo date('M j, Y', strtotime($submission['submission_date'])); ?>
                                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($submission['submission_date'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($submission['thesis_path']); ?>" 
                                               target="_blank" class="btn" style="padding: 4px 8px; font-size: 12px;">
                                                📄 Download
                                            </a>
                                        </td>
                                        <td>
                                            <?php
                                            switch($submission['review_status']) {
                                                case 'pending':
                                                    echo '<span class="status-badge status-pending">⏳ Pending Review</span>';
                                                    break;
                                                case 'approved':
                                                    echo '<span class="status-badge status-approved">✅ Approved</span>';
                                                    break;
                                                case 'failed':
                                                    echo '<span class="status-badge status-failed">❌ Failed</span>';
                                                    break;
                                                case 'revision_required':
                                                    echo '<span class="status-badge status-revision">📝 Revision Required</span>';
                                                    break;
                                                default:
                                                    echo '<span class="status-badge status-pending">⏳ Pending Review</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="min-width: 120px;">
                                            <?php 
                                            if ($submission['review_date']) {
                                                echo date('M j, Y', strtotime($submission['review_date']));
                                                echo '<br><small style="color: #666;">' . date('g:i A', strtotime($submission['review_date'])) . '</small>';
                                            } else {
                                                echo '<span style="color: #999;">Not reviewed</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="max-width: 300px;">
                                            <?php if (!empty($submission['reviewer_remarks'])): ?>
                                                <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 13px;">
                                                    <?php echo nl2br(htmlspecialchars($submission['reviewer_remarks'])); ?>
                                                </div>
                                            <?php elseif (!empty($submission['thesis_remark'])): ?>
                                                <small style="color: #666;"><?php echo htmlspecialchars($submission['thesis_remark']); ?></small>
                                            <?php else: ?>
                                                <span style="color: #999;">No comments yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Publication Consent Section -->
                    <?php
                    // Check for approved thesis waiting for publication consent
                    $consent_query = "SELECT thesis_id, thesis_title, review_date, allow_publication, publication_consent 
                                    FROM thesis 
                                    WHERE student_number = (SELECT student_number FROM student WHERE user_id = '$user_id')
                                    AND review_status = 'approved'
                                    AND (publication_consent = 'pending' OR publication_consent IS NULL)
                                    ORDER BY review_date DESC";
                    $consent_result = mysqli_query($conn, $consent_query);
                    $pending_consent = mysqli_fetch_assoc($consent_result);
                    
                    if ($pending_consent):
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>✅ Publication Consent</h3>
                        </div>
                        <div class="card-body">
                            <p style="margin-bottom: 20px; color: #333;">Your thesis "<strong><?php echo htmlspecialchars($pending_consent['thesis_title']); ?></strong>" has been approved. Would you like to publish it in the university repository?</p>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="thesis_id" value="<?php echo $pending_consent['thesis_id']; ?>">
                                <input type="hidden" name="submit_publication_consent" value="1">
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                                        <input type="checkbox" name="allow_publication" value="1" style="width: 18px; height: 18px; margin-right: 10px; cursor: pointer;">
                                        <span style="cursor: pointer;">Yes, publish my thesis in the repository</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    Submit Consent
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php echo $publication_message; ?>
                </div>

                <!-- Track Section -->
                <div id="track-section" class="content-section">
                    <?php
                    // Get submission history for current student
                    $track_query = "SELECT 
                                      t.thesis_id,
                                      t.thesis_title,
                                      t.thesis_path,
                                      t.submission_date,
                                      t.review_status,
                                      t.review_date,
                                      t.reviewer_remarks,
                                      t.thesis_remark,
                                      st.student_name,
                                      st.student_number,
                                      sp.supervisor_name
                                    FROM thesis t
                                    LEFT JOIN student st ON t.student_number = st.student_number
                                    LEFT JOIN supervisor sp ON t.supervisor_number = sp.supervisor_number
                                    WHERE st.user_id = '$user_id'
                                    ORDER BY t.submission_date ASC";
                    
                    $track_result = mysqli_query($conn, $track_query);
                    $submissions = [];
                    $timeline_data = [];
                    $status_counts = ['pending' => 0, 'approved' => 0, 'failed' => 0, 'revision_required' => 0];
                    
                    while ($row = mysqli_fetch_assoc($track_result)) {
                        $submissions[] = $row;
                        $status_counts[$row['review_status']]++;
                        
                        // Create timeline data for chart
                        $submission_date = strtotime($row['submission_date']);
                        $review_date = $row['review_date'] ? strtotime($row['review_date']) : null;
                        
                        // Status values for line graph (0=pending, 1=revision, 2=failed, 3=approved)
                        $status_value = match($row['review_status']) {
                            'pending' => 0,
                            'revision_required' => 1,
                            'failed' => 2,
                            'approved' => 3,
                            default => 0
                        };
                        
                        $timeline_data[] = [
                            'x' => date('Y-m-d', $submission_date),
                            'y' => $status_value,
                            'title' => $row['thesis_title'],
                            'status' => $row['review_status'],
                            'id' => $row['thesis_id']
                        ];
                    }
                    
                    $total_submissions = count($submissions);
                    $latest_submission = $total_submissions > 0 ? $submissions[$total_submissions - 1] : null;
                    ?>

                    <!-- Progress Overview Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 20px;">
                                <h3 style="margin: 0; font-size: 2em; color: #2a5298;"><?php echo $total_submissions; ?></h3>
                                <p style="margin: 8px 0 0 0; color: #666;">📄 Total Submissions</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 20px;">
                                <h3 style="margin: 0; font-size: 2em; color: #28a745;"><?php echo $status_counts['approved']; ?></h3>
                                <p style="margin: 8px 0 0 0; color: #666;">✅ Approved</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 20px;">
                                <h3 style="margin: 0; font-size: 2em; color: #17a2b8;"><?php echo $status_counts['revision_required']; ?></h3>
                                <p style="margin: 8px 0 0 0; color: #666;">📝 Revisions</p>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 20px;">
                                <h3 style="margin: 0; font-size: 2em; color: #ffc107;"><?php echo $status_counts['pending']; ?></h3>
                                <p style="margin: 8px 0 0 0; color: #666;">⏳ Pending</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($total_submissions > 0): ?>
                    <!-- Current Status -->
                    <?php if ($latest_submission): ?>
                    <div class="card" style="border-left: 4px solid <?php 
                        echo match($latest_submission['review_status']) {
                            'approved' => '#28a745',
                            'failed' => '#dc3545', 
                            'revision_required' => '#17a2b8',
                            'pending' => '#ffc107',
                            default => '#6c757d'
                        };
                    ?>;">
                        <div class="card-header">
                            <h3>📋 Current Status</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <h4 style="color: #2c3e50; margin-bottom: 15px;">Latest Submission</h4>
                                    <p><strong>📄 Title:</strong> <?php echo htmlspecialchars($latest_submission['thesis_title']); ?></p>
                                    <p><strong>📅 Submitted:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($latest_submission['submission_date'])); ?></p>
                                    <p><strong>📊 Status:</strong> 
                                        <?php
                                        switch($latest_submission['review_status']) {
                                            case 'pending':
                                                echo '<span class="status-badge status-pending">⏳ Pending Review</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="status-badge status-approved">✅ Approved</span>';
                                                break;
                                            case 'failed':
                                                echo '<span class="status-badge status-failed">❌ Failed</span>';
                                                break;
                                            case 'revision_required':
                                                echo '<span class="status-badge status-revision">📝 Revision Required</span>';
                                                break;
                                        }
                                        ?>
                                    </p>
                                    <?php if ($latest_submission['review_date']): ?>
                                    <p><strong>📝 Reviewed:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($latest_submission['review_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="color: #2c3e50; margin-bottom: 15px;">Feedback & Comments</h4>
                                    <?php if (!empty($latest_submission['reviewer_remarks'])): ?>
                                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
                                            <h5 style="color: #007bff; margin-top: 0;">👨‍🏫 Supervisor Feedback:</h5>
                                            <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($latest_submission['reviewer_remarks'])); ?></p>
                                        </div>
                                    <?php elseif (!empty($latest_submission['thesis_remark'])): ?>
                                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                            <p><strong>📝 Notes:</strong> <?php echo htmlspecialchars($latest_submission['thesis_remark']); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; color: #856404;">
                                            <p style="margin: 0;">⏳ No feedback available yet. Please wait for supervisor review.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Detailed History Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📚 Complete Submission History</h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach (array_reverse($submissions) as $index => $submission): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?php 
                                        echo match($submission['review_status']) {
                                            'approved' => 'timeline-success',
                                            'failed' => 'timeline-danger', 
                                            'revision_required' => 'timeline-info',
                                            'pending' => 'timeline-warning',
                                            default => 'timeline-secondary'
                                        };
                                    ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h4><?php echo htmlspecialchars($submission['thesis_title']); ?></h4>
                                            <small class="text-muted">ID: <?php echo $submission['thesis_id']; ?></small>
                                        </div>
                                        <div class="timeline-body">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                                <div>
                                                    <p><strong>📅 Submitted:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submission_date'])); ?></p>
                                                    <?php if ($submission['review_date']): ?>
                                                    <p><strong>📝 Reviewed:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($submission['review_date'])); ?></p>
                                                    <?php endif; ?>
                                                    <p><strong>📊 Status:</strong> 
                                                        <?php
                                                        switch($submission['review_status']) {
                                                            case 'pending':
                                                                echo '<span class="status-badge status-pending">⏳ Pending</span>';
                                                                break;
                                                            case 'approved':
                                                                echo '<span class="status-badge status-approved">✅ Approved</span>';
                                                                break;
                                                            case 'failed':
                                                                echo '<span class="status-badge status-failed">❌ Failed</span>';
                                                                break;
                                                            case 'revision_required':
                                                                echo '<span class="status-badge status-revision">📝 Revision Required</span>';
                                                                break;
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <a href="<?php echo htmlspecialchars($submission['thesis_path']); ?>" 
                                                       target="_blank" class="btn" 
                                                       style="padding: 5px 10px; font-size: 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                                                        📄 View Document
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Chart.js Script -->
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('journeyChart').getContext('2d');
                        const timelineData = <?php echo json_encode($timeline_data); ?>;
                        
                        const chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                datasets: [{
                                    label: 'Submission Journey',
                                    data: timelineData,
                                    borderColor: '#007bff',
                                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                    pointBackgroundColor: timelineData.map(point => {
                                        switch(point.status) {
                                            case 'approved': return '#28a745';
                                            case 'failed': return '#dc3545';
                                            case 'revision_required': return '#17a2b8';
                                            case 'pending': return '#ffc107';
                                            default: return '#6c757d';
                                        }
                                    }),
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 8,
                                    pointHoverRadius: 12,
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            title: function(context) {
                                                const point = timelineData[context[0].dataIndex];
                                                return point.title;
                                            },
                                            label: function(context) {
                                                const point = timelineData[context.dataIndex];
                                                const statusLabels = {
                                                    'pending': 'Pending Review',
                                                    'revision_required': 'Revision Required', 
                                                    'failed': 'Failed',
                                                    'approved': 'Approved'
                                                };
                                                return `Status: ${statusLabels[point.status]}`;
                                            },
                                            afterLabel: function(context) {
                                                const point = timelineData[context.dataIndex];
                                                return `Submission ID: ${point.id}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        type: 'time',
                                        time: {
                                            parser: 'YYYY-MM-DD',
                                            displayFormats: {
                                                day: 'MMM DD',
                                                month: 'MMM YYYY'
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Submission Date'
                                        }
                                    },
                                    y: {
                                        min: -0.5,
                                        max: 3.5,
                                        ticks: {
                                            stepSize: 1,
                                            callback: function(value) {
                                                const labels = {
                                                    0: 'Pending',
                                                    1: 'Revision Required',
                                                    2: 'Failed', 
                                                    3: 'Approved'
                                                };
                                                return labels[value] || '';
                                            }
                                        },
                                        title: {
                                            display: true,
                                            text: 'Status'
                                        }
                                    }
                                }
                            }
                        });
                    </script>

                    <!-- Timeline CSS -->
                    <style>
                        .timeline {
                            position: relative;
                            padding-left: 30px;
                        }
                        
                        .timeline::before {
                            content: '';
                            position: absolute;
                            left: 15px;
                            top: 0;
                            bottom: 0;
                            width: 2px;
                            background: #e9ecef;
                        }
                        
                        .timeline-item {
                            position: relative;
                            margin-bottom: 30px;
                            padding-left: 25px;
                        }
                        
                        .timeline-marker {
                            position: absolute;
                            left: -23px;
                            top: 8px;
                            width: 16px;
                            height: 16px;
                            border-radius: 50%;
                            border: 3px solid #fff;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }
                        
                        .timeline-success { background: #28a745; }
                        .timeline-danger { background: #dc3545; }
                        .timeline-info { background: #17a2b8; }
                        .timeline-warning { background: #ffc107; }
                        .timeline-secondary { background: #6c757d; }
                        
                        .timeline-content {
                            background: #fff;
                            border: 1px solid #e9ecef;
                            border-radius: 8px;
                            padding: 20px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                        }
                        
                        .timeline-header {
                            margin-bottom: 15px;
                            border-bottom: 1px solid #e9ecef;
                            padding-bottom: 10px;
                        }
                        
                        .timeline-header h4 {
                            margin: 0;
                            color: #2c3e50;
                            font-size: 16px;
                        }
                    </style>

                    <?php else: ?>
                    <!-- No Submissions State -->
                    <div class="card">
                        <div class="card-body" style="text-align: center; padding: 60px;">
                            <div style="font-size: 4em; margin-bottom: 20px;">📊</div>
                            <h3 style="color: #666; margin-bottom: 15px;">No Submissions Yet</h3>
                            <p style="color: #999; margin-bottom: 30px;">Your submission journey will appear here once you submit your first thesis.</p>
                            <button onclick="showTab('submit-section')" class="btn btn-primary">
                                📄 Submit Your First Thesis
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Profile Section -->
                <div id="profile-section" class="content-section">
                    <?php echo $profile_message; ?>
                    
                    <!-- Profile Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>👤 Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="student_name">Full Name:</label>
                                <input type="text" id="student_name" name="student_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['student_name'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="user_email">Email Address:</label>
                                <input type="email" id="user_email" name="user_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['user_email'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="student_faculty">Faculty:</label>
                                <input type="text" id="student_faculty" name="student_faculty" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['student_faculty'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="student_program">Program:</label>
                                <input type="text" id="student_program" name="student_program" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['student_program'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label>User ID:</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_id); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label>Supervisor:</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo !empty($user_data['supervisor_name']) ? htmlspecialchars($user_data['supervisor_name']) : 'Not Assigned'; ?>" 
                                       readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div style="padding: 15px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px; margin-bottom: 20px;">
                                <strong style="color: #1565c0;">ℹ️ Profile Information</strong>
                                <p style="margin: 8px 0 0 0; color: #0d47a1; font-size: 13px;">Your profile information is managed by the admin. To update any details, please contact the administrator.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🔒 Security Settings</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="current_password">Current Password:</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password:</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" 
                                           minlength="6" required>
                                    <small style="color: #666;">Password must be at least 6 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password:</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           minlength="6" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    🔑 Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 Account Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="feature-grid">
                                <div class="feature-item">
                                    <h4>Account Status</h4>
                                    <p style="color: #28a745; font-weight: bold;">✅ Active</p>
                                </div>
                                <div class="feature-item">
                                    <h4>User Role</h4>
                                    <p style="color: #007bff; font-weight: bold;">👨‍🎓 Student</p>
                                </div>
                                <div class="feature-item">
                                    <h4>Registration Date</h4>
                                    <p>Account created by admin</p>
                                </div>
                                <div class="feature-item">
                                    <h4>Profile Completion</h4>
                                    <p style="color: #28a745; font-weight: bold;">100% Complete</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                
                <!-- Supervisor Sections -->
                <!-- Review Section -->
                <div id="review-section" class="content-section">
                    <?php echo $review_message; ?>
                    
                    <!-- Pending Reviews -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📝 Pending Thesis Reviews</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get current supervisor's supervisor_number first
                            $supervisor_query = "SELECT supervisor_number FROM supervisor WHERE user_id = '$user_id'";
                            $supervisor_result = mysqli_query($conn, $supervisor_query);
                            
                            if (mysqli_num_rows($supervisor_result) > 0) {
                                $supervisor_data = mysqli_fetch_assoc($supervisor_result);
                                $supervisor_number = $supervisor_data['supervisor_number'];
                                
                                // Fetch pending thesis submissions for review
                                // Updated to use correct column name (student_number without space)
                                $pending_query = "SELECT t.thesis_id, t.thesis_title, t.thesis_path, 
                                                        COALESCE(t.submission_date, 'Not Available') as submission_date,
                                                        COALESCE(t.review_status, 'pending') as review_status,
                                                        s.student_name, s.student_faculty, s.student_program,
                                                        s.user_id as student_user_id, u.user_email
                                                 FROM thesis t
                                                 JOIN student s ON t.student_number = s.student_number
                                                 JOIN user u ON s.user_id = u.user_id
                                                 WHERE t.supervisor_number = '$supervisor_number'
                                                   AND (t.review_status IS NULL OR t.review_status = 'pending')
                                                 ORDER BY t.thesis_id DESC";
                                $pending_result = mysqli_query($conn, $pending_query);
                            } else {
                                $pending_result = false;
                            }
                            
                            if ($pending_result && mysqli_num_rows($pending_result) > 0): ?>
                                <?php while ($thesis = mysqli_fetch_assoc($pending_result)): ?>
                                    <div class="thesis-review-item" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f8f9fa;">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h4 style="color: #007bff; margin-bottom: 10px;">
                                                    📄 <?php echo htmlspecialchars($thesis['thesis_title']); ?>
                                                </h4>
                                                
                                                <div class="student-info" style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                                    <h5 style="color: #495057; margin-bottom: 10px;">👨‍🎓 Student Information</h5>
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($thesis['student_name']); ?></p>
                                                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($thesis['student_user_id']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($thesis['user_email']); ?></p>
                                                    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($thesis['student_faculty']); ?></p>
                                                    <p><strong>Program:</strong> <?php echo htmlspecialchars($thesis['student_program']); ?></p>
                                                </div>
                                                
                                                <div class="submission-info">
                                                    <p><strong>📅 Submitted:</strong> <?php echo htmlspecialchars($thesis['submission_date']); ?></p>
                                                    <p><strong>📁 File:</strong> 
                                                        <a href="<?php echo htmlspecialchars($thesis['thesis_path']); ?>" 
                                                           target="_blank" style="color: #007bff; text-decoration: none;">
                                                            📖 View Thesis Document
                                                        </a>
                                                    </p>
                                                    <p><strong>⏳ Status:</strong> 
                                                        <span class="badge status-pending">Pending Review</span>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="review-form" style="background: white; padding: 15px; border-radius: 5px;">
                                                    <h5 style="color: #495057; margin-bottom: 15px;">✍️ Submit Review</h5>
                                                    
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="thesis_id" value="<?php echo $thesis['thesis_id']; ?>">
                                                        
                                                        <div class="form-group" style="margin-bottom: 15px;">
                                                            <label for="review_status_<?php echo $thesis['thesis_id']; ?>">Decision:</label>
                                                            <select name="review_status" id="review_status_<?php echo $thesis['thesis_id']; ?>" 
                                                                    class="form-control" required>
                                                                <option value="">-- Select Decision --</option>
                                                                <option value="approved">✅ Approved</option>
                                                                <option value="revision_required">📝 Revision Required</option>
                                                                <option value="failed">❌ Failed</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="form-group" style="margin-bottom: 15px;">
                                                            <label for="reviewer_remarks_<?php echo $thesis['thesis_id']; ?>">Comments & Feedback:</label>
                                                            <textarea name="reviewer_remarks" 
                                                                    id="reviewer_remarks_<?php echo $thesis['thesis_id']; ?>" 
                                                                    class="form-control" rows="4" 
                                                                    placeholder="Provide detailed feedback to the student..."
                                                                    required></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" name="submit_review" class="btn btn-primary" style="width: 100%;">
                                                            📋 Submit Review
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: #6c757d;">
                                    <h4>📭 No Pending Reviews</h4>
                                    <p>There are no thesis submissions waiting for your review at the moment.</p>
                                    <p>New submissions from your students will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Completed Reviews -->
                    <div class="card">
                        <div class="card-header">
                            <h3>✅ Completed Reviews</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($supervisor_result && mysqli_num_rows($supervisor_result) > 0) {
                                // Fetch completed reviews
                                $completed_query = "SELECT t.thesis_id, t.thesis_title, t.thesis_path, 
                                                          t.submission_date, t.review_status, t.review_date, t.reviewer_remarks,
                                                          s.student_name, s.user_id as student_user_id
                                                   FROM thesis t
                                                   JOIN student s ON t.student_number = s.student_number
                                                   WHERE t.supervisor_number = '$supervisor_number'
                                                     AND t.review_status IN ('approved', 'failed', 'revision_required')
                                                   ORDER BY t.review_date DESC
                                                   LIMIT 10";
                                $completed_result = mysqli_query($conn, $completed_query);
                                
                                if ($completed_result && mysqli_num_rows($completed_result) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Thesis Title</th>
                                                    <th>Review Date</th>
                                                    <th>Decision</th>
                                                    <th>Comments</th>
                                                    <th>File</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($review = mysqli_fetch_assoc($completed_result)): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($review['student_name']); ?></strong><br>
                                                            <small><?php echo htmlspecialchars($review['student_user_id']); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($review['thesis_title']); ?></td>
                                                        <td><?php echo htmlspecialchars($review['review_date'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <?php
                                                            $status_class = '';
                                                            $status_text = '';
                                                            switch($review['review_status']) {
                                                                case 'approved':
                                                                    $status_class = 'status-approved';
                                                                    $status_text = '✅ Approved';
                                                                    break;
                                                                case 'failed':
                                                                    $status_class = 'status-rejected';
                                                                    $status_text = '❌ Failed';
                                                                    break;
                                                                case 'revision_required':
                                                                    $status_class = 'status-pending';
                                                                    $status_text = '📝 Revision Required';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                        <td style="max-width: 200px;">
                                                            <small><?php echo htmlspecialchars(substr($review['reviewer_remarks'] ?? '', 0, 100)) . (strlen($review['reviewer_remarks'] ?? '') > 100 ? '...' : ''); ?></small>
                                                        </td>
                                                        <td>
                                                            <a href="<?php echo htmlspecialchars($review['thesis_path']); ?>" 
                                                               target="_blank" style="color: #007bff;">📄 View</a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                                        <h4>📝 No Completed Reviews</h4>
                                        <p>You haven't completed any thesis reviews yet.</p>
                                        <p>Completed reviews will be shown here for reference.</p>
                                    </div>
                                <?php endif;
                            } else {
                                echo '<p style="text-align: center; color: #6c757d;">Unable to load review data.</p>';
                            } ?>
                        </div>
                    </div>

                    <!-- Review Statistics -->
                    <?php if ($supervisor_result && mysqli_num_rows($supervisor_result) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📊 Review Statistics</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get review statistics
                            $stats_query = "SELECT 
                                           COUNT(*) as total_submissions,
                                           SUM(CASE WHEN review_status IS NULL OR review_status = 'pending' THEN 1 ELSE 0 END) as pending_reviews,
                                           SUM(CASE WHEN review_status = 'approved' THEN 1 ELSE 0 END) as approved,
                                           SUM(CASE WHEN review_status = 'failed' THEN 1 ELSE 0 END) as failed,
                                           SUM(CASE WHEN review_status = 'revision_required' THEN 1 ELSE 0 END) as revision_required
                                           FROM thesis WHERE supervisor_number = '$supervisor_number'";
                            $stats_result = mysqli_query($conn, $stats_query);
                            $stats = mysqli_fetch_assoc($stats_result);
                            ?>
                            
                            <div class="feature-grid">
                                <div class="feature-item">
                                    <h4>Total Submissions</h4>
                                    <p style="color: #007bff; font-weight: bold; font-size: 24px;"><?php echo $stats['total_submissions']; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Pending Reviews</h4>
                                    <p style="color: #ffc107; font-weight: bold; font-size: 24px;"><?php echo $stats['pending_reviews']; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Approved</h4>
                                    <p style="color: #28a745; font-weight: bold; font-size: 24px;"><?php echo $stats['approved']; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Need Revision</h4>
                                    <p style="color: #17a2b8; font-weight: bold; font-size: 24px;"><?php echo $stats['revision_required']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Students Section -->
                <div id="students-section" class="content-section">
                    <?php echo $student_message; ?>
                    
                    <!-- Add Student Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3>➕ Add Student Under Supervision</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="student_user_id">Student User ID:</label>
                                    <input type="text" id="student_user_id" name="student_user_id" class="form-control" 
                                           placeholder="Enter student's User ID (e.g., STU001)" required>
                                    <small style="color: #666;">Enter the User ID of the student you want to supervise</small>
                                </div>
                                
                                <button type="submit" name="add_student" class="btn btn-primary">
                                    👨‍🎓 Add Student
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Current Students List -->
                    <div class="card">
                        <div class="card-header">
                            <h3>👥 Students Under My Supervision</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get current supervisor's supervisor_number first
                            $supervisor_query = "SELECT supervisor_number FROM supervisor WHERE user_id = '$user_id'";
                            $supervisor_result = mysqli_query($conn, $supervisor_query);
                            
                            if (mysqli_num_rows($supervisor_result) > 0) {
                                $supervisor_data = mysqli_fetch_assoc($supervisor_result);
                                $supervisor_number = $supervisor_data['supervisor_number'];
                                
                                // Fetch students under this supervisor's supervision
                                $students_query = "SELECT s.user_id, s.student_name, s.student_faculty, s.student_program, 
                                                         u.user_email, s.student_number
                                                  FROM student s 
                                                  JOIN user u ON s.user_id = u.user_id 
                                                  WHERE s.supervisor_number = '$supervisor_number' 
                                                  ORDER BY s.student_name";
                                $students_result = mysqli_query($conn, $students_query);
                            } else {
                                $students_result = false;
                            }
                            
                            if ($students_result && mysqli_num_rows($students_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Student Name</th>
                                                <th>Faculty</th>
                                                <th>Program</th>
                                                <th>Email</th>
                                                <th>Submissions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = mysqli_fetch_assoc($students_result)): 
                                                // Count thesis submissions for this student
                                                $submission_count_query = "SELECT COUNT(*) as count FROM thesis WHERE student_number = '{$student['student_number']}'";
                                                $submission_count_result = mysqli_query($conn, $submission_count_query);
                                                $submission_count = mysqli_fetch_assoc($submission_count_result)['count'];
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($student['user_id']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['student_faculty']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['student_program']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['user_email']); ?></td>
                                                    <td>
                                                        <span class="badge" style="background: <?php echo $submission_count > 0 ? '#28a745' : '#6c757d'; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                                            <?php echo $submission_count; ?> submission<?php echo $submission_count != 1 ? 's' : ''; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                    <p style="margin: 0; color: #6c757d;">
                                        📊 <strong>Summary:</strong> You are currently supervising <?php echo mysqli_num_rows($students_result); ?> student<?php echo mysqli_num_rows($students_result) != 1 ? 's' : ''; ?>.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: #6c757d;">
                                    <h4>📝 No Students Assigned</h4>
                                    <p>You don't have any students under your supervision yet.</p>
                                    <p>Use the form above to add students by entering their User ID.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Student Progress Overview -->
                    <?php if ($students_result && mysqli_num_rows($students_result) > 0): 
                        // Reset the result pointer to reuse the data
                        mysqli_data_seek($students_result, 0);
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📈 Student Progress Overview</h3>
                        </div>
                        <div class="card-body">
                            <div class="feature-grid">
                                <?php 
                                $total_students = 0;
                                $students_with_submissions = 0;
                                $total_submissions = 0;
                                
                                while ($student = mysqli_fetch_assoc($students_result)): 
                                    $total_students++;
                                    $submission_count_query = "SELECT COUNT(*) as count FROM thesis WHERE student_number = '{$student['student_number']}'";
                                    $submission_count_result = mysqli_query($conn, $submission_count_query);
                                    $submission_count = mysqli_fetch_assoc($submission_count_result)['count'];
                                    $total_submissions += $submission_count;
                                    if ($submission_count > 0) $students_with_submissions++;
                                endwhile; 
                                ?>
                                
                                <div class="feature-item">
                                    <h4>Total Students</h4>
                                    <p style="color: #007bff; font-weight: bold; font-size: 24px;"><?php echo $total_students; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Active Students</h4>
                                    <p style="color: #28a745; font-weight: bold; font-size: 24px;"><?php echo $students_with_submissions; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Total Submissions</h4>
                                    <p style="color: #ffc107; font-weight: bold; font-size: 24px;"><?php echo $total_submissions; ?></p>
                                </div>
                                <div class="feature-item">
                                    <h4>Progress Rate</h4>
                                    <p style="color: #17a2b8; font-weight: bold; font-size: 24px;">
                                        <?php echo $total_students > 0 ? round(($students_with_submissions / $total_students) * 100) : 0; ?>%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Reports Section -->
                <div id="reports-section" class="content-section">
                    <div class="card">
                        <div class="card-header">
                            <h3>📈 Reports</h3>
                        </div>
                        <div class="card-body">
                            <div class="dummy-content">
                                <h3>Generate Reports</h3>
                                <p>Create and download various reports about student progress.</p>
                                
                                <div class="coming-soon">
                                    <h4>📊 Reporting System</h4>
                                    <p>Generate comprehensive reports on student submissions, progress, and statistics.</p>
                                </div>

                                <div class="feature-grid">
                                    <div class="feature-item">
                                        <h4>Progress Reports</h4>
                                        <p>Student progress analytics</p>
                                    </div>
                                    <div class="feature-item">
                                        <h4>Statistics</h4>
                                        <p>Submission statistics</p>
                                    </div>
                                    <div class="feature-item">
                                        <h4>Export Options</h4>
                                        <p>PDF and Excel exports</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Section (Supervisor) -->
                <div id="profile-section" class="content-section">
                    <?php echo $profile_message; ?>
                    
                    <!-- Profile Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>👤 Professional Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="supervisor_name">Full Name:</label>
                                <input type="text" id="supervisor_name" name="supervisor_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['supervisor_name'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="user_email">Email Address:</label>
                                <input type="email" id="user_email" name="user_email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['user_email'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="supervisor_faculty">Faculty:</label>
                                <input type="text" id="supervisor_faculty" name="supervisor_faculty" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['supervisor_faculty'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label for="supervisor_department">Department:</label>
                                <input type="text" id="supervisor_department" name="supervisor_department" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['supervisor_department'] ?? ''); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            
                            <div class="form-group">
                                <label>User ID:</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_id); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🔒 Security Settings</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="current_password">Current Password:</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password:</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" 
                                           minlength="6" required>
                                    <small style="color: #666;">Password must be at least 6 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password:</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           minlength="6" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    🔑 Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Information Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 Account Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="feature-grid">
                                <div class="feature-item">
                                    <h4>Account Status</h4>
                                    <p style="color: #28a745; font-weight: bold;">✅ Active</p>
                                </div>
                                <div class="feature-item">
                                    <h4>User Role</h4>
                                    <p style="color: #007bff; font-weight: bold;">👨‍🏫 Supervisor</p>
                                </div>
                                <div class="feature-item">
                                    <h4>Registration Date</h4>
                                    <p>Account created by admin</p>
                                </div>
                                <div class="feature-item">
                                    <h4>Profile Completion</h4>
                                    <p style="color: #28a745; font-weight: bold;">100% Complete</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
            
            <!-- Database Section - Shared by both Students and Supervisors -->
            <div id="database-section" class="content-section">
                <?php
                // Calculate year range - show thesis from previous 5 years (excluding current year)
                $current_year = (int)date('Y');
                $start_year = $current_year - 5;
                $end_year = $current_year - 1; // Exclude current year
                
                // Get search parameters
                $search_title = isset($_GET['search_title']) ? mysqli_real_escape_string($conn, $_GET['search_title']) : '';
                $search_student = isset($_GET['search_student']) ? mysqli_real_escape_string($conn, $_GET['search_student']) : '';
                $search_supervisor = isset($_GET['search_supervisor']) ? mysqli_real_escape_string($conn, $_GET['search_supervisor']) : '';
                $filter_faculty = isset($_GET['filter_faculty']) ? mysqli_real_escape_string($conn, $_GET['filter_faculty']) : '';
                $filter_year = isset($_GET['filter_year']) ? mysqli_real_escape_string($conn, $_GET['filter_year']) : '';

                // Build WHERE clause for search/filter - only show approved thesis from previous 5 years
                $where_conditions = ["t.review_status = 'approved'", "YEAR(t.submission_date) >= $start_year", "YEAR(t.submission_date) <= $end_year"];
                if (!empty($search_title)) {
                    $where_conditions[] = "t.thesis_title LIKE '%$search_title%'";
                }
                if (!empty($search_student)) {
                    $where_conditions[] = "st.student_name LIKE '%$search_student%'";
                }
                if (!empty($search_supervisor)) {
                    $where_conditions[] = "sp.supervisor_name LIKE '%$search_supervisor%'";
                }
                if (!empty($filter_faculty)) {
                    $where_conditions[] = "st.student_faculty = '$filter_faculty'";
                }
                if (!empty($filter_year)) {
                    $where_conditions[] = "YEAR(t.submission_date) = '$filter_year'";
                }

                $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

                // Query to get all thesis with student and supervisor information
                $database_query = "SELECT 
                                    t.thesis_id,
                                    t.thesis_title,
                                    t.thesis_path,
                                    t.submission_date,
                                    t.review_status,
                                    t.review_date,
                                    t.reviewer_remarks,
                                    st.student_name,
                                    st.student_faculty,
                                    st.student_program,
                                    st.student_number,
                                    sp.supervisor_name,
                                    sp.supervisor_faculty,
                                    sp.supervisor_department,
                                    sp.supervisor_number
                                  FROM thesis t
                                  LEFT JOIN student st ON t.student_number = st.student_number
                                  LEFT JOIN supervisor sp ON t.supervisor_number = sp.supervisor_number
                                  $where_clause
                                  ORDER BY t.submission_date DESC";

                $database_result = mysqli_query($conn, $database_query);

                // Get unique faculties and years for filter dropdowns (only from previous 5 years)
                $faculties_query = "SELECT DISTINCT student_faculty FROM student WHERE student_faculty IS NOT NULL AND student_faculty != '' ORDER BY student_faculty";
                $faculties_result = mysqli_query($conn, $faculties_query);

                $years_query = "SELECT DISTINCT YEAR(submission_date) as year FROM thesis WHERE submission_date IS NOT NULL AND YEAR(submission_date) >= $start_year AND YEAR(submission_date) <= $end_year ORDER BY year DESC";
                $years_result = mysqli_query($conn, $years_query);
                ?>

                <div class="card">
                    <div class="card-header">
                        <h3>📚 Approved Thesis Repository</h3>
                        <p style="margin: 0; color: #666; font-size: 14px;">Browse and search through approved thesis submissions from the last 5 years (<?php echo $start_year; ?>-<?php echo $end_year; ?>) for reference</p>
                    </div>
                    <div class="card-body">
                        
                        <!-- Search and Filter Section -->
                        <form method="GET" action="" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <input type="hidden" name="section" value="database-section">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="search_title" style="font-weight: 500; margin-bottom: 5px; display: block;">🔍 Search Title:</label>
                                    <input type="text" id="search_title" name="search_title" class="form-control" 
                                           placeholder="Enter thesis title keywords" value="<?php echo htmlspecialchars($search_title); ?>">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="search_student" style="font-weight: 500; margin-bottom: 5px; display: block;">👨‍🎓 Search Student:</label>
                                    <input type="text" id="search_student" name="search_student" class="form-control" 
                                           placeholder="Enter student name" value="<?php echo htmlspecialchars($search_student); ?>">
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="search_supervisor" style="font-weight: 500; margin-bottom: 5px; display: block;">👨‍🏫 Search Supervisor:</label>
                                    <input type="text" id="search_supervisor" name="search_supervisor" class="form-control" 
                                           placeholder="Enter supervisor name" value="<?php echo htmlspecialchars($search_supervisor); ?>">
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="filter_faculty" style="font-weight: 500; margin-bottom: 5px; display: block;">🏫 Faculty:</label>
                                    <select id="filter_faculty" name="filter_faculty" class="form-control">
                                        <option value="">All Faculties</option>
                                        <?php while ($faculty = mysqli_fetch_assoc($faculties_result)): ?>
                                            <option value="<?php echo htmlspecialchars($faculty['student_faculty']); ?>" 
                                                    <?php echo $filter_faculty == $faculty['student_faculty'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($faculty['student_faculty']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="filter_year" style="font-weight: 500; margin-bottom: 5px; display: block;">📅 Year:</label>
                                    <select id="filter_year" name="filter_year" class="form-control">
                                        <option value="">All Years</option>
                                        <?php 
                                        mysqli_data_seek($years_result, 0);
                                        while ($year = mysqli_fetch_assoc($years_result)): 
                                        ?>
                                            <option value="<?php echo $year['year']; ?>" 
                                                    <?php echo $filter_year == $year['year'] ? 'selected' : ''; ?>>
                                                <?php echo $year['year']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div style="display: flex; align-items: end; gap: 10px;">
                                    <button type="submit" class="btn btn-primary" style="flex: 1;">🔍 Search</button>
                                    <a href="?section=database-section" 
                                       class="btn" style="background: #6c757d; color: white; text-decoration: none; flex: 1; text-align: center;">🔄 Reset</a>
                                </div>
                            </div>
                        </form>

                        <!-- Results Summary -->
                        <?php 
                        $total_results = mysqli_num_rows($database_result);
                        ?>
                        <div style="background: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                            <strong>📊 Results: </strong><?php echo $total_results; ?> approved thesis found
                            <?php if (!empty($search_title) || !empty($search_student) || !empty($search_supervisor) || !empty($filter_faculty) || !empty($filter_year)): ?>
                                <span style="color: #0066cc;">| Filters applied</span>
                            <?php endif; ?>
                        </div>

                        <!-- Thesis Database Table -->
                        <?php if ($total_results > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="min-width: 1200px;">
                                <thead>
                                    <tr style="background: #343a40; color: white;">
                                        <th style="width: 250px;">📄 Thesis Information</th>
                                        <th style="width: 200px;">👨‍🎓 Student Details</th>
                                        <th style="width: 200px;">👨‍🏫 Supervisor Details</th>
                                        <th style="width: 150px;">📅 Dates</th>
                                        <th style="width: 80px;">📁 File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($thesis = mysqli_fetch_assoc($database_result)): ?>
                                    <tr style="border-bottom: 2px solid #e9ecef;">
                                        <td style="vertical-align: top; padding: 15px;">
                                            <div>
                                                <strong style="color: #2c3e50; font-size: 14px;">
                                                    <?php echo htmlspecialchars($thesis['thesis_title']); ?>
                                                </strong>
                                                <br><small style="color: #666;">ID: <?php echo $thesis['thesis_id']; ?></small>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top; padding: 15px;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($thesis['student_name'] ?? 'N/A'); ?></strong>
                                                <br><small style="color: #666;">ID: <?php echo $thesis['student_number'] ?? 'N/A'; ?></small>
                                                <br><small style="color: #666;">📚 <?php echo htmlspecialchars($thesis['student_program'] ?? 'N/A'); ?></small>
                                                <br><small style="color: #666;">🏫 <?php echo htmlspecialchars($thesis['student_faculty'] ?? 'N/A'); ?></small>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top; padding: 15px;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($thesis['supervisor_name'] ?? 'N/A'); ?></strong>
                                                <br><small style="color: #666;">ID: <?php echo $thesis['supervisor_number'] ?? 'N/A'; ?></small>
                                                <br><small style="color: #666;">🏫 <?php echo htmlspecialchars($thesis['supervisor_faculty'] ?? 'N/A'); ?></small>
                                                <br><small style="color: #666;">🏢 <?php echo htmlspecialchars($thesis['supervisor_department'] ?? 'N/A'); ?></small>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top; padding: 15px;">
                                            <div>
                                                <strong>Submitted:</strong>
                                                <br><small><?php echo date('M j, Y', strtotime($thesis['submission_date'])); ?></small>
                                                <?php if ($thesis['review_date']): ?>
                                                <br><br><strong>Approved:</strong>
                                                <br><small><?php echo date('M j, Y', strtotime($thesis['review_date'])); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top; padding: 15px; text-align: center;">
                                            <a href="<?php echo htmlspecialchars($thesis['thesis_path']); ?>" 
                                               target="_blank" class="btn" 
                                               style="padding: 6px 10px; font-size: 11px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">
                                                📄 Download
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #666;">📭 No Approved Thesis Found</h4>
                            <p style="color: #999;">No approved thesis match your search criteria. Try adjusting your filters or search terms.</p>
                        </div>
                        <?php endif; ?>

                        <!-- Information -->
                        <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
                            <h5 style="margin-bottom: 10px; color: #155724;">✅ Approved Thesis Repository</h5>
                            <p style="margin: 0; font-size: 14px; color: #155724;">
                                <strong>📚 What you see here:</strong> This repository displays only approved and successfully defended thesis submissions. 
                                All files are available for download and can serve as valuable reference material for your research.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: #0c5460;">
                                <strong>💡 Tip:</strong> Use the search and filter options above to find thesis related to your field of study, 
                                specific faculty, or research year for better reference material.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let similarityChecked = false;
        let canSubmit = false;

        function handleFileChange() {
            console.log('handleFileChange() function called');
            // Reset similarity check when file changes
            similarityChecked = false;
            canSubmit = false;
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitHint').textContent = 'Checking similarity automatically...';
            document.getElementById('submitHint').style.color = '#007bff';
            document.getElementById('similarityResults').style.display = 'none';
            
            // Automatically check similarity when file is selected
            const fileInput = document.getElementById('thesis_file');
            console.log('File input found:', fileInput);
            console.log('Files selected:', fileInput.files.length);
            if (fileInput.files.length > 0) {
                console.log('File name:', fileInput.files[0].name);
                setTimeout(() => {
                    console.log('Calling checkSimilarity() after delay');
                    checkSimilarity();
                }, 500); // Small delay to ensure file is properly loaded
            }
        }

        async function checkSimilarity() {
            const fileInput = document.getElementById('thesis_file');
            const file = fileInput.files[0];
            
            console.log('checkSimilarity() called');
            console.log('File selected:', file ? file.name : 'None');
            
            if (!file) {
                console.log('No file selected');
                document.getElementById('submitHint').textContent = 'Please select a file first';
                document.getElementById('submitHint').style.color = 'red';
                return;
            }

            // Show loading indicator
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('similarityResults').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitHint').textContent = 'Analyzing your thesis with AI...';
            document.getElementById('submitHint').style.color = '#007bff';

            const formData = new FormData();
            formData.append('file', file);

            console.log('Sending file to similarity_checker.php...');

            try {
                const response = await fetch('similarity_checker.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response received. Status:', response.status);
                
                const result = await response.json();
                
                console.log('Similarity API Response:', result);

                if (response.ok) {
                    console.log('Response OK, displaying results');
                    displaySimilarityResults(result);
                    similarityChecked = true;
                    canSubmit = true;
                    
                    document.getElementById('submitBtn').disabled = false;
                    document.getElementById('submitHint').textContent = 'Similarity check complete - Ready to submit!';
                    document.getElementById('submitHint').style.color = 'green';
                } else {
                    throw new Error(result.error || 'Failed to check similarity');
                }
            } catch (error) {
                console.error('Error during similarity check:', error);
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('submitHint').textContent = 'Similarity check failed, but you can still submit';
                document.getElementById('submitHint').style.color = 'orange';
                
                const resultsDiv = document.getElementById('similarityResults');
                const contentDiv = document.getElementById('similarityContent');
                contentDiv.innerHTML = `
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;">
                        <h5>⚠️ Similarity Check Unavailable</h5>
                        <p>Unable to check similarity: ${error.message}</p>
                        <p>You can still proceed with your submission.</p>
                    </div>
                `;
                resultsDiv.style.display = 'block';
            } finally {
                document.getElementById('loadingIndicator').style.display = 'none';
            }
        }

        let similarityResult = null;

        function displaySimilarityResults(result) {
            const resultsDiv = document.getElementById('similarityResults');
            const contentDiv = document.getElementById('similarityContent');
            
            // Store result globally for report generation
            similarityResult = result;
            
            // Extract values with fallbacks
            const message = result.message || '✅ Similarity check completed!';
            const similarity = (result.max_similarity !== undefined && result.max_similarity !== null) ? result.max_similarity : 0;
            const hasError = result.error ? true : false;
            const matchesList = result.similar_files_list || [];
            
            // Always use success styling since we always allow submission
            let bgColor = hasError ? '#fff3cd' : '#d4edda';
            let borderColor = hasError ? '#ffeaa7' : '#c3e6cb';
            let textColor = hasError ? '#856404' : '#155724';
            
            let html = `
                <div style="background: ${bgColor}; border: 1px solid ${borderColor}; padding: 15px; border-radius: 5px; color: ${textColor};">
                    <h5 style="margin-bottom: 10px;">${message}</h5>
            `;
            
            // Display list of all matching documents if any found
            if (matchesList && matchesList.length > 0) {
                html += `
                    <div style="margin-top: 15px; margin-bottom: 15px;">
                        <h6 style="color: #2a5298; margin-bottom: 10px; font-weight: 600;">📋 Matching Documents:</h6>
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="background: #f0f0f0; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 10px; text-align: left; color: #495057; font-weight: 600;">Document Name</th>
                                    <th style="padding: 10px; text-align: center; color: #495057; font-weight: 600; width: 120px;">Similarity</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                // Add each matching document as a table row
                matchesList.forEach((match, index) => {
                    const similarity_pct = match.similarity;
                    let rowBgColor = index % 2 === 0 ? '#ffffff' : '#f9f9f9';
                    
                    // Color code based on similarity percentage
                    let similarityColor = '#28a745'; // Green for low similarity
                    if (similarity_pct >= 50) similarityColor = '#ff9800'; // Orange for medium
                    if (similarity_pct >= 75) similarityColor = '#f44336'; // Red for high
                    
                    html += `
                        <tr style="background: ${rowBgColor}; border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 10px; color: #495057;">${match.filename}</td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: ${similarityColor}; color: white; padding: 5px 10px; border-radius: 15px; font-weight: 600; font-size: 13px;">
                                    ${similarity_pct}%
                                </span>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            html += `
                    <div style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.3); border-radius: 3px;">
                        <strong>✅ Ready to submit:</strong> You can proceed with your thesis submission.
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" onclick="generateSimilarityReport()" style="
                            background: #2a5298;
                            color: white;
                            padding: 10px 20px;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                            font-weight: 500;
                        ">
                            📄 View Report
                        </button>
                    </div>
                </div>
            `;
            
            contentDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function generateSimilarityReport() {
            if (!similarityResult) {
                alert('No similarity data available. Please run similarity check again.');
                return;
            }

            const fileInput = document.getElementById('thesis_file');
            const uploadedFilename = fileInput.files[0] ? fileInput.files[0].name : 'Unknown';

            // Prepare report data with all matching documents
            const reportData = {
                uploaded_filename: uploadedFilename,
                similar_files_list: similarityResult.similar_files_list || []
            };

            // Send to backend to generate PDF
            fetch('http://localhost:5000/api/generate_report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(reportData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to generate report');
                }
                return response.blob();
            })
            .then(blob => {
                // Create blob URL and open in new tab
                const blobUrl = window.URL.createObjectURL(blob);
                window.open(blobUrl, '_blank');
                
                // Clean up the URL after a delay
                setTimeout(() => window.URL.revokeObjectURL(blobUrl), 100);
            })
            .catch(error => {
                console.error('Error generating report:', error);
                alert('Error generating report. Please try again.');
            });
        }

        // Prevent form submission if similarity not checked
        document.getElementById('thesisForm').addEventListener('submit', function(e) {
            if (!similarityChecked) {
                e.preventDefault();
                alert('Please wait for the automatic similarity check to complete before submitting.');
                return false;
            }
            
            // Show confirmation dialog
            if (!confirm('Are you sure you want to submit your thesis?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>

</body>
</html>
