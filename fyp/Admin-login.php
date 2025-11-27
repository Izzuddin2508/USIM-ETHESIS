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
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.8), rgba(200, 35, 51, 0.8)), url('https://www.usim.edu.my/wp-content/uploads/2021/09/USIM-1116x558-1.jpg');
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
            background: rgba(220, 53, 69, 0.3);
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-container {
            padding: 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            border-color: #dc3545;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .back-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            background: #f8f9fa;
            color: #c82333;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #e9ecef;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
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
