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
            <a href="my_skills.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_skills.php' ? 'active' : ''; ?>">
                <i class="fas fa-brain"></i> My Skills
            </a>
        </li>
        <li class="nav-item">
            <a href="skill_gap.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'skill_gap.php' ? 'active' : ''; ?>">
                <i class="fas fa-bullseye"></i> Career Gap Analysis
            </a>
        </li>
        <li class="nav-item">
            <a href="my_certifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_certifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-certificate"></i> My Certifications
            </a>
        </li>
        <li class="nav-item">
            <a href="training.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'training.php' ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap"></i> Training & Upskilling
            </a>
        </li>
        <li class="nav-item">
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
