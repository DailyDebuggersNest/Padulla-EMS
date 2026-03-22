<?php
// modules/enrollment/step3_save.php
// Saves the enrollment into the database and updates schedule slots
require_once '../../config/db.php';
include_once '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<div class='alert alert-danger'>Invalid Request!</div>";
    include_once '../../includes/footer.php';
    exit;
}

$student_id = $_POST['student_id'];
$acad_year = $_POST['acad_year'];
$semester_id = $_POST['semester_id'];
$total_units = (float)$_POST['total_units'];
$assessed_amount = (float)$_POST['assessed_amount'];
$program_id = !empty($_POST['program_id']) ? $_POST['program_id'] : null;
$year_level_id = !empty($_POST['year_level_id']) ? $_POST['year_level_id'] : null;
$section = !empty($_POST['section']) ? $_POST['section'] : null;
$schedule_ids = $_POST['schedule_ids'] ?? [];

if (empty($schedule_ids)) {
    echo "<div class='alert alert-danger'>No schedules provided. Ensure you selected subjects.</div>";
    include_once '../../includes/footer.php';
    exit;
}

try {
    // Begin Transaction
    $pdo->beginTransaction();
    
    $updStu = $pdo->prepare("UPDATE students SET program_id = ?, year_level_id = ? WHERE student_id = ?");
    $updStu->execute([$program_id ?: null, $year_level_id, $student_id]);
    $stmt = $pdo->prepare("
        INSERT INTO enrollments (student_id, academic_year, semester_id, status, total_units, assessed_amount, program_id, section) 
        VALUES (?, ?, ?, 'Enrolled', ?, ?, ?, ?)
    ");
    $stmt->execute([$student_id, $acad_year, $semester_id, $total_units, $assessed_amount, $program_id, $section]);
    $enrollment_id = $pdo->lastInsertId();

    // 2. Link subjects (schedules) to enrollment and increment enrolled_count
    $insertLinkStmt = $pdo->prepare("INSERT INTO enrollment_schedules (enrollment_id, schedule_id) VALUES (?, ?)");
    $updateSchedStmt = $pdo->prepare("UPDATE schedules SET enrolled_count = enrolled_count + 1 WHERE schedule_id = ?");

    foreach ($schedule_ids as $sch_id) {
        $insertLinkStmt->execute([$enrollment_id, $sch_id]);
        $updateSchedStmt->execute([$sch_id]);
    }

    // Commit Transaction
    $pdo->commit();

    $success = true;

} catch (Exception $e) {
    // Rollback if something failed
    $pdo->rollBack();
    $success = false;
    $error_message = $e->getMessage();
}

?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center bg-light p-3 border rounded">
            <span class="badge bg-secondary fs-6"><i class="fas fa-check"></i> 1. Term & Subjects</span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span class="badge bg-secondary fs-6"><i class="fas fa-check"></i> 2. Assessment Check</span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span class="badge bg-success fs-6"><i class="fas fa-flag-checkered"></i> 3. Confirmed</span>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 text-center mt-5">
        <?php if(isset($success) && $success): ?>
            <div class="card shadow-sm border-success">
                <div class="card-body py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h2 class="text-success">Enrollment Successful!</h2>
                    <p class="lead">Student <strong><?= htmlspecialchars($student_id) ?></strong> has been officially enrolled for <strong><?= htmlspecialchars("$acad_year - $semester_id") ?></strong>.</p>
                    
                    <div class="mt-5">
                        <a href="print_cor.php?id=<?= $enrollment_id ?>" class="btn btn-warning btn-lg me-2" target="_blank">
                            <i class="fas fa-print"></i> Print COR
                        </a>
                        <a href="<?= BASE_PATH ?>modules/payments/index.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-money-bill-wave"></i> Proceed to Payment
                        </a>
                    </div>
                    <div class="mt-3">
                        <a href="<?= BASE_PATH ?>index.php" class="text-muted">Return to Dashboard</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger shadow">
                <h4><i class="fas fa-times-circle"></i> Enrollment Failed</h4>
                <p>An error occurred while saving the enrollment data. No changes were made.</p>
                <small><?= htmlspecialchars($error_message) ?></small>
                <br><br>
                <a href="step1.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-danger">Start Over</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>