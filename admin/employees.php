
<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

// Handle form submission for adding new employee
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $employee_id = $_POST['employee_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    
    // Default password is employee ID (should be changed on first login in a real application)
    $password = password_hash($employee_id, PASSWORD_DEFAULT);
    
    // Check if employee ID already exists
    $check_query = "SELECT * FROM employees WHERE employee_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $employee_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $message = '<div class="error-message">Employee ID already exists!</div>';
    } else {
        $query = "INSERT INTO employees (employee_id, first_name, last_name, email, department, position, password, join_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $employee_id, $first_name, $last_name, $email, $department, $position, $password);
        
        if ($stmt->execute()) {
            $message = '<div class="success-message">Employee added successfully!</div>';
        } else {
            $message = '<div class="error-message">Error adding employee: ' . $conn->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Skill Tracking System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>Manage Employees</h1>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Add New Employee</h2>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label for="employee_id">Employee ID</label>
                            <input type="text" id="employee_id" name="employee_id" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" id="position" name="position" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">Employee List</h2>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM employees ORDER BY join_date DESC";
                        $result = $conn->query($query);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['employee_id']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                                echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($row['join_date']))) . '</td>';
                                echo '<td>';
                                echo '<a href="view_employee.php?id=' . $row['id'] . '" class="btn btn-outline">View</a> ';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" style="text-align: center;">No employees found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
