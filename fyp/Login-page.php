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
            background: url('img/usim.jpg') no-repeat center/cover fixed;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        .image-section {
            display: none;
        }

        .form-side {
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #2a5298;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }

        .logo {
            width: 70px;
            height: auto;
            margin: 0 auto 15px;
            border-radius: 8px;
            background: white;
            padding: 6px;
            display: block;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 13px;
        }

        .form-container {
            padding: 25px;
        }

        .toggle-buttons {
            display: flex;
            background: #f0f0f0;
            border-radius: 8px;
            padding: 3px;
            margin-bottom: 20px;
            position: relative;
        }

        .toggle-btn {
            flex: 1;
            background: transparent;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            font-size: 14px;
        }

        .toggle-btn.active {
            color: white;
        }

        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: calc(50% - 3px);
            height: calc(100% - 6px);
            background: #2a5298;
            border-radius: 6px;
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
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2a5298;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 8px;
        }

        .btn:hover {
            background: #1e3c72;
        }

        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
            border-left: 2px solid #2a5298;
        }

        .admin-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .admin-link a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .admin-link a:hover {
            color: #1e3c72;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 6px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .container {
                border-radius: 8px;
            }

            .header {
                padding: 20px;
                border-radius: 8px 8px 0 0;
            }

            .form-container {
                padding: 20px;
            }
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
