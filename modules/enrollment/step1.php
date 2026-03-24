<?php
// modules/enrollment/step1.php
// Enrollment Step 1 & 2 & 3: Select Term, Load Student, Choose Subjects
require_once '../../config/db.php';
include_once '../../includes/header.php';

$student_id = $_GET['student_id'] ?? '';
$term_code = $_GET['term_code'] ?? '';

// Find the active/latest term
$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();
$default_acad_year = $latestTerm ? $latestTerm['academic_year'] : '2025-2026';
$default_semester = $latestTerm ? $latestTerm['semester_id'] : 1;

// Default fallback if no term code
$acad_year = $default_acad_year;
$semester_id = $default_semester;

if (!empty($term_code) && strlen($term_code) === 3) {
    // Parse term code (e.g. 271 -> 27 and 1)
    $yr_prefix = substr($term_code, 0, 2); // e.g. '27'
    $term_suffix = substr($term_code, 2, 1); // e.g. '1'
    
    // Calculate academic year
    $end_year = 2000 + (int)$yr_prefix; // e.g. 2027
    $start_year = $end_year - 1;        // e.g. 2026
    $acad_year = $start_year . '-' . $end_year; // 2026-2027
    
    // Determine Semester
    if ($term_suffix === '1') {
        $semester_id = 1;
    } elseif ($term_suffix === '2') {
        $semester_id = 2;
    } elseif ($term_suffix === '0') {
        $semester_id = 3;
    }
} else if (isset($_GET['acad_year']) && isset($_GET['semester_id'])) {
    $acad_year = $_GET['acad_year'];
    $semester_id = $_GET['semester_id'];
}

if (!$student_id) {
    echo "<div class='alert alert-danger'>Student ID is required!</div>";
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
    echo "<div class='alert alert-danger'>Student not found!</div>";
    include_once '../../includes/footer.php';
    exit;
}

// Ensure program exists - REMOVED since we now choose it on the fly during enrollment step 1

$force_edit = isset($_GET['force_edit']) ? true : false;

// Check if already enrolled in this specific term
$checkEnrolled = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND academic_year = ? AND semester_id = ? AND status != 'Cancelled'");
$checkEnrolled->execute([$student_id, $acad_year, $semester_id]);
$already_enrolled = $checkEnrolled->fetch();

// Auto-Load subjects from curriculum based on Student's Program & Year Level & selected Term
$selected_program = $_GET['program_id'] ?? $student['program_id'];
$selected_year_level = $_GET['year_level_id'] ?? $student['year_level_id'];

if (!$selected_program) {
    // Default to the first program in the system if the student is entirely new
    $fallbackProg = $pdo->query("SELECT program_id FROM programs LIMIT 1")->fetchColumn();
    $selected_program = $fallbackProg;
}
if ($semester_id == 3) {
    // For Summer, fetch ALL courses in the program so students can retake/add failed subjects
    $currStmt = $pdo->prepare("
        SELECT c.*, MIN(cr.curriculum_id) as curriculum_id
        FROM curriculum cr
        JOIN courses c ON cr.course_id = c.course_id
        WHERE cr.program_id = ?
        GROUP BY c.course_id
    ");
    $currStmt->execute([$selected_program]);
} else {
    $currStmt = $pdo->prepare("
        SELECT c.*, cr.curriculum_id
        FROM curriculum cr
        JOIN courses c ON cr.course_id = c.course_id
        WHERE cr.program_id = ?
          AND cr.year_level_id = ?
          AND cr.semester_id = ?
    ");
    $currStmt->execute([$selected_program, $selected_year_level, $semester_id]);
}

$curriculum_subjects = $currStmt->fetchAll();

// We also need available schedules for these subjects so the user can select a section
// We will AJAX this or just load the data all at once. Since enrollment is step based, loading all section options here is nice.

?>

<div class="page-hero mb-4">
    <div class="page-hero-body">
        <span class="page-hero-kicker"><i class="fas fa-list-check"></i> Enrollment Workflow</span>
        <h2 class="page-hero-title">Step 1: Term and Subject Selection</h2>
        <p class="page-hero-text">Set the academic term, choose program context, and verify curriculum subjects before assessment.</p>
    </div>
</div>

<!-- Progress Bar -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="workflow-progress d-flex justify-content-between align-items-center">
            <span class="workflow-step active">1. Term & Subjects</span>
            <i class="fas fa-arrow-right workflow-arrow"></i>
            <span class="workflow-step">2. Assessment</span>
            <i class="fas fa-arrow-right workflow-arrow"></i>
            <span class="workflow-step">3. Confirmation</span>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Student Information Card -->
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header panel-header-strong">
                <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Student Target</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><th style="width: 35%">ID:</th><td><span class="badge bg-secondary"><?= htmlspecialchars($student['student_id']) ?></span></td></tr>
                    <tr><th>Name:</th><td class="fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td></tr>
                    <tr><th>Program:</th><td><?= htmlspecialchars($student['program_name'] ?? 'N/A') ?></td></tr>
                    <tr><th>Year Level:</th><td><?= htmlspecialchars($student['year_level_id']) ?></td></tr>
                    <tr><th>Status:</th><td>
                        <?php 
                            $badge = 'bg-success';
                            if ($student['status'] == 'Irregular') $badge = 'bg-warning text-dark';
                        ?>
                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($student['status']) ?></span>
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Enrollment Term Selection -->
    <div class="col-md-7">
        <div class="card shadow-sm h-100">
            <div class="card-header panel-header-strong">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Set Academic Term</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="step1.php">
                    <?php if ($force_edit): ?>
                        <input type="hidden" name="force_edit" value="1">
                    <?php endif; ?>
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-4">
                            <label>Academic Year</label>
                            <input type="text" name="acad_year" class="form-control" value="<?= htmlspecialchars($acad_year) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label>Semester</label>
                            <select name="semester_id" class="form-select" onchange="this.form.submit()">
                                <option value=1 <?= $semester_id == 1 ? 'selected' : '' ?>>1st Sem</option>
                                <option value=2 <?= $semester_id == 2 ? 'selected' : '' ?>>2nd Sem</option>
                                <option value=3 <?= $semester_id == 3 ? 'selected' : '' ?>>Summer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Year Level</label>
                            <select name="year_level_id" class="form-select" onchange="this.form.submit()">
                                <option value="1" <?= $selected_year_level == '1' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $selected_year_level == '2' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $selected_year_level == '3' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $selected_year_level == '4' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label>Program</label>
                            <select name="program_id" class="form-select" onchange="this.form.submit()">
                                <?php 
                                    $progStmt = $pdo->query("SELECT program_id, program_code, program_name FROM programs");
                                    $all_programs = $progStmt->fetchAll();
                                    
                                    // Make sure we carry over the previously decided fallback program
                                    foreach ($all_programs as $p):
                                ?>
                                    <option value="<?= $p['program_id'] ?>" <?= $selected_program == $p['program_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['program_code']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Section</label>
                            <?php $selected_section = $_GET['section'] ?? 'X'; ?>
                            <select name="section" class="form-select" onchange="this.form.submit()">
                                <option value="X" <?= $selected_section == 'X' ? 'selected' : '' ?>>X</option>
                                <option value="Y" <?= $selected_section == 'Y' ? 'selected' : '' ?>>Y</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-4">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-sync"></i> Refresh</button>
                        </div>
                    </div>
                </form>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i> Showing subjects for <strong>Year <?= htmlspecialchars($selected_year_level) ?> - <?= $semester_id == 3 ? 'Summer' : htmlspecialchars($semester_id) . ' Semester' ?></strong> based on curriculum.
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($already_enrolled && !$force_edit): ?>
    <div class="alert alert-warning mb-4 shadow-sm border-warning">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="alert-heading text-warning mb-1"><i class="fas fa-exclamation-triangle"></i> Already Enrolled</h4>
                <p class="mb-0 text-dark">This student is already officially enrolled for the <strong><?= $semester_id == 3 ? 'Summer' : htmlspecialchars($semester_id) . ' Semester' ?>, S.Y. <?= htmlspecialchars($acad_year) ?></strong>.</p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>modules/students/view.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-outline-warning"><i class="fas fa-eye"></i> View Profile</a>
                <a href="step1.php?student_id=<?= urlencode($student_id) ?>&acad_year=<?= urlencode($acad_year) ?>&semester_id=<?= urlencode($semester_id) ?>&program_id=<?= urlencode($selected_program) ?>&year_level_id=<?= urlencode($selected_year_level) ?>&section=<?= urlencode($selected_section ?? 'X') ?>&force_edit=1" class="btn btn-warning ms-2"><i class="fas fa-edit"></i> Force Modify Course/Subjects</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Subjects Selection Form -->
<form action="step2_process.php" method="POST" id="enrollmentForm" <?= ($already_enrolled && !$force_edit) ? 'style="display:none;"' : '' ?>>
    <?php if ($force_edit): ?>
        <input type="hidden" name="force_edit" value="1">
    <?php endif; ?>
    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
    <input type="hidden" name="acad_year" value="<?= htmlspecialchars($acad_year) ?>">
    <input type="hidden" name="semester_id" value="<?= htmlspecialchars($semester_id) ?>">
    <input type="hidden" name="program_id" value="<?= htmlspecialchars($selected_program) ?>">
    <input type="hidden" name="year_level_id" value="<?= htmlspecialchars($selected_year_level) ?>">
    <input type="hidden" name="section" value="<?= htmlspecialchars($selected_section ?? 'X') ?>">

    <div class="card shadow-sm mb-4">
        <div class="card-header panel-header-strong d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-book"></i> Available Curriculum Subjects</h5>
            <span class="badge bg-light text-dark">Total Units: <span id="totalUnitsBadge">0.00</span></span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 table-clean">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">
                            <input type="checkbox" id="checkAll" class="form-check-input" checked>
                        </th>
                        <th>Course Code</th>
                        <th>Course Description</th>
                        <th>Units</th>
                        <th>Schedule/Section</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($curriculum_subjects) > 0): ?>
                        <?php foreach ($curriculum_subjects as $index => $subject): ?>
                            <?php
                                    // Fetch available schedules for this subject in the current term
                                    $target_section = current(explode('-', $selected_section ?? 'X')); // Just in case it's Sec-X
                                    if ($target_section !== 'X' && $target_section !== 'Y') $target_section = 'X';
                                    
                                    $schedStmt = $pdo->prepare("
                                        SELECT s.*, t.first_name, t.last_name
                                        FROM schedules s
                                        LEFT JOIN teachers t ON s.teacher_id = t.teacher_id
                                        WHERE s.course_id = ? AND s.academic_year = ? AND s.semester_id = ? AND s.section_code = ?
                                    ");
                                    $schedStmt->execute([$subject['course_id'], $acad_year, $semester_id, $target_section]);
                                    $schedules = $schedStmt->fetchAll();
                                    
                                    // Make sure a subject appears in summer even if there's no official schedule assigned yet
                                    if(count($schedules) == 0 && $semester_id == 3) {
                                        $insertSched = $pdo->prepare("INSERT INTO schedules (course_id, academic_year, semester_id, days, time_start, time_end, room, capacity, section_code) VALUES (?, ?, ?, 'TBA', '08:00:00', '11:00:00', 'TBA', 40, ?)");
                                        $insertSched->execute([$subject['course_id'], $acad_year, $semester_id, $target_section]);
                                        
                                        $schedStmt->execute([$subject['course_id'], $acad_year, $semester_id, $target_section]);
                                        $schedules = $schedStmt->fetchAll();
                                    }

                                    // If no schedule exists for this specific section, skip showing the subject
                                    if(count($schedules) == 0) {
                                        continue;
                                    }
                                ?>
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input subject-checkbox" 
                                           name="subjects[]" value="<?= $subject['course_id'] ?>" 
                                           data-units="<?= $subject['total_units'] ?>" checked>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($subject['course_code']) ?></td>
                                <td><?= htmlspecialchars($subject['course_name']) ?></td>
                                <td><?= htmlspecialchars($subject['total_units']) ?></td>
                                <td>
                                    <?php if(count($schedules) > 0): ?>
                                        <?php 
                                            // Automatically pick the first schedule
                                            $sched = $schedules[0]; 
                                            $disp_section = $selected_section ?? 'X';
                                            $label = "{$disp_section} | {$sched['days']} " . date('h:i A', strtotime($sched['time_start'])) . " - " . date('h:i A', strtotime($sched['time_end'])) . " | Room: {$sched['room']}";
                                        ?>
                                        <input type="hidden" name="schedule_<?= $subject['course_id'] ?>" value="<?= $sched['schedule_id'] ?>">
                                        <div class="small fw-semibold text-dark"><?= htmlspecialchars($label) ?></div>
                                    <?php else: ?>
                                        <div class="text-danger small"><i class="fas fa-exclamation-triangle"></i> No schedule available</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No subjects found in curriculum for this term.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-success btn-lg" id="btnNext" <?= count($curriculum_subjects) == 0 ? 'disabled' : '' ?>>
                Proceed to Assessment <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.subject-checkbox');
        const totalUnitsBadge = document.getElementById('totalUnitsBadge');
        
        function calculateTotalUnits() {
            let total = 0;
            checkboxes.forEach(cb => {
                if(cb.checked) {
                    total += parseFloat(cb.getAttribute('data-units'));
                }
            });
            totalUnitsBadge.innerText = total.toFixed(2);
        }

        if(checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleSelectRequirement();
                calculateTotalUnits();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                toggleSelectRequirement();
                calculateTotalUnits();
            });
        });

        // Makes the select required only if the checkbox for that subject is checked
        function toggleSelectRequirement() {
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                const selectBox = tr.querySelector('.sched-select');
                if(selectBox) {
                    if(cb.checked) {
                        selectBox.setAttribute('required', 'required');
                        selectBox.removeAttribute('disabled');
                    } else {
                        selectBox.removeAttribute('required');
                        selectBox.setAttribute('disabled', 'disabled');
                    }
                }
            });
        }

        // Initialize
        toggleSelectRequirement();
        calculateTotalUnits();
    });
</script>

<?php include_once '../../includes/footer.php'; ?>


