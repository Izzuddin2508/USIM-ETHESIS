<?php
// Password Migration Script - Convert existing plaintext passwords to MD5 hashes
// RUN THIS SCRIPT ONLY ONCE to migrate existing passwords

include("Configuration.php");

echo "<h2>Password Migration Script</h2>";
echo "<p>This script will convert all existing plaintext passwords to MD5 hashes.</p>";

// Get all users with non-empty passwords
$query = "SELECT user_id, user_password FROM user WHERE user_password IS NOT NULL AND user_password != ''";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<h3>Found " . mysqli_num_rows($result) . " users with passwords to migrate:</h3>";
    echo "<ul>";
    
    $migrated_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $user_id = $row['user_id'];
        $current_password = $row['user_password'];
        
        // Check if password is already hashed (MD5 hashes are 32 characters long)
        if (strlen($current_password) == 32 && ctype_xdigit($current_password)) {
            echo "<li>User $user_id: Password already hashed (skipped)</li>";
        } else {
            // Hash the plaintext password
            $hashed_password = md5($current_password);
            
            // Update the password in database
            $update_query = "UPDATE user SET user_password='$hashed_password' WHERE user_id='$user_id'";
            
            if (mysqli_query($conn, $update_query)) {
                echo "<li>User $user_id: Password successfully migrated to MD5 hash</li>";
                $migrated_count++;
            } else {
                echo "<li style='color: red;'>User $user_id: Failed to migrate password - " . mysqli_error($conn) . "</li>";
            }
        }
    }
    
    echo "</ul>";
    echo "<h3>Migration completed! $migrated_count passwords were successfully migrated.</h3>";
    
} else {
    echo "<p>No passwords found to migrate.</p>";
}

echo "<br><hr>";
echo "<p><strong>Important:</strong> After running this script successfully, you can delete this file for security.</p>";
echo "<p><strong>Note:</strong> All new passwords will now be automatically hashed with MD5.</p>";

mysqli_close($conn);
?>