<?php
include '../includes/employee_auth_check.php';
include '../includes/db_connection.php';

$employee_id = $_SESSION['user_id'];

// Handle 1-click Training Enrollment
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_training'])) {
    $training_id = intval($_POST['training_id']);
    
    // Check if already enrolled
    $check = $conn->prepare("SELECT id FROM employee_training WHERE employee_id = ? AND training_id = ?");
    $check->bind_param("ii", $employee_id, $training_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = '<div class="alert-banner alert-warning"><i class="fas fa-info-circle"></i> You are already enrolled in this training program.</div>';
    } else {
        $enroll_stmt = $conn->prepare("INSERT INTO employee_training (employee_id, training_id, start_date, status) VALUES (?, ?, CURRENT_DATE(), 'In Progress')");
        $enroll_stmt->bind_param("ii", $employee_id, $training_id);
        if ($enroll_stmt->execute()) {
            // Log activity
            $act = $conn->prepare("INSERT INTO activities (employee_id, activity_description) VALUES (?, 'Enrolled in training program to close skill gap')");
            $act->bind_param("i", $employee_id);
            $act->execute();
            
            $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Successfully enrolled in training course! Track it under "My Training".</div>';
        }
    }
}

// Fetch employee info
$emp_stmt = $conn->prepare("SELECT e.*, r.role_name as target_role_name 
                           FROM employees e 
                           LEFT JOIN job_roles r ON e.target_role_id = r.id 
                           WHERE e.id = ?");
$emp_stmt->bind_param("i", $employee_id);
$emp_stmt->execute();
$employee = $emp_stmt->get_result()->fetch_assoc();

// Fetch all available job roles
$roles_res = $conn->query("SELECT * FROM job_roles ORDER BY role_name ASC");

// Selected role for comparison (defaults to target_role_id or role 1)
$selected_role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : (!empty($employee['target_role_id']) ? $employee['target_role_id'] : 1);

// Fetch selected role info
$role_stmt = $conn->prepare("SELECT * FROM job_roles WHERE id = ?");
$role_stmt->bind_param("i", $selected_role_id);
$role_stmt->execute();
$role_info = $role_stmt->get_result()->fetch_assoc();

// Gap Analysis data
$benchmark_data = [];
$labels = [];
$benchmark_scores = [];
$actual_scores = [];

$total_required_points = 0;
$total_earned_points = 0;
$met_count = 0;
$gap_count = 0;

if ($selected_role_id) {
    $gap_query = "
        SELECT 
            b.skill_id,
            s.skill_name,
            s.category,
            b.required_proficiency,
            b.is_mandatory,
            COALESCE(es.proficiency_level, 0) as current_proficiency,
            (COALESCE(es.proficiency_level, 0) - b.required_proficiency) as gap_diff,
            t.id as training_id,
            t.training_name,
            t.provider as training_provider,
            t.duration as training_duration
        FROM role_skill_benchmarks b
        JOIN skills s ON b.skill_id = s.id
        LEFT JOIN employee_skills es ON (b.skill_id = es.skill_id AND es.employee_id = ?)
        LEFT JOIN training t ON t.related_skills LIKE CONCAT('%', s.skill_name, '%')
        WHERE b.role_id = ?
        ORDER BY b.is_mandatory DESC, gap_diff ASC
    ";
    
    $stmt = $conn->prepare($gap_query);
    $stmt->bind_param("ii", $employee_id, $selected_role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $benchmark_data[] = $row;
        $labels[] = $row['skill_name'];
        $benchmark_scores[] = intval($row['required_proficiency']);
        $actual_scores[] = intval($row['current_proficiency']);

        $total_required_points += intval($row['required_proficiency']);
        $total_earned_points += min(intval($row['current_proficiency']), intval($row['required_proficiency']));

        if ($row['gap_diff'] >= 0) {
            $met_count++;
        } else {
            $gap_count++;
        }
    }
}

$match_percentage = $total_required_points > 0 ? round(($total_earned_points / $total_required_points) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Career & Skill Gap Analysis - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <div>
                    <h1><i class="fas fa-bullseye"></i> Career Pathway & Skill Gap Analysis</h1>
                    <p style="font-size: 0.85rem; color: #64748b;">Evaluate your skills against promotion and target position benchmarks</p>
                </div>
                <div class="user-info">
                    <span class="user-name">Employee: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <!-- Role Selector -->
            <form action="skill_gap.php" method="get" class="filter-bar">
                <div class="filter-group" style="flex: 1; max-width: 400px;">
                    <label for="role_id"><i class="fas fa-briefcase"></i> Explore Career Benchmarks for Role:</label>
                    <select name="role_id" id="role_id" onchange="this.form.submit()">
                        <?php 
                        $roles_res->data_seek(0);
                        while ($r = $roles_res->fetch_assoc()): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $selected_role_id == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['role_name'] . ' (' . $r['department'] . ')'); ?>
                                <?php if ($r['id'] == $employee['target_role_id']) echo ' ⭐ [My Target Role]'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <span style="font-size: 0.85rem; color: #64748b;">
                        Current Position: <strong><?php echo htmlspecialchars($employee['position']); ?></strong>
                    </span>
                </div>
            </form>

            <?php if ($role_info): ?>
                <!-- Hero Score Card -->
                <div class="match-score-card">
                    <div>
                        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8;">Career Readiness Status</span>
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0.25rem 0;">
                            Target Position: <span style="color: #60a5fa;"><?php echo htmlspecialchars($role_info['role_name']); ?></span>
                        </h2>
                        <p style="font-size: 0.85rem; color: #cbd5e1; max-width: 600px;">
                            <?php echo htmlspecialchars($role_info['description'] ?: 'Benchmark requirements set by your department management.'); ?>
                        </p>
                    </div>

                    <div class="match-score-circle" style="border-color: <?php echo $match_percentage >= 80 ? '#10b981' : ($match_percentage >= 60 ? '#f59e0b' : '#ef4444'); ?>;">
                        <span class="match-score-number"><?php echo $match_percentage; ?>%</span>
                        <span class="match-score-label">Role Fit</span>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Skills Evaluated</span>
                            <i class="fas fa-list-check stat-icon"></i>
                        </div>
                        <div class="stat-value"><?php echo count($benchmark_data); ?></div>
                        <div class="stat-description">Required for this role</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Benchmarks Met</span>
                            <i class="fas fa-check-circle stat-icon" style="color: #10b981;"></i>
                        </div>
                        <div class="stat-value" style="color: #10b981;"><?php echo $met_count; ?></div>
                        <div class="stat-description">You satisfy these requirements</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Identified Gaps</span>
                            <i class="fas fa-arrow-trend-up stat-icon" style="color: #f59e0b;"></i>
                        </div>
                        <div class="stat-value" style="color: #f59e0b;"><?php echo $gap_count; ?></div>
                        <div class="stat-description">Skills to develop</div>
                    </div>
                </div>

                <!-- Analysis Grid -->
                <div class="gap-analysis-grid">
                    <!-- Table -->
                    <div class="content-card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-tasks"></i> My Competencies vs. Benchmark</h2>
                        </div>

                        <?php if (count($benchmark_data) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Skill</th>
                                        <th>Required</th>
                                        <th>My Score</th>
                                        <th>Status</th>
                                        <th>Upskill Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($benchmark_data as $row): 
                                        $diff = $row['gap_diff'];
                                        $curr = $row['current_proficiency'];
                                        $req = $row['required_proficiency'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['skill_name']); ?></strong>
                                                <span style="font-size: 0.75rem; color: #64748b; display: block;"><?php echo htmlspecialchars($row['category']); ?></span>
                                            </td>
                                            <td><strong><?php echo $req; ?>/10</strong></td>
                                            <td>
                                                <span style="font-weight: 700; color: <?php echo $curr >= $req ? '#10b981' : '#dc2626'; ?>;">
                                                    <?php echo $curr; ?>/10
                                                </span>
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width: <?php echo ($curr * 10); ?>%; background-color: <?php echo $curr >= $req ? '#10b981' : '#ef4444'; ?>;"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($diff > 0): ?>
                                                    <span class="badge badge-exceeded"><i class="fas fa-check-double"></i> +<?php echo $diff; ?> Exceeded</span>
                                                <?php elseif ($diff == 0): ?>
                                                    <span class="badge badge-met"><i class="fas fa-check"></i> Benchmark Met</span>
                                                <?php else: ?>
                                                    <span class="badge badge-gap"><i class="fas fa-exclamation-triangle"></i> -<?php echo abs($diff); ?> Gap</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($diff < 0 && !empty($row['training_id'])): ?>
                                                    <form action="skill_gap.php?role_id=<?php echo $selected_role_id; ?>" method="post">
                                                        <input type="hidden" name="training_id" value="<?php echo $row['training_id']; ?>">
                                                        <button type="submit" name="enroll_training" class="btn" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; background-color: var(--success-color);">
                                                            <i class="fas fa-graduation-cap"></i> Enroll Course
                                                        </button>
                                                    </form>
                                                <?php elseif ($diff < 0): ?>
                                                    <span style="font-size: 0.75rem; color: #94a3b8;">Self-study</span>
                                                <?php else: ?>
                                                    <span class="badge badge-active" style="font-size: 0.75rem;"><i class="fas fa-check"></i> Qualified</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="padding: 2rem; text-align: center; color: #94a3b8;">
                                No benchmarks set for this role yet.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Radar Chart -->
                    <div class="content-card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-compass"></i> My Competency Polygon</h2>
                        </div>
                        <div class="radar-chart-container">
                            <canvas id="myRadarChart"></canvas>
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; text-align: center; padding: 0.75rem;">
                            <span style="color: #ef4444; font-weight: 700;">■ Red Line</span> = Benchmark Goal &nbsp;|&nbsp;
                            <span style="color: #2563eb; font-weight: 700;">■ Blue Polygon</span> = Your Current Score
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('myRadarChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [
                        {
                            label: 'Role Benchmark Target',
                            data: <?php echo json_encode($benchmark_scores); ?>,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            borderDash: [4, 4]
                        },
                        {
                            label: 'My Current Score',
                            data: <?php echo json_encode($actual_scores); ?>,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.25)',
                            pointBackgroundColor: '#2563eb',
                            borderWidth: 2.5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        r: {
                            min: 0,
                            max: 10,
                            ticks: { stepSize: 2, backdropColor: 'transparent' }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
