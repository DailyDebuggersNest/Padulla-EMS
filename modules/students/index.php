<?php
// modules/students/index.php
require_once '../../config/db.php';
include_once '../../includes/header.php';

// Handle Add Student Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';
    $program_id = null; // No longer collected here
    $year_level_id = null; // Default to null (N/A)

    try {
        $pdo->beginTransaction();
        
        // Auto-generate Student ID: UAA-YYYY-NNNN
        $current_year = date('Y');
        $prefix = "UAA-{$current_year}-";
        
        // Find the highest sequence number for the current year
        $stmt_seq = $pdo->prepare("SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
        $stmt_seq->execute([$prefix . '%']);
        $last_id = $stmt_seq->fetchColumn();
        
        $seq = 1;
        if ($last_id) {
            $parts = explode('-', $last_id);
            if (isset($parts[2])) {
                $seq = (int)$parts[2] + 1;
            }
        }
        
        $student_id = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, gender, program_id, year_level_id, status) VALUES (?, ?, ?, ?, ?, ?, 'Regular')");
        $stmt->execute([$student_id, $first_name, $last_name, $gender, $program_id, $year_level_id]);
        
        $pdo->commit();
        echo "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle'></i> Student {$student_id} added successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            echo "<div class='alert alert-danger alert-dismissible fade show'><i class='fas fa-exclamation-triangle'></i> Student ID already exists! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            echo "<div class='alert alert-danger alert-dismissible fade show'>Error adding student: " . $e->getMessage() . " <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// Handle Delete Student Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $student_id_to_delete = $_POST['student_id'] ?? '';
    if ($student_id_to_delete) {
        try {
            $pdo->beginTransaction();

            // Find all enrollment IDs for this student
            $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ?");
            $stmt->execute([$student_id_to_delete]);
            $enrollment_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($enrollment_ids)) {
                $inQuery = implode(',', array_fill(0, count($enrollment_ids), '?'));
                
                // Find all schedules associated with these enrollments
                $stmt = $pdo->prepare("SELECT schedule_id FROM enrollment_schedules WHERE enrollment_id IN ($inQuery)");
                $stmt->execute($enrollment_ids);
                $schedule_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Decrement enrolled_count for those schedules
                if (!empty($schedule_ids)) {
                    $schedInQuery = implode(',', array_fill(0, count($schedule_ids), '?'));
                    $pdo->prepare("UPDATE schedules SET enrolled_count = CASE WHEN enrolled_count > 0 THEN enrolled_count - 1 ELSE 0 END WHERE schedule_id IN ($schedInQuery)")->execute($schedule_ids);
                }

                // Delete enrollment schedules
                $pdo->prepare("DELETE FROM enrollment_schedules WHERE enrollment_id IN ($inQuery)")->execute($enrollment_ids);
            }

            // Delete payments
            $pdo->prepare("DELETE FROM payments WHERE student_id = ?")->execute([$student_id_to_delete]);
            
            // Delete enrollments
            $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$student_id_to_delete]);

            // Finally delete the student
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->execute([$student_id_to_delete]);
            
            $pdo->commit();
            echo "<div class='alert alert-warning alert-dismissible fade show'><i class='fas fa-trash-alt'></i> Student {$student_id_to_delete} (and all related records) deleted successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger alert-dismissible fade show'>Error deleting student: " . $e->getMessage() . " <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

$search = $_GET['search'] ?? '';
$filter_program = $_GET['program_id'] ?? '';
$filter_year = $_GET['year_level_id'] ?? '';

// Find the active/latest term to check current enrollment status
$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();
$active_academic_year = $latestTerm ? $latestTerm['academic_year'] : '2025-2026';
$active_semester = $latestTerm ? $latestTerm['semester_id'] : 1;

// Fetch academic programs for the filter dropdown
$programs = $pdo->query("SELECT * FROM programs ORDER BY program_code")->fetchAll();

// Build query
$sql = "SELECT s.*, p.program_code, p.program_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.student_id AND e.academic_year = '$active_academic_year' AND e.semester_id = '$active_semester' AND e.status != 'Cancelled') as is_currently_enrolled,
        (SELECT section FROM enrollments e WHERE e.student_id = s.student_id AND e.status != 'Cancelled' ORDER BY enrollment_date DESC LIMIT 1) as latest_section,
        (SELECT semester_id FROM enrollments e WHERE e.student_id = s.student_id AND e.status != 'Cancelled' ORDER BY enrollment_date DESC LIMIT 1) as latest_semester,
        (SELECT enrollment_id FROM enrollments e WHERE e.student_id = s.student_id AND e.status != 'Cancelled' ORDER BY enrollment_date DESC LIMIT 1) as latest_enrollment_id
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.program_id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_program) {
    $sql .= " AND s.program_id = ?";
    $params[] = $filter_program;
}
if ($filter_year) {
    $sql .= " AND s.year_level_id = ?";
    $params[] = $filter_year;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-users text-primary"></i> Student Masterlist</h2>
        <p class="text-muted">Browse and select students for enrollment.</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newStudentModal"><i class="fas fa-plus"></i> New Student</button>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" class="row border-bottom pb-3 mb-3">
            <div class="col-md-4 mb-2">
                <input type="text" name="search" class="form-control" placeholder="Search ID or Name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3 mb-2">
                <select name="program_id" class="form-select">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $prog): ?>
                        <option value="<?= $prog['program_id'] ?>" <?= $filter_program == $prog['program_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prog['program_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <select name="year_level_id" class="form-select">
                    <option value="">All Years</option>
                    <option value="1" <?= $filter_year == '1' ? 'selected' : '' ?>>1st Year</option>
                    <option value="2" <?= $filter_year == '2' ? 'selected' : '' ?>>2nd Year</option>
                    <option value="3" <?= $filter_year == '3' ? 'selected' : '' ?>>3rd Year</option>
                    <option value="4" <?= $filter_year == '4' ? 'selected' : '' ?>>4th Year</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php $seq = 1; foreach ($students as $student): ?>
                            <tr>
                                <td><?= $seq++ ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($student['student_id'] ?? '') ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?></td>
                                <td><?= htmlspecialchars(empty(trim($student['program_code'] ?? '')) ? 'N/A' : $student['program_code']) ?></td>
                                <td><?= htmlspecialchars(empty(trim($student['year_level_id'] ?? '')) ? 'N/A' : $student['year_level_id']) ?></td>
                                <td><?= htmlspecialchars(empty(trim($student['latest_section'] ?? '')) ? 'N/A' : $student['latest_section']) ?></td>
                                <td><?= htmlspecialchars(empty(trim($student['latest_semester'] ?? '')) ? 'N/A' : $student['latest_semester'] . ' Sem') ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = 'bg-success';
                                        if ($student['status'] == 'Irregular') $badgeClass = 'bg-warning text-dark';
                                        if ($student['status'] == 'Dropped') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($student['status']) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?student_id=<?= urlencode($student['student_id']) ?>" class="btn btn-sm btn-info text-white">
                                            <i class="fas fa-eye"></i> Profile
                                        </a>
                                        
                                        <a href="edit.php?student_id=<?= urlencode($student['student_id']) ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>

                                        <?php if (!empty($student['latest_enrollment_id'])): ?>
                                            <a href="../payments/pay.php?enrollment_id=<?= urlencode($student['latest_enrollment_id']) ?>" class="btn btn-sm btn-success text-white">
                                                <i class="fas fa-money-bill-wave"></i> Pay
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-money-bill-wave"></i> Pay
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($student['is_currently_enrolled'] > 0): ?>
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-check-circle"></i> Enrolled
                                            </button>
                                        <?php else: ?>
                                            <a href="<?= BASE_PATH ?>modules/enrollment/step1.php?student_id=<?= urlencode($student['student_id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check-circle"></i> Enroll
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                            <input type="hidden" name="action" value="delete_student">
                                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger text-white" style="border-top-left-radius: 0; border-bottom-left-radius: 0;" title="Delete Student">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">No students found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<!-- Add New Student Modal -->
<div class="modal fade" id="newStudentModal" tabindex="-1" aria-labelledby="newStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_student">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="newStudentModalLabel"><i class="fas fa-user-plus"></i> Add New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle"></i> Student ID will be automatically generated as <strong>UAA-<?= date('Y') ?>-XXXX</strong>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>