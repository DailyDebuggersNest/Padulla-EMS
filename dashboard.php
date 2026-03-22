<?php
// Dashboard index
require_once __DIR__ . '/config/db.php';
include_once __DIR__ . '/includes/header.php';

// Fetch quick stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Find the active/latest term for accurate enrolled count
$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();
$active_academic_year = $latestTerm ? $latestTerm['academic_year'] : '2025-2026';
$active_semester = $latestTerm ? $latestTerm['semester_id'] : 1;

$totalEnrolledStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE academic_year = ? AND semester_id = ? AND status != 'Cancelled'");
$totalEnrolledStmt->execute([$active_academic_year, $active_semester]);
$totalEnrolled = $totalEnrolledStmt->fetchColumn();

$totalPayments = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn();
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3 shadow-sm rounded">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users"></i> Total Students</h5>
                <p class="card-text display-4"><?= $totalStudents ?: 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3 shadow-sm rounded">
            <div class="card-body">
                <h5 class="card-title" title="Term: <?= htmlspecialchars($active_academic_year . ' - ' . $active_semester) ?>"><i class="fas fa-user-check"></i> Enrolled this Term</h5>
                <p class="card-text display-4"><?= $totalEnrolled ?: 0 ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3 shadow-sm rounded">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Total Payments</h5>
                <p class="card-text display-4">₱<?= number_format($totalPayments ?: 0, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm rounded">
            <div class="card-header bg-light">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?= BASE_PATH ?>modules/enrollment/index.php" class="btn btn-primary me-2"><i class="fas fa-user-plus"></i> New Enrollment</a>
                <a href="<?= BASE_PATH ?>modules/students/index.php" class="btn btn-secondary me-2"><i class="fas fa-users"></i> Student Masterlist</a>
                <a href="<?= BASE_PATH ?>modules/enrollment/records.php" class="btn btn-info me-2 text-white"><i class="fas fa-file-alt"></i> View Records</a>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>