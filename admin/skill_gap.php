<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

// Fetch all employees
$employees_res = $conn->query("SELECT e.id, e.employee_id, e.first_name, e.last_name, e.department, e.position, e.target_role_id, r.role_name as target_role_name 
                               FROM employees e 
                               LEFT JOIN job_roles r ON e.target_role_id = r.id 
                               ORDER BY e.first_name ASC");

// Fetch all roles
$roles_res = $conn->query("SELECT * FROM job_roles ORDER BY role_name ASC");

// Selected Employee and Role
$selected_emp_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : null;
$selected_role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : null;

// Default to first employee if none selected
if (!$selected_emp_id && $employees_res->num_rows > 0) {
    $first_emp = $employees_res->fetch_assoc();
    $selected_emp_id = $first_emp['id'];
    $selected_role_id = $first_emp['target_role_id'] ? $first_emp['target_role_id'] : 1;
    $employees_res->data_seek(0);
}

// If employee selected but no role, use employee's target_role_id or default to role 1
if ($selected_emp_id && !$selected_role_id) {
    $emp_role_check = $conn->prepare("SELECT target_role_id FROM employees WHERE id = ?");
    $emp_role_check->bind_param("i", $selected_emp_id);
    $emp_role_check->execute();
    $target_r = $emp_role_check->get_result()->fetch_assoc();
    $selected_role_id = !empty($target_r['target_role_id']) ? $target_r['target_role_id'] : 1;
}

// Fetch Selected Employee Info
$emp_info = null;
if ($selected_emp_id) {
    $stmt = $conn->prepare("SELECT e.*, r.role_name as target_role_name 
                            FROM employees e 
                            LEFT JOIN job_roles r ON e.target_role_id = r.id 
                            WHERE e.id = ?");
    $stmt->bind_param("i", $selected_emp_id);
    $stmt->execute();
    $emp_info = $stmt->get_result()->fetch_assoc();
}

// Fetch Selected Role Info
$role_info = null;
if ($selected_role_id) {
    $stmt = $conn->prepare("SELECT * FROM job_roles WHERE id = ?");
    $stmt->bind_param("i", $selected_role_id);
    $stmt->execute();
    $role_info = $stmt->get_result()->fetch_assoc();
}

// Compute Gap Analysis Data
$benchmark_data = [];
$labels = [];
$benchmark_scores = [];
$actual_scores = [];

$total_required_points = 0;
$total_earned_points = 0;
$met_count = 0;
$gap_count = 0;

if ($selected_role_id && $selected_emp_id) {
    $gap_query = "
        SELECT 
            b.skill_id,
            s.skill_name,
            s.category,
            b.required_proficiency,
            b.is_mandatory,
            COALESCE(es.proficiency_level, 0) as current_proficiency,
            (COALESCE(es.proficiency_level, 0) - b.required_proficiency) as gap_diff,
            (SELECT training_name FROM training WHERE related_skills LIKE CONCAT('%', s.skill_name, '%') LIMIT 1) as recommended_course
        FROM role_skill_benchmarks b
        JOIN skills s ON b.skill_id = s.id
        LEFT JOIN employee_skills es ON (b.skill_id = es.skill_id AND es.employee_id = ?)
        WHERE b.role_id = ?
        ORDER BY b.is_mandatory DESC, gap_diff ASC
    ";
    
    $stmt = $conn->prepare($gap_query);
    $stmt->bind_param("ii", $selected_emp_id, $selected_role_id);
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

// Match Readiness percentage
$match_percentage = $total_required_points > 0 ? round(($total_earned_points / $total_required_points) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Gap Analysis & Radar - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js for Radar/Spider Visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <div>
                    <h1><i class="fas fa-chart-pie"></i> Skill Gap Analysis</h1>
                    <p style="font-size: 0.85rem; color: #64748b;">Compare employee proficiency against target role benchmarks</p>
                </div>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Filter Selection Bar -->
            <form action="skill_gap.php" method="get" class="filter-bar">
                <div class="filter-group">
                    <label for="employee_id"><i class="fas fa-user"></i> Select Employee:</label>
                    <select name="employee_id" id="employee_id" onchange="this.form.submit()">
                        <?php 
                        $employees_res->data_seek(0);
                        while ($emp = $employees_res->fetch_assoc()): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo $selected_emp_id == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ' - ' . $emp['position'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="role_id"><i class="fas fa-bullseye"></i> Compare Against Role:</label>
                    <select name="role_id" id="role_id" onchange="this.form.submit()">
                        <?php 
                        $roles_res->data_seek(0);
                        while ($r = $roles_res->fetch_assoc()): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $selected_role_id == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['role_name'] . ' [' . $r['department'] . ']'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <a href="roles.php" class="btn btn-secondary"><i class="fas fa-cog"></i> Configure Benchmarks</a>
                </div>
            </form>

            <?php if ($emp_info && $role_info): ?>
                <!-- Hero Readiness Card -->
                <div class="match-score-card">
                    <div>
                        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8;">Competency Evaluation</span>
                        <h2 style="font-size: 1.6rem; font-weight: 700; margin: 0.25rem 0;">
                            <?php echo htmlspecialchars($emp_info['first_name'] . ' ' . $emp_info['last_name']); ?> 
                            <span style="font-size: 1rem; font-weight: 400; color: #cbd5e1;">vs.</span> 
                            <span style="color: #60a5fa;"><?php echo htmlspecialchars($role_info['role_name']); ?></span>
                        </h2>
                        <p style="font-size: 0.85rem; color: #94a3b8; max-width: 600px;">
                            Department: <strong><?php echo htmlspecialchars($emp_info['department']); ?></strong> | Current Position: <strong><?php echo htmlspecialchars($emp_info['position']); ?></strong>
                        </p>
                    </div>

                    <div style="display: flex; gap: 1.5rem; align-items: center;">
                        <div class="match-score-circle" style="border-color: <?php echo $match_percentage >= 80 ? '#10b981' : ($match_percentage >= 60 ? '#f59e0b' : '#ef4444'); ?>;">
                            <span class="match-score-number"><?php echo $match_percentage; ?>%</span>
                            <span class="match-score-label">Fit Score</span>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Target Skills</span>
                            <i class="fas fa-list-check stat-icon"></i>
                        </div>
                        <div class="stat-value"><?php echo count($benchmark_data); ?></div>
                        <div class="stat-description">Evaluated for this role</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Benchmarks Met</span>
                            <i class="fas fa-check-circle stat-icon" style="color: #10b981;"></i>
                        </div>
                        <div class="stat-value" style="color: #10b981;"><?php echo $met_count; ?></div>
                        <div class="stat-description">Meeting or exceeding targets</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Identified Gaps</span>
                            <i class="fas fa-triangle-exclamation stat-icon" style="color: #ef4444;"></i>
                        </div>
                        <div class="stat-value" style="color: #ef4444;"><?php echo $gap_count; ?></div>
                        <div class="stat-description">Upskilling required</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Readiness Status</span>
                            <i class="fas fa-graduation-cap stat-icon" style="color: #2563eb;"></i>
                        </div>
                        <div class="stat-value" style="font-size: 1.25rem;">
                            <?php 
                            if ($match_percentage >= 90) echo '<span style="color: #10b981;">Promotion Ready</span>';
                            elseif ($match_percentage >= 70) echo '<span style="color: #2563eb;">Near Target</span>';
                            else echo '<span style="color: #f59e0b;">Development Needed</span>';
                            ?>
                        </div>
                        <div class="stat-description">Career trajectory standing</div>
                    </div>
                </div>

                <!-- Visual Radar Chart & Detailed Breakdown Grid -->
                <div class="gap-analysis-grid">
                    <!-- Detailed Gap Table -->
                    <div class="content-card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-table-list"></i> Skill-by-Skill Gap Matrix</h2>
                        </div>

                        <?php if (count($benchmark_data) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Skill</th>
                                        <th>Required</th>
                                        <th>Current</th>
                                        <th>Deficit</th>
                                        <th>Status</th>
                                        <th>Action</th>
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
                                            <td>
                                                <span style="font-weight: 700; color: #475569;"><?php echo $req; ?>/10</span>
                                            </td>
                                            <td>
                                                <span style="font-weight: 700; color: <?php echo $curr >= $req ? '#10b981' : '#dc2626'; ?>;">
                                                    <?php echo $curr; ?>/10
                                                </span>
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width: <?php echo ($curr * 10); ?>%; background-color: <?php echo $curr >= $req ? '#10b981' : '#ef4444'; ?>;"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="gap-diff <?php echo $diff < 0 ? 'gap-negative' : ($diff > 0 ? 'gap-positive' : 'gap-zero'); ?>">
                                                    <?php echo $diff > 0 ? '+' . $diff : $diff; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($diff > 0): ?>
                                                    <span class="badge badge-exceeded"><i class="fas fa-arrow-up"></i> Exceeded</span>
                                                <?php elseif ($diff == 0): ?>
                                                    <span class="badge badge-met"><i class="fas fa-check"></i> Benchmark Met</span>
                                                <?php else: ?>
                                                    <span class="badge badge-gap"><i class="fas fa-exclamation-circle"></i> Gap (-<?php echo abs($diff); ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($diff < 0 && !empty($row['recommended_course'])): ?>
                                                    <a href="training.php" class="action-btn view-btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Recommended Training: <?php echo htmlspecialchars($row['recommended_course']); ?>">
                                                        <i class="fas fa-book-open"></i> Course
                                                    </a>
                                                <?php else: ?>
                                                    <span style="font-size: 0.75rem; color: #94a3b8;">--</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="padding: 2rem; text-align: center; color: #94a3b8;">
                                No skill benchmarks configured for role: <strong><?php echo htmlspecialchars($role_info['role_name']); ?></strong>.
                                <br><br>
                                <a href="roles.php" class="btn" style="font-size: 0.85rem;"><i class="fas fa-plus"></i> Add Benchmarks for this Role</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Radar Spider Chart Card -->
                    <div class="content-card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-compass"></i> Competency Radar Chart</h2>
                        </div>
                        <div class="radar-chart-container">
                            <canvas id="skillRadarChart"></canvas>
                        </div>
                        <div style="font-size: 0.75rem; color: #64748b; text-align: center; padding: 0.75rem;">
                            <span style="color: #ef4444; font-weight: 700;">■ Red Line</span> = Required Role Benchmark &nbsp;|&nbsp;
                            <span style="color: #2563eb; font-weight: 700;">■ Blue Polygon</span> = Employee Current Proficiency
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert-banner alert-warning">
                    <i class="fas fa-info-circle"></i> Please select a valid employee and job role to view the gap analysis.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script to render Chart.js Radar Chart -->
    <script>
        const ctx = document.getElementById('skillRadarChart');
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
                            pointBackgroundColor: '#ef4444',
                            pointBorderColor: '#fff',
                            borderWidth: 2,
                            borderDash: [4, 4]
                        },
                        {
                            label: 'Employee Current Proficiency',
                            data: <?php echo json_encode($actual_scores); ?>,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.25)',
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#2563eb',
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
                            ticks: {
                                stepSize: 2,
                                backdropColor: 'transparent'
                            },
                            grid: {
                                color: '#e2e8f0'
                            },
                            angleLines: {
                                color: '#e2e8f0'
                            },
                            pointLabels: {
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                },
                                color: '#1e293b'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
