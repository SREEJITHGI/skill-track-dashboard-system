<?php
include '../includes/admin_auth_check.php';
include '../includes/db_connection.php';

$message = "";

// Handle Add Master Certification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_master_cert'])) {
    $cert_name = trim($_POST['cert_name']);
    $provider = trim($_POST['provider']);
    $valid_period = !empty($_POST['valid_period']) ? intval($_POST['valid_period']) : null;
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO certifications (cert_name, provider, description, valid_period) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $cert_name, $provider, $description, $valid_period);
    if ($stmt->execute()) {
        $message = '<div class="success-message">Certification catalog updated successfully!</div>';
    } else {
        $message = '<div class="error-message">Error adding certification: ' . $conn->error . '</div>';
    }
}

// Handle Assign Certification to Employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_cert'])) {
    $employee_id = intval($_POST['employee_id']);
    $certification_id = intval($_POST['certification_id']);
    $date_earned = $_POST['date_earned'];
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;

    $stmt = $conn->prepare("INSERT INTO employee_certifications (employee_id, certification_id, date_earned, expiration_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $employee_id, $certification_id, $date_earned, $expiration_date);
    if ($stmt->execute()) {
        // Log activity
        $act = $conn->prepare("INSERT INTO activities (employee_id, activity_description) VALUES (?, 'Assigned new professional certification')");
        $act->bind_param("i", $employee_id);
        $act->execute();

        $message = '<div class="success-message">Certification assigned to employee successfully!</div>';
    } else {
        $message = '<div class="error-message">Error assigning certification: ' . $conn->error . '</div>';
    }
}

// Query certifications with expiry statuses
$query = "
    SELECT 
        ec.id as emp_cert_id,
        ec.date_earned,
        ec.expiration_date,
        ec.cert_file_path,
        e.id as employee_id,
        e.employee_id as emp_code,
        e.first_name,
        e.last_name,
        e.department,
        c.cert_name,
        c.provider,
        CASE
            WHEN ec.expiration_date IS NULL THEN 'No Expiry'
            WHEN ec.expiration_date < CURRENT_DATE() THEN 'Expired'
            WHEN ec.expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
            ELSE 'Active'
        END as expiry_status,
        DATEDIFF(ec.expiration_date, CURRENT_DATE()) as days_remaining
    FROM employee_certifications ec
    JOIN employees e ON ec.employee_id = e.id
    JOIN certifications c ON ec.certification_id = c.id
    ORDER BY 
        CASE 
            WHEN ec.expiration_date IS NOT NULL AND ec.expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 1
            ELSE 2
        END,
        ec.expiration_date ASC
";
$certs_result = $conn->query($query);

// Expiry alerts count
$expiring_soon_count = 0;
$expired_count = 0;
$active_count = 0;

if ($certs_result) {
    while ($r = $certs_result->fetch_assoc()) {
        if ($r['expiry_status'] == 'Expiring Soon') $expiring_soon_count++;
        elseif ($r['expiry_status'] == 'Expired') $expired_count++;
        else $active_count++;
    }
    $certs_result->data_seek(0);
}

// Fetch all master certifications & employees for dropdowns
$master_certs = $conn->query("SELECT * FROM certifications ORDER BY cert_name ASC");
$all_employees = $conn->query("SELECT id, employee_id, first_name, last_name, department FROM employees ORDER BY first_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certifications & Expiry Tracker - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <div>
                    <h1><i class="fas fa-certificate"></i> Certifications & Expiry Radar</h1>
                    <p style="font-size: 0.85rem; color: #64748b;">Monitor compliance, professional accreditations, and renewal deadlines</p>
                </div>
                <div class="user-info">
                    <span class="user-name">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <?php if ($expiring_soon_count > 0 || $expired_count > 0): ?>
                <div class="alert-banner alert-warning">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div>
                        <strong>Certification Action Required:</strong>
                        <?php if ($expiring_soon_count > 0): ?>
                            <span><?php echo $expiring_soon_count; ?> certification(s) are expiring within the next 30 days.</span>
                        <?php endif; ?>
                        <?php if ($expired_count > 0): ?>
                            <span> &nbsp;|&nbsp; <?php echo $expired_count; ?> certification(s) have expired.</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="stats-container" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Active Credentials</span>
                        <i class="fas fa-shield-check stat-icon" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-value" style="color: #10b981;"><?php echo $active_count; ?></div>
                    <div class="stat-description">Valid and in good standing</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Expiring Soon</span>
                        <i class="fas fa-clock stat-icon" style="color: #f59e0b;"></i>
                    </div>
                    <div class="stat-value" style="color: #f59e0b;"><?php echo $expiring_soon_count; ?></div>
                    <div class="stat-description">Within next 30 days</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Expired Credentials</span>
                        <i class="fas fa-times-circle stat-icon" style="color: #ef4444;"></i>
                    </div>
                    <div class="stat-value" style="color: #ef4444;"><?php echo $expired_count; ?></div>
                    <div class="stat-description">Requires immediate renewal</div>
                </div>
            </div>

            <!-- Modals / Forms Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="form-container" style="margin-bottom: 0;">
                    <h2><i class="fas fa-plus"></i> Add Master Certification</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="cert_name">Certification Name *</label>
                            <input type="text" id="cert_name" name="cert_name" placeholder="e.g. AWS Solutions Architect Professional" required>
                        </div>
                        <div class="form-group">
                            <label for="provider">Issuing Provider *</label>
                            <input type="text" id="provider" name="provider" placeholder="e.g. Amazon Web Services, Cisco, PMI" required>
                        </div>
                        <div class="form-group">
                            <label for="valid_period">Validity Period (Months)</label>
                            <input type="number" id="valid_period" name="valid_period" placeholder="e.g. 24 or 36 (Leave blank if lifetime)">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="2" placeholder="Accreditation scope..."></textarea>
                        </div>
                        <button type="submit" name="add_master_cert" class="btn"><i class="fas fa-save"></i> Save Certification</button>
                    </form>
                </div>

                <div class="form-container" style="margin-bottom: 0;">
                    <h2><i class="fas fa-user-check"></i> Assign Certification to Employee</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label for="employee_id">Select Employee *</label>
                            <select id="employee_id" name="employee_id" required>
                                <option value="">-- Choose Employee --</option>
                                <?php 
                                $all_employees->data_seek(0);
                                while ($emp = $all_employees->fetch_assoc()): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="certification_id">Select Certification *</label>
                            <select id="certification_id" name="certification_id" required>
                                <option value="">-- Choose Certification --</option>
                                <?php 
                                $master_certs->data_seek(0);
                                while ($c = $master_certs->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['cert_name'] . ' (' . $c['provider'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_earned">Date Earned *</label>
                            <input type="date" id="date_earned" name="date_earned" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="expiration_date">Expiration Date (Optional)</label>
                            <input type="date" id="expiration_date" name="expiration_date">
                        </div>
                        <button type="submit" name="assign_cert" class="btn btn-secondary" style="background-color: var(--primary-color);">
                            <i class="fas fa-award"></i> Record Credential
                        </button>
                    </form>
                </div>
            </div>

            <!-- Certifications Inventory Table -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Employee Certification Registry & Status</h2>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Certification</th>
                            <th>Provider</th>
                            <th>Earned</th>
                            <th>Expiration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($certs_result->num_rows > 0): ?>
                            <?php while ($c = $certs_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></strong>
                                        <span style="font-size: 0.75rem; color: #64748b; display: block;"><?php echo htmlspecialchars($c['department']); ?> (<?php echo htmlspecialchars($c['emp_code']); ?>)</span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($c['cert_name']); ?></strong></td>
                                    <td><span class="badge" style="background: #f1f5f9; color: #334155;"><?php echo htmlspecialchars($c['provider']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($c['date_earned'])); ?></td>
                                    <td>
                                        <?php if (!empty($c['expiration_date'])): ?>
                                            <span><?php echo date('M d, Y', strtotime($c['expiration_date'])); ?></span>
                                            <?php if ($c['days_remaining'] > 0 && $c['days_remaining'] <= 30): ?>
                                                <span style="font-size: 0.75rem; color: #b45309; font-weight: 700; display: block;">
                                                    (<?php echo $c['days_remaining']; ?> days left)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-style: italic;">Lifetime / No Expiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['expiry_status'] == 'Expiring Soon'): ?>
                                            <span class="badge badge-expiring"><i class="fas fa-clock"></i> Expiring Soon</span>
                                        <?php elseif ($c['expiry_status'] == 'Expired'): ?>
                                            <span class="badge badge-expired"><i class="fas fa-exclamation-circle"></i> Expired</span>
                                        <?php else: ?>
                                            <span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">No employee certifications recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
