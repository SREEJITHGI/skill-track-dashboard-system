
<?php
include '../includes/employee_auth_check.php';
include '../includes/db_connection.php';

$employee_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Skills - Skill Tracking System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>My Skills</h1>
                <div class="user-info">
                    <span class="user-name">Employee: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">My Skill Set</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Skill</th>
                            <th>Category</th>
                            <th>Proficiency</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT es.*, s.skill_name, s.category 
                                  FROM employee_skills es
                                  JOIN skills s ON es.skill_id = s.id
                                  WHERE es.employee_id = ?
                                  ORDER BY es.proficiency_level DESC";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $employee_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['skill_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                                echo '<td>';
                                echo '<div class="skill-progress">';
                                echo '<div class="progress-bar" style="width: ' . ($row['proficiency_level'] * 10) . '%;"></div>';
                                echo '</div>';
                                echo '<span style="font-size: 0.8rem;">' . $row['proficiency_level'] . '/10</span>';
                                echo '</td>';
                                echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($row['last_updated']))) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center;">No skills found in your profile</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Skill Development Timeline</h2>
                </div>
                
                <div style="padding: 1rem;">
                    <p>Timeline visualization will be shown here. This displays your skill progression over time.</p>
                    <!-- In a real application, you would implement a chart/timeline visualization here -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
