
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: employee/dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Skill Tracking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h1>Employee Skill Tracking System</h1>
            <p>Please select your login type</p>
        </div>
        <div class="login-options">
            <a href="admin_login.php" class="login-card">
                <div class="login-icon"><i class="fas fa-user-tie"></i></div>
                <h2>Admin Login</h2>
                <p>Login as HR or Management</p>
            </a>
            <a href="employee_login.php" class="login-card">
                <div class="login-icon"><i class="fas fa-user"></i></div>
                <h2>Employee Login</h2>
                <p>Login to view your skills and progress</p>
            </a>
        </div>
    </div>
</body>
</html>
