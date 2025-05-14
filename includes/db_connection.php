
<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "skill_tracking_system";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
