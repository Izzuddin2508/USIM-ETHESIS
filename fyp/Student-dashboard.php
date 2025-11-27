<?php
session_start();
include 'Configuration.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login-page.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student details + supervisor info
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

$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
} else {
    echo "<h3>No student data found for user ID: $user_id</h3>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - USIM eThesis</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: white;
        }

        /* Sidebar */
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #B2BEB5; /* navy blue */
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 5px solid #003366;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar img {
            width: 100px;
            height: auto;
            margin-top: 20px;
            border-radius: 5px;
        }

        .student-info {
            background-color: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
            width: 90%;
            font-size: 14px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            width: 90%;
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background-color: #003366;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background-color: #004080;
        }

        /* Main content */
        .main {
            margin-left: 270px;
            padding: 30px;
            border-left: 3px solid #001f3f;
        }

        h1 {
            color: #001f3f;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 14px;
        }

        .logout-btn {
            margin-top: auto;
            margin-bottom: 20px;
        }

        .logout-btn input {
            background-color: #c82333;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-btn input:hover {
            background-color: #a71d2a;
        }
    </style>

    <script>
        // Force URL hash for reload safety
        if (!window.location.hash) {
            window.location = window.location + '#';
            window.location.reload();
        }

        // Greeting message
        window.onload = function() {
            const hours = new Date().getHours();
            let greeting = "";

            if (hours < 12) greeting = "Good Morning";
            else if (hours < 18) greeting = "Good Afternoon";
            else greeting = "Good Evening";

            document.getElementById("greeting").innerText = greeting + ", <?php echo addslashes($row['student_name']); ?>!";
        };
    </script>
</head>
<body>

    <div class="sidebar">
        <img src="img/Logo-usim.png" alt="USIM Logo">

        <div class="student-info">
            <p><strong>ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['student_name']); ?></p>
            <p><strong>Program:</strong> <?php echo htmlspecialchars($row['student_program']); ?></p>
            <p><strong>Faculty:</strong> <?php echo htmlspecialchars($row['student_faculty']); ?></p>
            <p><strong>Supervisor:</strong> 
                <?php echo !empty($row['supervisor_name']) ? htmlspecialchars($row['supervisor_name']) : 'Not Assigned'; ?>
            </p>
        </div>

        <a href="#">Submit</a>
        <a href="#">Track</a>
        <a href="#">Database</a>
        <a href="#">Edit Profile</a>

        <div class="logout-btn">
            <form method="POST" action="logout.php">
                <input type="submit" name="logout" value="Logout">
            </form>
        </div>
    </div>

    <div class="main">
        <h1 id="greeting"></h1>
        <p>Welcome to your USIM eThesis Student Dashboard.</p>

        <footer>
            <p>© 2025 Universiti Sains Islam Malaysia - eThesis System</p>
        </footer>
    </div>

</body>
</html>
