
<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

// Handle form submission for adding new skill
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_skill'])) {
    $skill_name = $_POST['skill_name'];
    $skill_category = $_POST['skill_category'];
    $description = $_POST['description'];
    
    // Check if skill already exists
    $check_query = "SELECT * FROM skills WHERE skill_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $skill_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $message = '<div class="error-message">Skill already exists!</div>';
    } else {
        $query = "INSERT INTO skills (skill_name, category, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $skill_name, $skill_category, $description);
        
        if ($stmt->execute()) {
            $message = '<div class="success-message">Skill added successfully!</div>';
        } else {
            $message = '<div class="error-message">Error adding skill: ' . $conn->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Skills - Skill Tracking System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>Manage Skills</h1>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Add New Skill</h2>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label for="skill_name">Skill Name</label>
                            <input type="text" id="skill_name" name="skill_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="skill_category">Category</label>
                            <input type="text" id="skill_category" name="skill_category" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 5px;"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_skill" class="btn btn-primary">Add Skill</button>
                    </div>
                </form>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Skill List</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM skills ORDER BY skill_name";
                        $result = $conn->query($query);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $row['id'] . '</td>';
                                echo '<td>' . htmlspecialchars($row['skill_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                                echo '<td>';
                                echo '<a href="assign_skill.php?id=' . $row['id'] . '" class="btn btn-outline">Assign</a> ';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" style="text-align: center;">No skills found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
