<?php
// modules/enrollment/step2_process.php
// Takes the selected subjects & schedules and calculates assessment
require_once '../../config/db.php';
include_once '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<div class='alert alert-danger'>Invalid Request!</div>";
    include_once '../../includes/footer.php';
    exit;
}

$student_id = $_POST['student_id'] ?? '';
$acad_year = $_POST['acad_year'] ?? '';
$semester_id = $_POST['semester_id'] ?? '';
$program_id_selected = $_POST['program_id'] ?? null;
$year_level_selected = $_POST['year_level_id'] ?? null;
$section_selected = $_POST['section'] ?? '';
$selected_subjects = $_POST['subjects'] ?? [];

if (!$student_id || empty($selected_subjects)) {
    echo "<div class='alert alert-warning'>Please select at least one subject to enroll.</div>";
    echo "<a href='step1.php?student_id=$student_id' class='btn btn-primary'>Go Back</a>";
    include_once '../../includes/footer.php';
    exit;
}

// System Constants for Assessment
$TUITION_PER_UNIT = 500.00;
$MISC_FEE = 1500.00;
$LAB_FEE = 800.00; // per lab subject

$total_units = 0;
$total_tuition = 0;
$total_lab_fees = 0;
$enrollment_schedules = [];

// Validate and process selected schedules
foreach ($selected_subjects as $course_id) {
    if (isset($_POST['schedule_' . $course_id])) {
        $schedule_id = $_POST['schedule_' . $course_id];
        
        // Fetch course and schedule details
        $stmt = $pdo->prepare("
            SELECT c.course_code, c.course_name, c.total_units, c.units_lab, s.*
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            WHERE s.schedule_id = ?
        ");
        $stmt->execute([$schedule_id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $total_units += $row['total_units'];
            if ($row['units_lab'] > 0) {
                $total_lab_fees += $LAB_FEE;
            }
            $enrollment_schedules[] = $row;
        }
    }
}

$total_tuition = $total_units * $TUITION_PER_UNIT;
$total_assessment = $total_tuition + $MISC_FEE + $total_lab_fees;

// Fetch Student details for display
$stuStmt = $pdo->prepare("SELECT s.*, p.program_code FROM students s JOIN programs p ON s.program_id = p.program_id WHERE s.student_id = ?");
$stuStmt->execute([$student_id]);
$student = $stuStmt->fetch();

// Quick Verification: Duplicate Enrollment Check
$checkEntry = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND academic_year = ? AND semester_id = ?");
$checkEntry->execute([$student_id, $acad_year, $semester_id]);
if ($checkEntry->rowCount() > 0) {
    echo "<div class='alert alert-danger'><h3><i class='fas fa-exclamation-circle'></i> Already Enrolled</h3><p>This student is already enrolled or pending for $acad_year - $semester_id.</p></div>";
    echo "<a href="<?= BASE_PATH ?>modules/enrollment/records.php' class='btn btn-primary'>View Records</a>";
    include_once '../../includes/footer.php';
    exit;
}

?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center bg-light p-3 border rounded">
            <span class="badge bg-secondary fs-6"><i class="fas fa-check"></i> 1. Term & Subjects</span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span class="badge bg-primary fs-6">2. Assessment Check</span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span class="badge bg-secondary fs-6">3. Confirmation</span>
        </div>
    </div>
</div>

<div class="row">
    <!-- Schedule Review Card -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Class Schedule Confirmation</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Units</th>
                            <th>Section</th>
                            <th>Schedule & Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($enrollment_schedules as $es): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($es['course_code']) ?></td>
                                <td><?= htmlspecialchars($es['course_name']) ?></td>
                                <td><?= htmlspecialchars($es['total_units']) ?></td>
                                <td><?= htmlspecialchars($es['section_code']) ?></td>
                                <td class="small">
                                    <?= htmlspecialchars($es['days'] . ' ' . $es['time_start'] . '-' . $es['time_end']) ?><br>
                                    <span class="text-muted">Rm: <?= htmlspecialchars($es['room']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between">
                <a href="javascript:history.back()" class="btn btn-secondary">Modify Subjects</a>
                <strong>Total Enrolled Units: <?= number_format($total_units, 2) ?></strong>
            </div>
        </div>
    </div>

    <!-- Assessment Card -->
    <div class="col-md-4">
        <div class="card shadow-sm border-primary mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Fee Assessment</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Tuition (₱<?= number_format($TUITION_PER_UNIT, 2) ?> x <?= number_format($total_units, 2) ?>)</td>
                        <td class="text-end">₱<?= number_format($total_tuition, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Miscellaneous Fee</td>
                        <td class="text-end">₱<?= number_format($MISC_FEE, 2) ?></td>
                    </tr>
                    <?php if($total_lab_fees > 0): ?>
                    <tr>
                        <td>Laboratory Fees</td>
                        <td class="text-end">₱<?= number_format($total_lab_fees, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold fs-5 text-primary">
                        <td>Total Amount Due</td>
                        <td class="text-end">₱<?= number_format($total_assessment, 2) ?></td>
                    </tr>
                </table>

                <form method="POST" action="step3_save.php" class="mt-4">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                    <input type="hidden" name="acad_year" value="<?= htmlspecialchars($acad_year) ?>">
                    <input type="hidden" name="semester_id" value="<?= htmlspecialchars($semester_id) ?>">
                    <input type="hidden" name="total_units" value="<?= htmlspecialchars($total_units) ?>">
                    <input type="hidden" name="assessed_amount" value="<?= htmlspecialchars($total_assessment) ?>">
                    <input type="hidden" name="program_id" value="<?= htmlspecialchars($program_id_selected) ?>">
                      <input type="hidden" name="year_level_id" value="<?= htmlspecialchars($year_level_selected) ?>">                    <input type="hidden" name="section" value="<?= htmlspecialchars($section_selected) ?>">                    <?php foreach($enrollment_schedules as $es): ?>
                        <input type="hidden" name="schedule_ids[]" value="<?= htmlspecialchars($es['schedule_id']) ?>">
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="fas fa-check-circle"></i> Confirm Enrollment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>