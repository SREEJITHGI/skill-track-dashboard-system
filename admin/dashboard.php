
<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

// Get total employees
$query = "SELECT COUNT(*) as total_employees FROM employees";
$result = $conn->query($query);
$total_employees = $result->fetch_assoc()['total_employees'];

// Get total skills
$query = "SELECT COUNT(*) as total_skills FROM skills";
$result = $conn->query($query);
$total_skills = $result->fetch_assoc()['total_skills'];

// Get total certifications
$query = "SELECT COUNT(*) as total_certifications FROM certifications";
$result = $conn->query($query);
$total_certifications = $result->fetch_assoc()['total_certifications'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Skill Tracking System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Employees</span>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-description">Registered in the system</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Skills</span>
                        <i class="fas fa-brain stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_skills; ?></div>
                    <div class="stat-description">Being tracked</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Certifications</span>
                        <i class="fas fa-certificate stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_certifications; ?></div>
                    <div class="stat-description">Registered in the system</div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Activities</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Activity</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT a.*, e.first_name, e.last_name 
                                  FROM activities a 
                                  JOIN employees e ON a.employee_id = e.id
                                  ORDER BY a.activity_date DESC LIMIT 5";
                        $result = $conn->query($query);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['activity_description']) . '</td>';
                                echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($row['activity_date']))) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" style="text-align: center;">No recent activities</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
