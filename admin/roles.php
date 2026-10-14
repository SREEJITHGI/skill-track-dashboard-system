<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

$message = "";

// Handle Add Role
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_role'])) {
    $role_name = trim($_POST['role_name']);
    $department = trim($_POST['department']);
    $description = trim($_POST['description']);

    if (!empty($role_name) && !empty($department)) {
        $check = $conn->prepare("SELECT id FROM job_roles WHERE role_name = ?");
        $check->bind_param("s", $role_name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = '<div class="error-message">Role name already exists!</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO job_roles (role_name, department, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $role_name, $department, $description);
            if ($stmt->execute()) {
                $message = '<div class="success-message">Job role added successfully!</div>';
            } else {
                $message = '<div class="error-message">Error adding role: ' . $conn->error . '</div>';
            }
        }
    }
}

// Handle Add Skill Benchmark to Role
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_benchmark'])) {
    $role_id = intval($_POST['role_id']);
    $skill_id = intval($_POST['skill_id']);
    $required_proficiency = intval($_POST['required_proficiency']);
    $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO role_skill_benchmarks (role_id, skill_id, required_proficiency, is_mandatory) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE required_proficiency = VALUES(required_proficiency), is_mandatory = VALUES(is_mandatory)");
    $stmt->bind_param("iiii", $role_id, $skill_id, $required_proficiency, $is_mandatory);
    if ($stmt->execute()) {
        $message = '<div class="success-message">Skill benchmark updated for this role!</div>';
    } else {
        $message = '<div class="error-message">Error saving benchmark: ' . $conn->error . '</div>';
    }
}

// Handle Assign Target Role to Employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_target_role'])) {
    $employee_id = intval($_POST['employee_id']);
    $target_role_id = intval($_POST['target_role_id']);

    $stmt = $conn->prepare("UPDATE employees SET target_role_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $target_role_id, $employee_id);
    if ($stmt->execute()) {
        $message = '<div class="success-message">Employee target role updated successfully!</div>';
    } else {
        $message = '<div class="error-message">Error assigning target role: ' . $conn->error . '</div>';
    }
}

// Fetch all roles with skill count
$roles_query = "SELECT r.*, COUNT(b.id) as benchmark_count 
                FROM job_roles r
                LEFT JOIN role_skill_benchmarks b ON r.id = b.role_id
                GROUP BY r.id
                ORDER BY r.role_name ASC";
$roles_result = $conn->query($roles_query);

// Fetch all skills for dropdown
$skills_result = $conn->query("SELECT id, skill_name, category FROM skills ORDER BY skill_name ASC");
$all_skills = [];
while ($row = $skills_result->fetch_assoc()) {
    $all_skills[] = $row;
}

// Fetch employees for target role assignment
$emp_result = $conn->query("SELECT e.id, e.employee_id, e.first_name, e.last_name, e.department, e.position, r.role_name as target_role
                            FROM employees e
                            LEFT JOIN job_roles r ON e.target_role_id = r.id
                            ORDER BY e.first_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Roles & Skill Benchmarks - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <h1>Role Benchmarks & Competency Targets</h1>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Configured Roles</span>
                        <i class="fas fa-id-badge stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $roles_result->num_rows; ?></div>
                    <div class="stat-description">Defined job positions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Gap Analysis</span>
                        <i class="fas fa-chart-pie stat-icon"></i>
                    </div>
                    <div class="stat-value"><a href="skill_gap.php" style="color: inherit; text-decoration: underline;">Launch</a></div>
                    <div class="stat-description">Compare staff vs targets</div>
                </div>
            </div>

            <!-- Add Role Form & Assign Form -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="form-container" style="margin-bottom: 0;">
                    <h2><i class="fas fa-plus-circle"></i> Create New Job Role</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="role_name">Role Title *</label>
                            <input type="text" id="role_name" name="role_name" placeholder="e.g. Senior DevOps Specialist" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department *</label>
                            <input type="text" id="department" name="department" placeholder="e.g. IT, Engineering, Product" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Role Responsibilities / Competencies</label>
                            <textarea id="description" name="description" rows="3" placeholder="Core functions and deliverables expected for this tier..."></textarea>
                        </div>
                        <button type="submit" name="add_role" class="btn"><i class="fas fa-save"></i> Save Job Role</button>
                    </form>
                </div>

                <div class="form-container" style="margin-bottom: 0;">
                    <h2><i class="fas fa-user-tag"></i> Assign Target Role to Staff</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="employee_id">Select Employee *</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">-- Choose Staff Member --</option>
                                <?php 
                                $emp_result->data_seek(0);
                                while ($emp = $emp_result->fetch_assoc()): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                        <?php if (!empty($emp['target_role'])) echo ' [Current Target: ' . htmlspecialchars($emp['target_role']) . ']'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="target_role_id">Target Benchmark Role *</label>
                            <select id="target_role_id" name="target_role_id" required>
                                <option value="">-- Choose Target Role --</option>
                                <?php 
                                $roles_result->data_seek(0);
                                while ($r = $roles_result->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>">
                                        <?php echo htmlspecialchars($r['role_name'] . ' (' . $r['department'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="assign_target_role" class="btn btn-secondary" style="background-color: var(--primary-color);">
                            <i class="fas fa-check"></i> Assign Career Target
                        </button>
                    </form>
                </div>
            </div>

            <!-- Role Benchmarks Table -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-layer-group"></i> Active Roles & Benchmark Criteria</h2>
                </div>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Role Title</th>
                                <th>Department</th>
                                <th>Benchmark Skills</th>
                                <th>Add Skill Requirement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $roles_result->data_seek(0);
                            if ($roles_result->num_rows > 0):
                                while ($role = $roles_result->fetch_assoc()): 
                                    // Fetch current benchmarks for this role
                                    $b_stmt = $conn->prepare("SELECT b.*, s.skill_name, s.category 
                                                             FROM role_skill_benchmarks b
                                                             JOIN skills s ON b.skill_id = s.id
                                                             WHERE b.role_id = ?
                                                             ORDER BY b.required_proficiency DESC");
                                    $b_stmt->bind_param("i", $role['id']);
                                    $b_stmt->execute();
                                    $benchmarks = $b_stmt->get_result();
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($role['role_name']); ?></strong>
                                        <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">
                                            <?php echo htmlspecialchars($role['description'] ?: 'No description provided'); ?>
                                        </p>
                                    </td>
                                    <td><span class="badge" style="background: #e0f2fe; color: #0369a1;"><?php echo htmlspecialchars($role['department']); ?></span></td>
                                    <td>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; max-width: 320px;">
                                            <?php if ($benchmarks->num_rows > 0): ?>
                                                <?php while ($bm = $benchmarks->fetch_assoc()): ?>
                                                    <span class="badge <?php echo $bm['is_mandatory'] ? 'badge-met' : 'badge-exceeded'; ?>" title="<?php echo $bm['is_mandatory'] ? 'Mandatory Skill' : 'Optional/Bonus Skill'; ?>">
                                                        <?php echo htmlspecialchars($bm['skill_name']); ?>: <strong><?php echo $bm['required_proficiency']; ?>/10</strong>
                                                    </span>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <span style="font-size: 0.8rem; color: #94a3b8; font-style: italic;">No benchmarks added yet</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- Inline Quick Add Benchmark -->
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                            <select name="skill_id" required style="padding: 0.35rem; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                                                <option value="">Skill...</option>
                                                <?php foreach ($all_skills as $sk): ?>
                                                    <option value="<?php echo $sk['id']; ?>"><?php echo htmlspecialchars($sk['skill_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="number" name="required_proficiency" min="1" max="10" value="8" placeholder="1-10" required style="width: 55px; padding: 0.35rem; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                                            <button type="submit" name="add_benchmark" class="btn" style="padding: 0.35rem 0.6rem; font-size: 0.75rem;" title="Set Required Benchmark">
                                                <i class="fas fa-plus"></i> Set
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="skill_gap.php?role_id=<?php echo $role['id']; ?>" class="action-btn view-btn" title="View Gap Analysis for this Role">
                                            <i class="fas fa-chart-line"></i> Gap Analysis
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">No job roles configured yet. Create one using the form above.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
