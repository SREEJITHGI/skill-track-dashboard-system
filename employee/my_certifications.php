<?php
include '../includes/employee_auth_check.php';
include '../includes/db_connection.php';

$employee_id = $_SESSION['user_id'];
$message = "";

// Handle Add Certification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_my_cert'])) {
    $certification_id = intval($_POST['certification_id']);
    $date_earned = $_POST['date_earned'];
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;

    $stmt = $conn->prepare("INSERT INTO employee_certifications (employee_id, certification_id, date_earned, expiration_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $employee_id, $certification_id, $date_earned, $expiration_date);
    if ($stmt->execute()) {
        $act = $conn->prepare("INSERT INTO activities (employee_id, activity_description) VALUES (?, 'Registered a new earned certification')");
        $act->bind_param("i", $employee_id);
        $act->execute();

        $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Certification added to your profile!</div>';
    } else {
        $message = '<div class="error-message">Error adding certification: ' . $conn->error . '</div>';
    }
}

// Fetch employee certifications with expiry check
$query = "
    SELECT 
        ec.*,
        c.cert_name,
        c.provider,
        c.description,
        CASE
            WHEN ec.expiration_date IS NULL THEN 'No Expiry'
            WHEN ec.expiration_date < CURRENT_DATE() THEN 'Expired'
            WHEN ec.expiration_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
            ELSE 'Active'
        END as expiry_status,
        DATEDIFF(ec.expiration_date, CURRENT_DATE()) as days_remaining
    FROM employee_certifications ec
    JOIN certifications c ON ec.certification_id = c.id
    WHERE ec.employee_id = ?
    ORDER BY ec.date_earned DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$my_certs = $stmt->get_result();

$expiring_alert = false;
while ($c = $my_certs->fetch_assoc()) {
    if ($c['expiry_status'] == 'Expiring Soon' || $c['expiry_status'] == 'Expired') {
        $expiring_alert = true;
        break;
    }
}
$my_certs->data_seek(0);

// Fetch master certs for dropdown
$available_certs = $conn->query("SELECT * FROM certifications ORDER BY cert_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certifications - Skill Track</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header-bar">
                <div>
                    <h1><i class="fas fa-certificate"></i> My Professional Certifications</h1>
                    <p style="font-size: 0.85rem; color: #64748b;">Credentials, verified licenses, and renewal monitoring</p>
                </div>
                <div class="user-info">
                    <span class="user-name">Employee: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <?php if ($expiring_alert): ?>
                <div class="alert-banner alert-warning">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div>
                        <strong>Action Needed:</strong> One or more of your certifications are expiring soon or have expired. Please prepare renewal documentation.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add Certification Form -->
            <div class="form-container">
                <h2><i class="fas fa-plus-circle"></i> Add Earned Certification</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="certification_id">Certification *</label>
                            <select id="certification_id" name="certification_id" required>
                                <option value="">-- Select Certification --</option>
                                <?php while ($ac = $available_certs->fetch_assoc()): ?>
                                    <option value="<?php echo $ac['id']; ?>">
                                        <?php echo htmlspecialchars($ac['cert_name'] . ' (' . $ac['provider'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_earned">Date Earned *</label>
                            <input type="date" id="date_earned" name="date_earned" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="expiration_date">Expiration Date</label>
                            <input type="date" id="expiration_date" name="expiration_date">
                        </div>
                    </div>
                    <button type="submit" name="add_my_cert" class="btn"><i class="fas fa-save"></i> Add to My Profile</button>
                </form>
            </div>

            <!-- Certifications List -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-award"></i> Active Credential Portfolio</h2>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Certification</th>
                            <th>Issuing Organization</th>
                            <th>Earned Date</th>
                            <th>Expiration Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_certs->num_rows > 0): ?>
                            <?php while ($c = $my_certs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['cert_name']); ?></strong>
                                        <p style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($c['description'] ?: ''); ?></p>
                                    </td>
                                    <td><span class="badge" style="background: #f1f5f9; color: #334155;"><?php echo htmlspecialchars($c['provider']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($c['date_earned'])); ?></td>
                                    <td>
                                        <?php if (!empty($c['expiration_date'])): ?>
                                            <?php echo date('M d, Y', strtotime($c['expiration_date'])); ?>
                                            <?php if ($c['days_remaining'] > 0 && $c['days_remaining'] <= 30): ?>
                                                <span style="font-size: 0.75rem; color: #b45309; font-weight: 700; display: block;">
                                                    (<?php echo $c['days_remaining']; ?> days remaining)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-style: italic;">No Expiry / Lifetime</span>
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
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">You have not registered any certifications yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
