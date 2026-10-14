<?php
include '../includes/employee_auth_check.php';
include '../includes/db_connection.php';

$employee_id = $_SESSION['user_id'];
$message = "";

// Handle Enroll
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll'])) {
    $training_id = intval($_POST['training_id']);

    $check = $conn->prepare("SELECT id FROM employee_training WHERE employee_id = ? AND training_id = ?");
    $check->bind_param("ii", $employee_id, $training_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = '<div class="alert-banner alert-warning">You are already enrolled in this course.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO employee_training (employee_id, training_id, start_date, status) VALUES (?, ?, CURRENT_DATE(), 'In Progress')");
        $stmt->bind_param("ii", $employee_id, $training_id);
        if ($stmt->execute()) {
            $act = $conn->prepare("INSERT INTO activities (employee_id, activity_description) VALUES (?, 'Enrolled in a new training program')");
            $act->bind_param("i", $employee_id);
            $act->execute();

            $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Successfully enrolled in training!</div>';
        }
    }
}

// Handle Mark Completed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_course'])) {
    $emp_training_id = intval($_POST['emp_training_id']);

    $stmt = $conn->prepare("UPDATE employee_training SET status = 'Completed', completion_date = CURRENT_DATE() WHERE id = ? AND employee_id = ?");
    $stmt->bind_param("ii", $emp_training_id, $employee_id);
    if ($stmt->execute()) {
        $act = $conn->prepare("INSERT INTO activities (employee_id, activity_description) VALUES (?, 'Successfully completed training program')");
        $act->bind_param("i", $employee_id);
        $act->execute();

        $message = '<div class="success-message"><i class="fas fa-graduation-cap"></i> Congratulations! Course marked as completed.</div>';
    }
}

// Fetch user's enrolled training
$enrolled_query = "
    SELECT et.*, t.training_name, t.description, t.provider, t.duration, t.related_skills
    FROM employee_training et
    JOIN training t ON et.training_id = t.id
    WHERE et.employee_id = ?
    ORDER BY FIELD(et.status, 'In Progress', 'Not Started', 'Completed', 'Failed'), et.start_date DESC
";
$stmt = $conn->prepare($enrolled_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$enrolled_res = $stmt->get_result();

// Smart Recommendation Engine: Find skills where user has a gap against their target role
$rec_query = "
    SELECT DISTINCT t.*, s.skill_name, b.required_proficiency, COALESCE(es.proficiency_level, 0) as current_score
    FROM employees e
    JOIN role_skill_benchmarks b ON e.target_role_id = b.role_id
    JOIN skills s ON b.skill_id = s.id
    LEFT JOIN employee_skills es ON (s.id = es.skill_id AND es.employee_id = e.id)
    JOIN training t ON t.related_skills LIKE CONCAT('%', s.skill_name, '%')
    LEFT JOIN employee_training et ON (t.id = et.training_id AND et.employee_id = e.id)
    WHERE e.id = ? 
      AND (es.proficiency_level IS NULL OR es.proficiency_level < b.required_proficiency)
      AND (et.id IS NULL)
";
$rec_stmt = $conn->prepare($rec_query);
$rec_stmt->bind_param("i", $employee_id);
$rec_stmt->execute();
$recommendations = $rec_stmt->get_result();

// Fetch all training catalog for browsing
$catalog = $conn->query("SELECT * FROM training ORDER BY training_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training & Upskilling - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <div>
                    <h1><i class="fas fa-graduation-cap"></i> Training & Learning Pathways</h1>
                    <p style="font-size: 0.85rem; color: #64748b;">Upskilling courses tailored to close your identified competency gaps</p>
                </div>
                <div class="user-info">
                    <span class="user-name">Employee: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <!-- Smart Recommendations Banner based on Gaps -->
            <?php if ($recommendations->num_rows > 0): ?>
                <div class="content-card" style="border-left: 4px solid var(--primary-color);">
                    <div class="card-header">
                        <h2 class="card-title" style="color: var(--primary-color);">
                            <i class="fas fa-sparkles"></i> AI / Smart Recommended Courses (Based on Your Skill Gaps)
                        </h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; padding: 1rem 0;">
                        <?php while ($rec = $recommendations->fetch_assoc()): ?>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                        <span class="badge badge-gap">
                                            Gap in <?php echo htmlspecialchars($rec['skill_name']); ?> (<?php echo $rec['current_score']; ?>/<?php echo $rec['required_proficiency']; ?>)
                                        </span>
                                        <span style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($rec['duration']); ?></span>
                                    </div>
                                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: 0.35rem;">
                                        <?php echo htmlspecialchars($rec['training_name']); ?>
                                    </h3>
                                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.75rem;">
                                        <?php echo htmlspecialchars($rec['description']); ?>
                                    </p>
                                    <span style="font-size: 0.75rem; color: #475569;">Provider: <strong><?php echo htmlspecialchars($rec['provider']); ?></strong></span>
                                </div>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin-top: 1rem;">
                                    <input type="hidden" name="training_id" value="<?php echo $rec['id']; ?>">
                                    <button type="submit" name="enroll" class="btn" style="width: 100%; font-size: 0.8rem; padding: 0.5rem;">
                                        <i class="fas fa-plus-circle"></i> Enroll to Close Gap
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- My Enrolled Courses -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-spinner"></i> My Enrolled Programs</h2>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Training Course</th>
                            <th>Provider</th>
                            <th>Duration</th>
                            <th>Enrolled Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($enrolled_res->num_rows > 0): ?>
                            <?php while ($course = $enrolled_res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['training_name']); ?></strong>
                                        <span style="font-size: 0.75rem; color: #64748b; display: block;"><?php echo htmlspecialchars($course['related_skills'] ?: ''); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['provider']); ?></td>
                                    <td><?php echo htmlspecialchars($course['duration']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($course['start_date'])); ?></td>
                                    <td>
                                        <?php if ($course['status'] == 'Completed'): ?>
                                            <span class="badge badge-active"><i class="fas fa-check-circle"></i> Completed</span>
                                        <?php elseif ($course['status'] == 'In Progress'): ?>
                                            <span class="badge badge-exceeded"><i class="fas fa-spinner fa-spin"></i> In Progress</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo htmlspecialchars($course['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($course['status'] == 'In Progress'): ?>
                                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                                <input type="hidden" name="emp_training_id" value="<?php echo $course['id']; ?>">
                                                <button type="submit" name="complete_course" class="action-btn edit-btn" style="font-size: 0.75rem;" title="Mark Course as Completed">
                                                    <i class="fas fa-check"></i> Mark Done
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.75rem;">--</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">You are not currently enrolled in any training programs.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Full Catalog -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-book"></i> Browse Entire Training Catalog</h2>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                    <?php 
                    $catalog->data_seek(0);
                    while ($cat = $catalog->fetch_assoc()): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($cat['training_name']); ?>
                                </h3>
                                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($cat['description']); ?>
                                </p>
                                <span style="font-size: 0.75rem; color: #475569;">Duration: <?php echo htmlspecialchars($cat['duration']); ?> | Provider: <?php echo htmlspecialchars($cat['provider']); ?></span>
                            </div>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin-top: 1rem;">
                                <input type="hidden" name="training_id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" name="enroll" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem; padding: 0.45rem;">
                                    <i class="fas fa-plus"></i> Enroll
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
