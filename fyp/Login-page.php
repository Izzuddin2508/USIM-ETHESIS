<?php
session_start();
include("Configuration.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // Account confirmation process
        $user_id = $_POST['user_id'];
        $ic_number = $_POST['ic_number'];
        $user_password = $_POST['user_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords match
        if ($user_password !== $confirm_password) {
            echo "<script>alert('❌ Passwords do not match.');</script>";
        } else {
            // Check if user_id exists and IC number matches
            $check_query = "SELECT * FROM user WHERE user_id='$user_id'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) == 1) {
                $user_data = mysqli_fetch_assoc($check_result);
                
                // Verify IC number matches
                if ($user_data['ic_number'] != $ic_number) {
                    echo "<script>alert('❌ IC Number does not match. Please verify your details.');</script>";
                } else {
                    // Check if user already has password (already confirmed)
                    if (!empty($user_data['user_password'])) {
                        echo "<script>alert('❌ This account is already confirmed. Please login instead.');</script>";
                    } else {
                        // Update user with hashed password
                        $hashed_password = md5($user_password);
                        $update_query = "UPDATE user SET user_password='$hashed_password' WHERE user_id='$user_id'";
                        
                        if (mysqli_query($conn, $update_query)) {
                            echo "<script>alert('✅ Account confirmed successfully! You can now login.'); window.location.href='Login-page.php';</script>";
                        } else {
                            echo "<script>alert('❌ Confirmation failed. Please try again.');</script>";
                        }
                    }
                }
            } else {
                echo "<script>alert('❌ Invalid user ID. Please contact admin.');</script>";
            }
        }
    } else {
        // Login process
        $login_input = $_POST['login_input']; // can be ID or Email
        $password = $_POST['password'];
        $hashed_password = md5($password); // Hash the input password for comparison

        // Query: allow login by user_id or user_email with hashed password
        $query = "SELECT * FROM user 
                  WHERE (user_id='$login_input' OR user_email='$login_input') 
                  AND user_password='$hashed_password'";

        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Check if user already logged in
            if ($row['is_logged_in'] == 1) {
                echo "<script>alert('⚠️ This account is already logged in elsewhere.');</script>";
            } else {
                // Mark as logged in
                mysqli_query($conn, "UPDATE user SET is_logged_in=1 WHERE user_id='{$row['user_id']}'");

                // Store session data
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_role'] = $row['user_role'];

                // Redirect to unified dashboard
                header("Location: Dashboard.php");
                exit();
            }
        } else {
            echo "<script>alert('❌ Invalid ID/Email or Password.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USIM eThesis Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            position: relative;
            display: flex;
            min-height: 600px;
        }

        .image-section {
            flex: 1;
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.8)), url('https://www.usim.edu.my/wp-content/uploads/2021/09/USIM-1116x558-1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 40px;
        }

        .image-overlay {
            background: rgba(0, 31, 63, 0.3);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .image-overlay h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .image-overlay p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .form-side {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
            border-radius: 10px;
            background: white;
            padding: 8px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .form-container {
            padding: 30px;
        }

        .toggle-buttons {
            display: flex;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 25px;
            position: relative;
        }

        .toggle-btn {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .toggle-btn.active {
            color: white;
        }

        .toggle-slider {
            position: absolute;
            top: 4px;
            left: 4px;
            width: calc(50% - 4px);
            height: calc(100% - 8px);
            background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
            border-radius: 8px;
            transition: transform 0.3s ease;
            z-index: 1;
        }

        .toggle-slider.register {
            transform: translateX(100%);
        }

        .form-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #001f3f;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 31, 63, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .info-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }

        .admin-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .admin-link a {
            color: #001f3f;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .admin-link a:hover {
            background: #f8f9fa;
            color: #003366;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #e9ecef;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 400px;
            }
            
            .image-section {
                min-height: 200px;
                padding: 20px;
            }
            
            .image-overlay h2 {
                font-size: 22px;
            }
            
            .image-overlay p {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .header {
                padding: 25px 20px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .image-section {
                min-height: 150px;
                padding: 15px;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Image Section -->
        <div class="image-section">
            <div class="image-overlay">
                <h2>Welcome to USIM</h2>
                <p>Universiti Sains Islam Malaysia<br>
                Excellence in Islamic Education<br>
                and Research Innovation</p>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="form-side">
            <!-- Header Section -->
            <div class="header">
                <img src="img/Logo-usim.png" alt="USIM Logo" class="logo">
                <h1>eThesis Systems</h1>
                <p>Digital Thesis Management Platform</p>
            </div>

        <!-- Form Container -->
        <div class="form-container">
            <!-- Toggle Buttons -->
            <div class="toggle-buttons">
                <div class="toggle-slider" id="toggleSlider"></div>
                <button type="button" class="toggle-btn active" id="loginBtn" onclick="showLogin()">
                    Login
                </button>
                <button type="button" class="toggle-btn" id="registerBtn" onclick="showRegister()">
                    Register
                </button>
            </div>

            <!-- Login Form -->
            <div id="loginForm" class="form-section active">
                <form method="POST" action="" id="loginFormSubmit">
                    <div class="form-group">
                        <label for="login_input">Email or User ID</label>
                        <input type="text" 
                               id="login_input" 
                               name="login_input" 
                               class="form-control" 
                               placeholder="Enter your email or user ID"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required>
                    </div>

                    <button type="submit" class="btn" id="loginSubmitBtn">
                        Login
                    </button>
                </form>
            </div>

            <!-- Register Form (Account Confirmation) -->
            <div id="registerForm" class="form-section">
                <form method="POST" action="" id="registerFormSubmit">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="info-text" style="margin-bottom: 20px; background: #e8f4f8; border-left-color: #667eea;">
                        📋 <strong>Account Confirmation</strong><br>
                        
                    </div>
                    
                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input type="text" 
                               id="user_id" 
                               name="user_id" 
                               class="form-control" 
                               placeholder="e.g., S2024001, SUP001"
                               required>
                        <div class="info-text">
                            💡 Your User ID is provided by the admin
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ic_number">IC Number</label>
                        <input type="text" 
                               id="ic_number" 
                               name="ic_number" 
                               class="form-control" 
                               placeholder="e.g., 950123145678"
                               pattern="\d{12}"
                               maxlength="12"
                               minlength="12"
                               required>
                        <div class="info-text">
                            🔐 Enter your 12-digit IC Number for verification
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="user_password">Password</label>
                        <input type="password" 
                               id="user_password" 
                               name="user_password" 
                               class="form-control" 
                               placeholder="Create a secure password"
                               minlength="6"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               placeholder="Confirm your password"
                               required>
                    </div>

                    <button type="submit" class="btn" id="registerSubmitBtn">
                        Confirm Account
                    </button>

                    <div class="info-text">
                        🔒 Password must be at least 6 characters<br>
                        📞 Contact admin if you don't have your User ID or IC Number
                    </div>
                </form>
            </div>

            <!-- Admin Link -->
            <div class="admin-link">
                <a href="Admin-login.php">
                    🔐 Admin Login
                </a>
            </div>
        </div>
        </div>
    </div>

    <script>
        // Prevent form resubmission
        if (!window.location.hash) {
            window.location = window.location + '#';
            window.location.reload();
        }

        // Toggle between login and register forms
        function showLogin() {
            document.getElementById('loginForm').classList.add('active');
            document.getElementById('registerForm').classList.remove('active');
            document.getElementById('loginBtn').classList.add('active');
            document.getElementById('registerBtn').classList.remove('active');
            document.getElementById('toggleSlider').classList.remove('register');
        }

        function showRegister() {
            document.getElementById('loginForm').classList.remove('active');
            document.getElementById('registerForm').classList.add('active');
            document.getElementById('loginBtn').classList.remove('active');
            document.getElementById('registerBtn').classList.add('active');
            document.getElementById('toggleSlider').classList.add('register');
        }

        // Form submission with loading states
        document.getElementById('loginFormSubmit').addEventListener('submit', function() {
            const btn = document.getElementById('loginSubmitBtn');
            btn.innerHTML = '<div class="loading"></div> Logging in...';
            btn.disabled = true;
        });

        document.getElementById('registerFormSubmit').addEventListener('submit', function() {
            const btn = document.getElementById('registerSubmitBtn');
            btn.innerHTML = '<div class="loading"></div> Confirming Account...';
            btn.disabled = true;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('user_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.style.borderColor = '#dc3545';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '#e9ecef';
            }
        });

        // Initialize
        window.onload = function() {
            showLogin();
        }
    </script>
</body>
</html>
