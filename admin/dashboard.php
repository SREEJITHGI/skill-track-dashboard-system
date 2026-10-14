
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

// Get total roles
$query = "SELECT COUNT(*) as total_roles FROM job_roles";
$result = $conn->query($query);
$total_roles = $result ? $result->fetch_assoc()['total_roles'] : 0;

// Get expiring certifications count (<= 30 days)
$query = "SELECT COUNT(*) as expiring_count FROM employee_certifications WHERE expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
$result = $conn->query($query);
$expiring_certs = $result ? $result->fetch_assoc()['expiring_count'] : 0;
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

            <?php if ($expiring_certs > 0): ?>
                <div class="alert-banner alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Action Needed:</strong> <?php echo $expiring_certs; ?> employee certification(s) require renewal within 30 days. 
                        <a href="certifications.php" style="text-decoration: underline; font-weight: 700;">View Expiring Credentials</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Employees</span>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-description">Registered staff</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Skills</span>
                        <i class="fas fa-brain stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_skills; ?></div>
                    <div class="stat-description">Catalog inventory</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Role Benchmarks</span>
                        <i class="fas fa-bullseye stat-icon" style="color: #2563eb;"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_roles; ?></div>
                    <div class="stat-description"><a href="roles.php" style="color: inherit; text-decoration: underline;">Configure</a></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Skill Gap Analysis</span>
                        <i class="fas fa-chart-pie stat-icon" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-value" style="font-size: 1.25rem;"><a href="skill_gap.php" style="color: var(--primary-color);">Open Radar</a></div>
                    <div class="stat-description">Staff vs. targets</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Expiring Certs</span>
                        <i class="fas fa-clock stat-icon" style="color: <?php echo $expiring_certs > 0 ? '#f59e0b' : '#64748b'; ?>;"></i>
                    </div>
                    <div class="stat-value" style="color: <?php echo $expiring_certs > 0 ? '#f59e0b' : 'inherit'; ?>;"><?php echo $expiring_certs; ?></div>
                    <div class="stat-description"><a href="certifications.php" style="color: inherit; text-decoration: underline;">Audit renewals</a></div>
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
