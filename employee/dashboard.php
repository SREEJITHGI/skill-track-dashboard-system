
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
$cert_count = $result->fetch_assoc()['cert_count'];
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
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Your Skills</span>
                        <i class="fas fa-brain stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $skill_count; ?></div>
                    <div class="stat-description">Skills in your profile</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Your Certifications</span>
                        <i class="fas fa-certificate stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $cert_count; ?></div>
                    <div class="stat-description">Certifications achieved</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Employee ID</span>
                        <i class="fas fa-id-card stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($_SESSION['employee_id']); ?></div>
                    <div class="stat-description">Your unique identifier</div>
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
