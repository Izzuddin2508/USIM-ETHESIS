<?php
session_start();
include 'Configuration.php'; // database connection file

// If not logged in, redirect back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: Login-page.php");
    exit();
}

// Get logged in user_id
$user_id = $_SESSION['user_id'];

// Fetch supervisor details using JOIN
$query = "
    SELECT s.supervisor_name, s.supervisor_faculty, s.supervisor_department, u.user_email 
    FROM supervisor s
    INNER JOIN user u ON s.user_id = u.user_id
    WHERE s.user_id = '$user_id'
";

$result = mysqli_query($conn, $query);

// Check if data found
if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
} else {
    echo "<h3>No supervisor data found for user ID: $user_id</h3>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supervisor Dashboard</title>
</head>
<body>
    <h2>Supervisor Dashboard</h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['supervisor_name']); ?></p>
    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($row['supervisor_faculty']); ?></p>
    <p><strong>Department:</strong> <?php echo htmlspecialchars($row['supervisor_department']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['user_email']); ?></p>

    <br>
    <form method="POST" action="logout.php">
        <input type="submit" name="logout" value="Logout">
    </form>
</body>
</html>
