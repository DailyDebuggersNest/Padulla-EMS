<?php
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isDashboard = str_ends_with($currentPath, '/dashboard.php');
$isStudents = strpos($currentPath, '/modules/students/') !== false;
$isRecords = str_ends_with($currentPath, '/modules/enrollment/records.php');
$isEnrollment = strpos($currentPath, '/modules/enrollment/') !== false && !$isRecords;
$isPrograms = strpos($currentPath, '/modules/programs/') !== false;
$isPayments = strpos($currentPath, '/modules/payments/') !== false;
$isReports = strpos($currentPath, '/modules/reports/') !== false;
?>

<aside id="sidebar-wrapper" class="ua-sidebar">
    <div class="sidebar-heading">
        <div class="sidebar-brand-wrap">
            <img src="<?= BASE_PATH ?>assets/img/logo.png" alt="UA Academy Logo" class="sidebar-logo">
            <div>
                <div class="sidebar-brand">UA <span>ACADEMY</span></div>
                <div class="sidebar-subtitle">Enrollment Management System</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-nav-label">Main</div>
        <a href="<?= BASE_PATH ?>dashboard.php" class="ua-nav-link <?= $isDashboard ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span class="ua-nav-text">Dashboard</span>
        </a>
        <a href="<?= BASE_PATH ?>modules/students/index.php" class="ua-nav-link <?= $isStudents ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span class="ua-nav-text">Students</span>
        </a>
        <a href="<?= BASE_PATH ?>modules/enrollment/index.php" class="ua-nav-link <?= $isEnrollment ? 'active' : '' ?>">
            <i class="fas fa-user-plus"></i>
            <span class="ua-nav-text">Enrollment</span>
        </a>

        <div class="sidebar-nav-label mt-2">Academic</div>
        <a href="<?= BASE_PATH ?>modules/programs/index.php" class="ua-nav-link <?= $isPrograms ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span class="ua-nav-text">Program</span>
        </a>
        <a href="<?= BASE_PATH ?>modules/enrollment/records.php" class="ua-nav-link <?= $isRecords ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i>
            <span class="ua-nav-text">Records</span>
        </a>

        <div class="sidebar-nav-label mt-2">Finance</div>
        <a href="<?= BASE_PATH ?>modules/payments/index.php" class="ua-nav-link <?= $isPayments ? 'active' : '' ?>">
            <i class="fas fa-money-bill-alt"></i>
            <span class="ua-nav-text">Payments</span>
        </a>
        <a href="<?= BASE_PATH ?>modules/reports/index.php" class="ua-nav-link <?= $isReports ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span class="ua-nav-text">Reports</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_PATH ?>logout.php" class="ua-nav-link ua-nav-link-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="ua-nav-text">Logout</span>
        </a>
    </div>
</aside>