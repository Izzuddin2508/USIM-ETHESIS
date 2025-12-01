<?php
session_start();
include 'Configuration.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: Admin-login.php");
    exit();
}

// Handle supervisor assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_supervisor'])) {
    $student_number = $_POST['student_number'];
    $supervisor_number = $_POST['supervisor_number'];
    
    $assign_query = "UPDATE student SET supervisor_number = '$supervisor_number' WHERE student_number = '$student_number'";
    if (mysqli_query($conn, $assign_query)) {
        $success_message = "Supervisor assigned successfully!";
    } else {
        $error_message = "Error assigning supervisor: " . mysqli_error($conn);
    }
}

// Handle thesis review update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_thesis_review'])) {
    $thesis_id = $_POST['thesis_id'];
    $review_status = $_POST['review_status'];
    $reviewer_remarks = $_POST['reviewer_remarks'];
    
    $review_query = "UPDATE thesis SET review_status = '$review_status', reviewer_remarks = '$reviewer_remarks', review_date = NOW() WHERE thesis_id = '$thesis_id'";
    if (mysqli_query($conn, $review_query)) {
        $success_message = "Thesis review updated successfully!";
    } else {
        $error_message = "Error updating thesis review: " . mysqli_error($conn);
    }
}

// Handle user role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $role_query = "UPDATE user SET user_role = '$new_role' WHERE user_id = '$user_id'";
    if (mysqli_query($conn, $role_query)) {
        $success_message = "User role updated successfully!";
    } else {
        $error_message = "Error updating user role: " . mysqli_error($conn);
    }
}

// Handle PDF download for unregistered users
if (isset($_GET['download_unregistered_pdf'])) {
    // Get unregistered users (users who haven't confirmed with password)
    $unregistered_query = "SELECT user_id, ic_number, user_role FROM user WHERE user_password IS NULL OR user_password = '' ORDER BY user_role, user_id";
    $unregistered_result = mysqli_query($conn, $unregistered_query);
    
    // Create formatted content
    $content = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Unregistered Users Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #001f3f; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #001f3f; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .summary { margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; }
        .role-student { color: #28a745; font-weight: bold; }
        .role-supervisor { color: #007bff; font-weight: bold; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>USIM eThesis System</h1>
        <h2>Unregistered Users Report</h2>
        <p>Generated on: " . date('d/m/Y H:i:s') . "</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>User ID</th>
                <th>IC Number</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>";
    
    $count = 1;
    $student_count = 0;
    $supervisor_count = 0;
    
    while ($user = mysqli_fetch_assoc($unregistered_result)) {
        $role_class = $user['user_role'] == 'student' ? 'role-student' : 'role-supervisor';
        $ic_display = $user['ic_number'] ? $user['ic_number'] : 'Not Set';
        $content .= "<tr>
            <td>{$count}</td>
            <td>{$user['user_id']}</td>
            <td>{$ic_display}</td>
            <td class='{$role_class}'>" . ucfirst($user['user_role']) . "</td>
        </tr>";
        
        if ($user['user_role'] == 'student') {
            $student_count++;
        } elseif ($user['user_role'] == 'supervisor') {
            $supervisor_count++;
        }
        $count++;
    }
    
    $total = $student_count + $supervisor_count;
    
    $content .= "</tbody>
    </table>
    
    <div class='summary'>
        <h3>Summary</h3>
        <p><strong>Students:</strong> {$student_count}</p>
        <p><strong>Supervisors:</strong> {$supervisor_count}</p>
        <p><strong>Total Unregistered Users:</strong> {$total}</p>
    </div>
    
    <div class='no-print' style='margin-top: 30px; text-align: center;'>
        <button onclick='window.print()' style='padding: 10px 20px; background-color: #001f3f; color: white; border: none; border-radius: 5px; cursor: pointer;'>Print Report</button>
    </div>
</body>
</html>";
    
    // Set headers for download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="unregistered_users_report_' . date('Y-m-d') . '.html"');
    
    echo $content;
    exit();
}

// Handle user registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_user'])) {
    $user_id = $_POST['user_id'];
    $ic_number = $_POST['ic_number'];
    $user_role = $_POST['user_role'];
    $full_name = $_POST['full_name'];
    $user_email = $_POST['user_email'];
    $faculty = $_POST['faculty'];
    $program_or_department = $_POST['program_or_department'];

    // Validate IC number format (should be 12 digits)
    if (!preg_match('/^\d{12}$/', $ic_number)) {
        $error_message = "IC Number must be exactly 12 digits!";
    } else {
        // Check if user_id already exists
        $check_user_id = "SELECT * FROM user WHERE user_id='$user_id'";
        $result_user_id = mysqli_query($conn, $check_user_id);
        
        // Check if ic_number already exists (exclude NULL values)
        $check_ic = "SELECT * FROM user WHERE ic_number='$ic_number' AND ic_number IS NOT NULL";
        $result_ic = mysqli_query($conn, $check_ic);
        
        // Check if email already exists
        $check_email = "SELECT * FROM user WHERE user_email='$user_email' AND user_email IS NOT NULL AND user_email != ''";
        $result_email = mysqli_query($conn, $check_email);
        
        if (mysqli_num_rows($result_user_id) > 0) {
            $error_message = "User ID already exists!";
        } elseif (mysqli_num_rows($result_ic) > 0) {
            $error_message = "IC Number already exists!";
        } elseif (mysqli_num_rows($result_email) > 0) {
            $error_message = "Email already exists!";
        } else {
            // Insert into user table with email set, password NULL (to be set during confirmation)
            $user_query = "INSERT INTO user (user_id, ic_number, user_email, user_password, user_role, is_logged_in) 
                           VALUES ('$user_id', '$ic_number', '$user_email', NULL, '$user_role', 0)";
            
            if (mysqli_query($conn, $user_query)) {
                // Create role-specific record immediately with all details
                if ($user_role == 'student') {
                    $role_query = "INSERT INTO student (user_id, student_name, student_faculty, student_program) 
                                   VALUES ('$user_id', '$full_name', '$faculty', '$program_or_department')";
                } elseif ($user_role == 'supervisor') {
                    $role_query = "INSERT INTO supervisor (user_id, supervisor_name, supervisor_faculty, supervisor_department) 
                                   VALUES ('$user_id', '$full_name', '$faculty', '$program_or_department')";
                }
                
                if (isset($role_query) && mysqli_query($conn, $role_query)) {
                    $success_message = "User created successfully! User can now confirm their account by setting a password using User ID: $user_id and IC Number.";
                } else {
                    // Rollback user creation if role-specific record fails
                    mysqli_query($conn, "DELETE FROM user WHERE user_id='$user_id'");
                    $error_message = "Error creating user account: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error creating user account: " . mysqli_error($conn);
            }
        }
    }
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id_to_delete = $_GET['delete_user'];
    
    // Delete from role-specific table first
    $delete_student = "DELETE FROM student WHERE user_id='$user_id_to_delete'";
    $delete_supervisor = "DELETE FROM supervisor WHERE user_id='$user_id_to_delete'";
    
    mysqli_query($conn, $delete_student);
    mysqli_query($conn, $delete_supervisor);
    
    // Delete from user table
    $delete_user = "DELETE FROM user WHERE user_id='$user_id_to_delete'";
    if (mysqli_query($conn, $delete_user)) {
        $success_message = "User deleted successfully!";
    } else {
        $error_message = "Error deleting user: " . mysqli_error($conn);
    }
}

// Get statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM user WHERE user_role = 'student') as total_students,
        (SELECT COUNT(*) FROM user WHERE user_role = 'supervisor') as total_supervisors,
        (SELECT COUNT(*) FROM thesis) as total_thesis,
        (SELECT COUNT(*) FROM thesis WHERE review_status = 'pending') as pending_thesis,
        (SELECT COUNT(*) FROM thesis WHERE review_status = 'approved') as approved_thesis,
        (SELECT COUNT(*) FROM user WHERE user_password IS NULL OR user_password = '') as unregistered_users
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch all users
$users_query = "SELECT * FROM user ORDER BY user_number DESC";
$users_result = mysqli_query($conn, $users_query);

// Fetch all students with supervisor information
$students_query = "
    SELECT s.*, u.user_email, u.is_logged_in, sv.supervisor_name, sv.supervisor_faculty 
    FROM student s 
    LEFT JOIN user u ON s.user_id = u.user_id 
    LEFT JOIN supervisor sv ON s.supervisor_number = sv.supervisor_number 
    ORDER BY s.student_number DESC
";
$students_result = mysqli_query($conn, $students_query);

// Fetch all supervisors
$supervisors_query = "
    SELECT sv.*, u.user_email, u.is_logged_in,
    (SELECT COUNT(*) FROM student WHERE supervisor_number = sv.supervisor_number) as student_count
    FROM supervisor sv 
    LEFT JOIN user u ON sv.user_id = u.user_id 
    ORDER BY sv.supervisor_number DESC
";
$supervisors_result = mysqli_query($conn, $supervisors_query);

// Fetch all thesis with complete information
$thesis_query = "
    SELECT t.*, s.student_name, s.student_faculty, sv.supervisor_name 
    FROM thesis t 
    LEFT JOIN student s ON t.student_number = s.student_number 
    LEFT JOIN supervisor sv ON t.supervisor_number = sv.supervisor_number 
    ORDER BY t.submission_date DESC
";
$thesis_result = mysqli_query($conn, $thesis_query);

// Fetch supervisors for dropdown
$supervisor_dropdown_query = "SELECT supervisor_number, supervisor_name FROM supervisor ORDER BY supervisor_name";
$supervisor_dropdown_result = mysqli_query($conn, $supervisor_dropdown_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - USIM eThesis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
            font-weight: 300;
        }

        .logout-btn {
            position: absolute;
            top: 25px;
            right: 180px;
        }

        .download-btn {
            position: absolute;
            top: 25px;
            right: 20px;
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.3);
        }

        .download-btn:hover {
            background: linear-gradient(135deg, #44a08d 0%, #4ecdc4 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 205, 196, 0.4);
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            text-align: center;
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .stat-card.students { border-left-color: #4ecdc4; }
        .stat-card.supervisors { border-left-color: #667eea; }
        .stat-card.thesis { border-left-color: #feca57; }
        .stat-card.pending { border-left-color: #ff9ff3; }
        .stat-card.approved { border-left-color: #54a0ff; }
        .stat-card.unregistered { border-left-color: #5f27cd; }

        .stat-number {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #74b9ff;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tabs {
            display: flex;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e8f4f8;
        }

        .tab-button {
            flex: 1;
            padding: 18px 25px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #74b9ff;
            transition: all 0.3s ease;
            position: relative;
            font-size: 15px;
        }

        .tab-button.active {
            color: #667eea;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%);
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .tab-button:hover {
            background: #f8f9ff;
            color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e8f4f8;
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%);
            padding: 25px;
            border-bottom: 1px solid #dee2e6;
        }

        .card-header h3 {
            color: #667eea;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .alert {
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 15px;
            border-left: 5px solid;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1f2eb 0%, #a3e9d0 100%);
            color: #0e6245;
            border-left-color: #4ecdc4;
        }

        .alert-error {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #d63031;
            border-left-color: #fdcb6e;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e8f4f8;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);
            color: #2d3436;
            box-shadow: 0 4px 15px rgba(254, 202, 87, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(253, 121, 168, 0.3);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e8f4f8;
        }

        th {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%);
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { 
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%); 
            color: #2d3436; 
        }
        .status-approved { 
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); 
            color: white; 
        }
        .status-failed { 
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%); 
            color: white; 
        }
        .status-revision { 
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); 
            color: white; 
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-student { 
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); 
        }
        .role-supervisor { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid #e8f4f8;
        }

        .modal-header {
            padding: 25px;
            border-bottom: 1px solid #e8f4f8;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%);
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            color: #667eea;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .modal-body {
            padding: 25px;
        }

        .close {
            color: #74b9ff;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #667eea;
        }

        .info-box {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1f2eb 100%);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border-left: 5px solid #4ecdc4;
        }

        .info-box h4 {
            color: #44a08d;
            margin-bottom: 15px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .logout-btn, .download-btn {
                position: static;
                margin: 10px 5px;
                display: inline-block;
            }
            
            .header {
                text-align: left;
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 Admin Dashboard</h1>
        <p>USIM eThesis System Administration</p>
        <div class="logout-btn">
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
        <div class="download-btn">
            <a href="?download_unregistered_pdf=1" class="download-btn">📄 Download Report</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card students">
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">👨‍🎓 Total Students</div>
            </div>
            <div class="stat-card supervisors">
                <div class="stat-number"><?php echo $stats['total_supervisors']; ?></div>
                <div class="stat-label">👨‍🏫 Total Supervisors</div>
            </div>
            <div class="stat-card thesis">
                <div class="stat-number"><?php echo $stats['total_thesis']; ?></div>
                <div class="stat-label">📄 Total Thesis</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $stats['pending_thesis']; ?></div>
                <div class="stat-label">⏳ Pending Reviews</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number"><?php echo $stats['approved_thesis']; ?></div>
                <div class="stat-label">✅ Approved Thesis</div>
            </div>
            <div class="stat-card unregistered">
                <div class="stat-number"><?php echo $stats['unregistered_users']; ?></div>
                <div class="stat-label">🔄 Unregistered Users</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="showTab('users')">👥 User Management</button>
            <button class="tab-button" onclick="showTab('students')">👨‍🎓 Students</button>
            <button class="tab-button" onclick="showTab('supervisors')">👨‍🏫 Supervisors</button>
            <button class="tab-button" onclick="showTab('thesis')">📄 Thesis Management</button>
            <button class="tab-button" onclick="showTab('assignments')">🔗 Assignments</button>
        </div>

        <!-- User Management Tab -->
        <div id="users" class="tab-content active">
            <!-- Create New User Section -->
            <div class="card">
                <div class="card-header">
                    <h3>➕ Create New User</h3>
                </div>
                <div class="card-body">
                    <p style="color: #6c757d; margin-bottom: 20px;">
                        Create a new user account with all details. The user will only need to confirm their account by setting a password using their User ID and IC Number.
                    </p>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label>User ID:</label>
                                <input type="text" name="user_id" required placeholder="e.g., S2024001, SUP001">
                                <small style="color: #6c757d; font-size: 12px;">Unique identifier for the user</small>
                            </div>
                            <div class="form-group">
                                <label>IC Number:</label>
                                <input type="text" name="ic_number" required placeholder="e.g., 950123145678" pattern="\d{12}" maxlength="12" minlength="12">
                                <small style="color: #6c757d; font-size: 12px;">National identification number (exactly 12 digits)</small>
                            </div>
                            <div class="form-group">
                                <label>Role:</label>
                                <select name="user_role" id="user_role" required onchange="updateProgramLabel()">
                                    <option value="">Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name:</label>
                                <input type="text" name="full_name" required placeholder="Enter full name">
                                <small style="color: #6c757d; font-size: 12px;">Full name of the user</small>
                            </div>
                            <div class="form-group">
                                <label>Email Address:</label>
                                <input type="email" name="user_email" required placeholder="e.g., user@usim.edu.my">
                                <small style="color: #6c757d; font-size: 12px;">User's email address</small>
                            </div>
                            <div class="form-group">
                                <label>Faculty:</label>
                                <input type="text" name="faculty" required placeholder="e.g., Faculty of Science and Technology">
                                <small style="color: #6c757d; font-size: 12px;">User's faculty</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label id="program_label">Program / Department:</label>
                                <input type="text" name="program_or_department" required placeholder="Program (students) or Department (supervisors)" id="program_input">
                                <small style="color: #6c757d; font-size: 12px;" id="program_hint">Program for students or Department for supervisors</small>
                            </div>
                        </div>

                        <div class="info-box">
                            <h4 style="margin: 0 0 10px 0; color: #17a2b8;">📋 What happens next:</h4>
                            <ul style="margin: 0; padding-left: 20px; color: #6c757d;">
                                <li>User account will be created with all details</li>
                                <li>User will receive their User ID and IC Number</li>
                                <li>User must confirm account by setting password on login page</li>
                                <li>User enters: User ID, IC Number, Password to confirm</li>
                                <li>After confirmation, user can login normally</li>
                            </ul>
                        </div>

                        <button type="submit" name="register_user" class="btn btn-success">Create User Account</button>
                    </form>
                </div>
            </div>

            <!-- All Users List -->
            <div class="card">
                <div class="card-header">
                    <h3>👥 All Users</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>IC Number</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registration Status</th>
                                    <th>Login Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($users_result, 0);
                                while ($user = mysqli_fetch_assoc($users_result)): 
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['user_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['ic_number'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                            if (empty($user['user_email'])) {
                                                echo '<span style="color: #6c757d; font-style: italic;">Not registered</span>';
                                            } else {
                                                echo htmlspecialchars($user['user_email']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['user_role']; ?>">
                                                <?php echo ucfirst($user['user_role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (empty($user['user_password'])): ?>
                                                <span class="status-badge status-pending">Pending Confirmation</span>
                                            <?php else: ?>
                                                <span class="status-badge status-approved">Confirmed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_logged_in'] == 1): ?>
                                                <span style="color: #4ecdc4; font-weight: 500;">🟢 Online</span>
                                            <?php else: ?>
                                                <span style="color: #74b9ff;">⚫ Offline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?delete_user=<?php echo $user['user_id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this user?')" 
                                               class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                                🗑️ Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Tab -->
        <div id="students" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>👨‍🎓 Student Management</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Faculty</th>
                                    <th>Program</th>
                                    <th>Supervisor</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['user_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_faculty']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_program']); ?></td>
                                        <td>
                                            <?php if ($student['supervisor_name']): ?>
                                                <span style="color: #667eea; font-weight: 500;"><?php echo htmlspecialchars($student['supervisor_name']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #fd79a8; font-style: italic;">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['user_email'] ?? 'Not registered'); ?></td>
                                        <td>
                                            <?php if ($student['is_logged_in'] == 1): ?>
                                                <span style="color: #4ecdc4; font-weight: 500;">🟢 Online</span>
                                            <?php else: ?>
                                                <span style="color: #74b9ff;">⚫ Offline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="openAssignModal(<?php echo $student['student_number']; ?>, '<?php echo htmlspecialchars($student['student_name']); ?>')" 
                                                    class="btn btn-warning" style="padding: 6px 12px; font-size: 12px;">
                                                🔗 Assign Supervisor
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supervisors Tab -->
        <div id="supervisors" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>👨‍🏫 Supervisor Management</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Supervisor ID</th>
                                    <th>Name</th>
                                    <th>Faculty</th>
                                    <th>Department</th>
                                    <th>Students Count</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($supervisor = mysqli_fetch_assoc($supervisors_result)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($supervisor['user_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($supervisor['supervisor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($supervisor['supervisor_faculty']); ?></td>
                                        <td><?php echo htmlspecialchars($supervisor['supervisor_department']); ?></td>
                                        <td>
                                            <span class="status-badge status-approved"><?php echo $supervisor['student_count']; ?> Students</span>
                                        </td>
                                        <td><?php echo htmlspecialchars($supervisor['user_email'] ?? 'Not registered'); ?></td>
                                        <td>
                                            <?php if ($supervisor['is_logged_in'] == 1): ?>
                                                <span style="color: #4ecdc4; font-weight: 500;">🟢 Online</span>
                                            <?php else: ?>
                                                <span style="color: #74b9ff;">⚫ Offline</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thesis Management Tab -->
        <div id="thesis" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>📄 Thesis Management</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Thesis ID</th>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Supervisor</th>
                                    <th>Submission Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($thesis = mysqli_fetch_assoc($thesis_result)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $thesis['thesis_id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($thesis['thesis_title']); ?></strong>
                                            <?php if (!empty($thesis['thesis_remark'])): ?>
                                                <br><small style="color: #6c757d;"><?php echo htmlspecialchars($thesis['thesis_remark']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($thesis['student_name']); ?></div>
                                            <small style="color: #6c757d;"><?php echo htmlspecialchars($thesis['student_faculty']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($thesis['supervisor_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($thesis['submission_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $thesis['review_status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $thesis['review_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="openReviewModal(<?php echo $thesis['thesis_id']; ?>, '<?php echo htmlspecialchars($thesis['thesis_title']); ?>', '<?php echo $thesis['review_status']; ?>', '<?php echo htmlspecialchars($thesis['reviewer_remarks'] ?? ''); ?>')" 
                                                    class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                                📝 Review
                                            </button>
                                            <a href="<?php echo htmlspecialchars($thesis['thesis_path']); ?>" target="_blank" 
                                               class="btn btn-success" style="padding: 6px 12px; font-size: 12px;">
                                                📄 View PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div id="assignments" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3>🔗 Quick Assignment Tools</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                        <div style="padding: 25px; background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%); border-radius: 15px; border-left: 5px solid #667eea;">
                            <h4 style="color: #667eea; margin-bottom: 18px; font-weight: 600;">📊 Quick Statistics</h4>
                            <p style="margin-bottom: 12px; color: #2d3436;"><strong>Unassigned Students:</strong> 
                                <?php 
                                $unassigned_query = "SELECT COUNT(*) as count FROM student WHERE supervisor_number IS NULL";
                                $unassigned_result = mysqli_query($conn, $unassigned_query);
                                $unassigned = mysqli_fetch_assoc($unassigned_result);
                                echo $unassigned['count'];
                                ?>
                            </p>
                            <p style="margin-bottom: 12px; color: #2d3436;"><strong>Available Supervisors:</strong> <?php echo $stats['total_supervisors']; ?></p>
                            <p style="margin-bottom: 0; color: #2d3436;"><strong>Thesis Awaiting Review:</strong> <?php echo $stats['pending_thesis']; ?></p>
                        </div>
                        
                        <div style="padding: 25px; background: linear-gradient(135d, #d1f2eb 0%, #e8f4f8 100%); border-radius: 15px; border-left: 5px solid #4ecdc4;">
                            <h4 style="color: #44a08d; margin-bottom: 18px; font-weight: 600;">📈 System Health</h4>
                            <p style="margin-bottom: 12px; color: #2d3436;"><strong>Registered Users:</strong> <?php echo ($stats['total_students'] + $stats['total_supervisors'] - $stats['unregistered_users']); ?></p>
                            <p style="margin-bottom: 12px; color: #2d3436;"><strong>Completed Registrations:</strong> 
                                <?php echo round((($stats['total_students'] + $stats['total_supervisors'] - $stats['unregistered_users']) / ($stats['total_students'] + $stats['total_supervisors'])) * 100, 1); ?>%
                            </p>
                            <p style="margin-bottom: 0; color: #2d3436;"><strong>Total Submissions:</strong> <?php echo $stats['total_thesis']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Supervisor Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔗 Assign Supervisor</h3>
                <span class="close" onclick="closeModal('assignModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="student_number" name="student_number">
                    <div class="form-group">
                        <label>Student:</label>
                        <input type="text" id="student_name" readonly style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Select Supervisor:</label>
                        <select name="supervisor_number" required>
                            <option value="">Choose a supervisor...</option>
                            <?php 
                            mysqli_data_seek($supervisor_dropdown_result, 0);
                            while ($sup = mysqli_fetch_assoc($supervisor_dropdown_result)): 
                            ?>
                                <option value="<?php echo $sup['supervisor_number']; ?>">
                                    <?php echo htmlspecialchars($sup['supervisor_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_supervisor" class="btn btn-success">Assign Supervisor</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Review Thesis Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Review Thesis</h3>
                <span class="close" onclick="closeModal('reviewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" id="thesis_id" name="thesis_id">
                    <div class="form-group">
                        <label>Thesis Title:</label>
                        <input type="text" id="thesis_title" readonly style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Review Status:</label>
                        <select name="review_status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="failed">Failed</option>
                            <option value="revision_required">Revision Required</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reviewer Remarks:</label>
                        <textarea name="reviewer_remarks" rows="4" placeholder="Enter your review comments..."></textarea>
                    </div>
                    <button type="submit" name="update_thesis_review" class="btn btn-primary">Update Review</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab and mark button as active
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Modal functions
        function openAssignModal(studentNumber, studentName) {
            document.getElementById('student_number').value = studentNumber;
            document.getElementById('student_name').value = studentName;
            document.getElementById('assignModal').style.display = 'block';
        }

        function openReviewModal(thesisId, thesisTitle, currentStatus, currentRemarks) {
            document.getElementById('thesis_id').value = thesisId;
            document.getElementById('thesis_title').value = thesisTitle;
            document.querySelector('[name="review_status"]').value = currentStatus;
            document.querySelector('[name="reviewer_remarks"]').value = currentRemarks;
            document.getElementById('reviewModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Update program label based on role
        function updateProgramLabel() {
            const role = document.getElementById('user_role').value;
            const label = document.getElementById('program_label');
            const input = document.getElementById('program_input');
            const hint = document.getElementById('program_hint');
            
            if (role === 'student') {
                label.textContent = 'Program:';
                input.placeholder = 'e.g., Computer Science';
                hint.textContent = 'Student\'s program';
            } else if (role === 'supervisor') {
                label.textContent = 'Department:';
                input.placeholder = 'e.g., Computer Science Department';
                hint.textContent = 'Supervisor\'s department';
            } else {
                label.textContent = 'Program / Department:';
                input.placeholder = 'Program (students) or Department (supervisors)';
                hint.textContent = 'Program for students or Department for supervisors';
            }
        }
    </script>
</body>
</html>
