<?php
session_start();

// Update database to mark user as logged out (only for regular users, not admin)
if (isset($_SESSION['user_id'])) {
    include 'Configuration.php';
    $user_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE user SET is_logged_in=0 WHERE user_id='$user_id'");
}

// Destroy session
session_destroy();

// Redirect based on who was logged in
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: Admin-login.php");
} else {
    header("Location: Login-page.php");
}
exit();
?>
