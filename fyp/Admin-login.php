<?php
session_start();

// Hardcoded admin credentials
$admin_username = "admin";
$admin_password_hash = "0192023a7bbd73250516f069df18b500"; // MD5 hash of "admin123"

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password_hash = md5($password); // Hash the input password

    if ($username === $admin_username && $password_hash === $admin_password_hash) {
        // Set admin session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // Redirect to admin dashboard
        header("Location: Admin-dashboard.php");
        exit();
    } else {
        $error_message = "Invalid admin credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - USIM eThesis</title>
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
            background: #dc3545;
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
            border-color: #dc3545;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #dc3545;
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
            background: #c82333;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 2px solid #dc3545;
            font-size: 13px;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .back-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #c82333;
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
                <h2>Admin Access</h2>
                <p>Universiti Sains Islam Malaysia<br>
                eThesis System Administration<br>
                Secure Administrative Portal</p>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="form-side">
            <!-- Header Section -->
            <div class="header">
                <img src="img/Logo-usim.png" alt="USIM Logo" class="logo">
                <h1>Admin Portal</h1>
                <p>System Administration</p>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        ❌ <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="adminLoginForm">
                    <div class="form-group">
                        <label for="username">Admin Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Enter admin username"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password">Admin Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter admin password"
                               required>
                    </div>

                    <button type="submit" class="btn" id="adminLoginBtn">
                        Login as Administrator
                    </button>
                </form>

                <!-- Back Link -->
                <div class="back-link">
                    <a href="Login-page.php">
                        ← Back to User Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const btn = document.getElementById('adminLoginBtn');
            btn.innerHTML = '<div class="loading"></div> Authenticating...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
