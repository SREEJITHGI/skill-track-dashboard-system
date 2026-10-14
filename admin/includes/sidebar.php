<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-chart-line"></i> Skill Track</h2>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="employees.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Employees
            </a>
        </li>
        <li class="nav-item">
            <a href="skills.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'skills.php' ? 'active' : ''; ?>">
                <i class="fas fa-brain"></i> Skills
            </a>
        </li>
        <li class="nav-item">
            <a href="roles.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullseye"></i> Role Benchmarks
            </a>
        </li>
        <li class="nav-item">
            <a href="skill_gap.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'skill_gap.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Skill Gap Analysis
            </a>
        </li>
        <li class="nav-item">
            <a href="certifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'certifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate"></i> Certifications
            </a>
        </li>
        <li class="nav-item">
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
