
<?php
include '../includes/employee_auth_check.php';
include '../includes/db_connection.php';

$employee_id = $_SESSION['user_id'];

// Get employee information
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Count employee skills
$query = "SELECT COUNT(*) as skill_count FROM employee_skills WHERE employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$skill_count = $result->fetch_assoc()['skill_count'];

// Count employee certifications
$query = "SELECT COUNT(*) as cert_count FROM employee_certifications WHERE employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
// Check expiring certifications
$query = "SELECT COUNT(*) as exp_count FROM employee_certifications WHERE employee_id = ? AND expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$my_expiring = $stmt->get_result()->fetch_assoc()['exp_count'];

// Get target role name
$query = "SELECT r.role_name FROM employees e LEFT JOIN job_roles r ON e.target_role_id = r.id WHERE e.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$target_role_res = $stmt->get_result()->fetch_assoc();
$my_target_role = !empty($target_role_res['role_name']) ? $target_role_res['role_name'] : 'Not Assigned';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Skill Tracking System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>Employee Dashboard</h1>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if ($my_expiring > 0): ?>
                <div class="alert-banner alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Notice:</strong> You have <?php echo $my_expiring; ?> certification(s) expiring within 30 days. 
                        <a href="my_certifications.php" style="text-decoration: underline; font-weight: 700;">Check My Credentials</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Your Skills</span>
                        <i class="fas fa-brain stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $skill_count; ?></div>
                    <div class="stat-description"><a href="my_skills.php" style="color: inherit; text-decoration: underline;">View & Update</a></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Target Role</span>
                        <i class="fas fa-bullseye stat-icon" style="color: #2563eb;"></i>
                    </div>
                    <div class="stat-value" style="font-size: 1.15rem; color: #2563eb;"><?php echo htmlspecialchars($my_target_role); ?></div>
                    <div class="stat-description"><a href="skill_gap.php" style="color: inherit; text-decoration: underline;">View Gap Analysis</a></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Certifications</span>
                        <i class="fas fa-certificate stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $cert_count; ?></div>
                    <div class="stat-description"><a href="my_certifications.php" style="color: inherit; text-decoration: underline;">Portfolio</a></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Upskilling Courses</span>
                        <i class="fas fa-graduation-cap stat-icon" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-value"><a href="training.php" style="color: var(--success-color);">Explore</a></div>
                    <div class="stat-description">Close your skill gaps</div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
                    </div>
                    
                    <div>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position']); ?></p>
                    </div>
                    
                    <div>
                        <p><strong>Join Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($employee['join_date']))); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Updates</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM activities WHERE employee_id = ? ORDER BY activity_date DESC LIMIT 5";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $employee_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['activity_description']) . '</td>';
                                echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($row['activity_date']))) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="2" style="text-align: center;">No recent activities</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
