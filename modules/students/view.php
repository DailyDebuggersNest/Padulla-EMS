<?php
// modules/students/view.php
// View Student Profile & Enrollment History
require_once '../../config/db.php';
include_once '../../includes/header.php';

$student_id = $_GET['student_id'] ?? '';

if (!$student_id) {
    echo "<div class='alert alert-danger mx-4 mt-4'>Student ID is required.</div>";
    include_once '../../includes/footer.php';
    exit;
}

// Fetch Student details
$stmt = $pdo->prepare("SELECT s.*, p.program_code, p.program_name 
                       FROM students s 
                       LEFT JOIN programs p ON s.program_id = p.program_id 
                       WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='alert alert-danger mx-4 mt-4'>Student not found.</div>";
    include_once '../../includes/footer.php';
    exit;
}

// Fetch Enrollment History
$enrollmentStmt = $pdo->prepare("
    SELECT e.*,
        (SELECT MAX(curr.year_level_id) 
         FROM enrollment_schedules es 
         JOIN schedules s ON es.schedule_id = s.schedule_id 
         JOIN curriculum curr ON s.course_id = curr.course_id 
         WHERE es.enrollment_id = e.enrollment_id AND curr.program_id = ?) as curr_year_level
    FROM enrollments e
    WHERE e.student_id = ?
    ORDER BY e.academic_year DESC,
             e.semester_id DESC
");
$enrollmentStmt->execute([$student['program_id'], $student_id]);
$enrollments = $enrollmentStmt->fetchAll();

$latest_year = date('Y');
if (count($enrollments) > 0) {
    $latest_year = (int)substr($enrollments[0]['academic_year'], 0, 4);
}

foreach ($enrollments as &$e) {
    if (!empty($e['curr_year_level'])) {
        $e['inferred_year_level'] = $e['curr_year_level'];
    } else {
        $offset = $latest_year - (int)substr($e['academic_year'], 0, 4);
        $e['inferred_year_level'] = max(1, $student['year_level_id'] - $offset);
    }
}
unset($e);

function getOrdinal($number) {
    if (!is_numeric($number)) return $number;
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if ((($number % 100) >= 11) && (($number%100) <= 13)) return $number. 'th';
    return $number. $ends[$number % 10];
}

?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-user-graduate text-info"></i> Student Profile</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
        <a href="<?= BASE_PATH ?>modules/enrollment/step1.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-primary"><i class="fas fa-check-circle"></i> New Enrollment</a>
    </div>
</div>

<div class="row">
    <!-- Student Information Card -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Personal Info</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="bg-secondary text-white rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 100px; height: 100px; font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Student ID</strong>
                        <span class="badge bg-secondary"><?= htmlspecialchars($student['student_id']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Name</strong>
                        <span class="text-end"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Program</strong>
                        <span class="text-end fw-bold"><?= htmlspecialchars($student['program_code']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Year Level</strong>
                        <span class="text-end"><?= htmlspecialchars($student['year_level_id']) ?> Year</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Section</strong>
                        <span class="text-end fw-bold"><?= htmlspecialchars($student['section'] ?? 'X') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Status</strong>
                        <span class="badge bg-success"><?= htmlspecialchars($student['status']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Enrollment History Dropdown Style -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-dark fw-bold"><i class="fas fa-history text-primary"></i> Enrollment History</h5>
                
                <?php if (count($enrollments) > 0): ?>
                <div class="d-flex align-items-center">
                    <span class="text-muted small me-2 text-nowrap">Select Semester</span>
                    <select class="form-select form-select-sm shadow-none" id="semesterSelect" onchange="showSemester(this.value)" style="min-width: 250px;">
                        <?php foreach ($enrollments as $index => $enrollment): ?>
                            <option value="<?= $enrollment['enrollment_id'] ?>">
                                <?= getOrdinal($enrollment['inferred_year_level']) ?> Year • <?= $enrollment['semester_id'] == 1 ? '1st' : ($enrollment['semester_id'] == 2 ? '2nd' : 'Summer') ?> Semester • S.Y. <?= htmlspecialchars($enrollment['academic_year']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="all">Show All Semesters</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="card-body bg-light">
                <?php if (count($enrollments) > 0): ?>
                    <div id="enrollmentHistoryContainer">
                        <?php foreach ($enrollments as $index => $enrollment): 
                            $enr_id = $enrollment['enrollment_id'];
                            // Fetch subjects for this enrollment
                            $subStmt = $pdo->prepare("
                                SELECT c.course_code, c.course_name, c.total_units
                                FROM enrollment_schedules es
                                JOIN schedules s ON es.schedule_id = s.schedule_id
                                JOIN courses c ON s.course_id = c.course_id
                                WHERE es.enrollment_id = ?
                            ");
                            $subStmt->execute([$enr_id]);
                            $subjects = $subStmt->fetchAll();
                            
                            $statusBadge = 'text-success border-success';
                            $statusText = 'ENROLLED';
                            if ($enrollment['status'] == 'Pending') { $statusBadge = 'text-warning border-warning'; $statusText = 'PENDING'; }
                            if ($enrollment['status'] == 'Cancelled') { $statusBadge = 'text-danger border-danger'; $statusText = 'CANCELLED'; }
                        ?>
                            <div class="semester_id-card mb-4" id="sem_<?= $enr_id ?>" <?= $index !== 0 ? 'style="display:none;"' : '' ?>>
                                <div class="bg-white border rounded p-0 overflow-hidden shadow-sm">
                                    
                                    <!-- Header Row matching Image -->
                                    <div class="d-flex align-items-center px-4 py-3 border-bottom">
                                        <div class="bg-primary text-white rounded px-3 py-1 fw-bold small me-3">
                                            <?= getOrdinal($enrollment['inferred_year_level']) ?> YEAR
                                        </div>
                                        <div class="fs-6 fw-bold me-3"><?= $enrollment['semester_id'] == 1 ? '1st' : ($enrollment['semester_id'] == 2 ? '2nd' : 'Summer') ?> Semester</div>
                                        <div class="text-muted small">S.Y. <?= htmlspecialchars($enrollment['academic_year']) ?></div>
                                        <div class="ms-auto text-end">
                                             <a href="<?= BASE_PATH ?>modules/enrollment/print_cor.php?enrollment_id=<?= $enr_id ?>" target="_blank" class="btn btn-sm btn-outline-secondary border-0"><i class="fas fa-print"></i></a>
                                        </div>
                                    </div>

                                    <!-- Table matching Image -->
                                    <div class="table-responsive px-4 py-2">
                                        <table class="table table-borderless table-hover align-middle mb-0">
                                            <thead class="text-muted small border-bottom">
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="15%">CODE</th>
                                                    <th width="40%">SUBJECT NAME</th>
                                                    <th width="10%" class="text-center">UNITS</th>
                                                    <th width="10%" class="text-center">GRADE</th>
                                                    <th width="10%">REMARKS</th>
                                                    <th width="10%" class="text-center">STATUS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($subjects) > 0): ?>
                                                    <?php foreach ($subjects as $i => $sub): ?>
                                                        <tr class="border-bottom">
                                                            <td class="text-muted"><?= $i + 1 ?></td>
                                                            <td><span class="badge bg-light text-primary border px-2 py-1"><?= htmlspecialchars($sub['course_code']) ?></span></td>
                                                            <td class="fw-bold text-dark"><?= htmlspecialchars($sub['course_name']) ?></td>
                                                            <td class="text-center"><span class="badge bg-light text-primary border rounded-circle p-2 px-3"><?= htmlspecialchars(number_format($sub['total_units'], 0)) ?></span></td>
                                                        <!-- Grades and Status logic -->
                                                        <?php if ($index === 0): ?>
                                                            <td class="text-center"><span class="text-muted fw-bold">--</span></td>
                                                            <td class="text-muted small">Ongoing</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light rounded-pill px-3 py-1 text-primary border border-primary"><i class="fas fa-spinner fa-spin small me-1"></i> TAKING</span>
                                                            </td>
                                                        <?php else: ?>
                                                            <td class="text-center"><span class="badge bg-light text-success border border-success px-2 py-1">1.25</span></td>
                                                            <td class="text-muted small">Excellent</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light rounded-pill px-3 py-1 <?= $statusBadge ?>"><i class="fas fa-circle small me-1"></i> PASSED</span>
                                                            </td>
                                                        <?php endif; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted py-4">No subjects recorded.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Footer matching Image -->
                                    <div class="bg-light px-4 py-3 d-flex justify-content-end align-items-center">
                                        <div class="fw-bold text-dark me-4 fs-5">Semester Total:</div>
                                        <div class="fw-bold text-primary fs-5 text-center lh-1">
                                            <?= htmlspecialchars(number_format($enrollment['total_units'], 0)) ?> <br>
                                            <span class="small" style="font-size: 0.5em;">units</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5>No Enrollment History</h5>
                        <p class="text-muted">This student has not enrolled in any semester yet.</p>
                        <a href="<?= BASE_PATH ?>modules/enrollment/step1.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-primary mt-2">Enroll Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showSemester(enrId) {
    const cards = document.querySelectorAll('.semester_id-card');
    cards.forEach(card => {
        if (enrId === 'all') {
            card.style.display = 'block';
        } else {
            if (card.id === 'sem_' + enrId) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}
</script>

<?php include_once '../../includes/footer.php'; ?>
