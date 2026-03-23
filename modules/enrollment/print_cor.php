<?php
// modules/enrollment/print_cor.php
// Validates and prints the Certificate of Registration
require_once '../../config/db.php';

$enrollment_id = $_GET['id'] ?? ($_GET['enrollment_id'] ?? '');
if(!$enrollment_id) die("Enrollment ID required.");

// Fetch enrollment and student info
$stmt = $pdo->prepare("
    SELECT e.*, s.first_name, s.last_name, s.year_level_id, s.status, p.program_code, p.program_name 
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    LEFT JOIN programs p ON s.program_id = p.program_id
    WHERE e.enrollment_id = ?
");
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch();

if(!$enrollment) die("Enrollment record not found.");

// Fetch schedules/subjects
$schedStmt = $pdo->prepare("
    SELECT c.course_code, c.course_name, c.total_units, s.days, s.time_start, s.time_end, s.room, s.section_code
    FROM enrollment_schedules es
    JOIN schedules s ON es.schedule_id = s.schedule_id
    JOIN courses c ON s.course_id = c.course_id
    WHERE es.enrollment_id = ?
");
$schedStmt->execute([$enrollment_id]);
$schedules = $schedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Registration - <?= htmlspecialchars($enrollment['student_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; background: #fff; padding: 20px; }
        .cor-container { border: 2px solid #000; padding: 30px; margin: 0 auto; max-width: 900px; }
        .school-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px; }
        .school-header h2 { margin: 0; font-weight: bold; }
        .info-table th { width: 150px; text-align: left; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .cor-container { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print COR</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="cor-container">
        <div class="school-header">
            <h2>UA ACADEMY</h2>
            <p class="mb-0">Office of the University Registrar</p>
            <h4 class="mt-2 text-uppercase">Certificate of Registration</h4>
        </div>

        <div class="row mb-4">
            <div class="col-sm-8">
                <table class="info-table">
                    <tr><th>Student No:</th><td><strong><?= htmlspecialchars($enrollment['student_id']) ?></strong></td></tr>
                    <tr><th>Name:</th><td><?= htmlspecialchars(strtoupper($enrollment['last_name'] . ', ' . $enrollment['first_name'])) ?></td></tr>
                    <tr><th>Program:</th><td><?= htmlspecialchars($enrollment['program_name'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="col-sm-4 text-end">
                <table class="info-table ms-auto">
                    <tr><th>Term:</th><td><?= htmlspecialchars(($enrollment['semester_id'] == 3 ? 'Summer' : $enrollment['semester_id'] . ' Sem') . ', ' . $enrollment['academic_year']) ?></td></tr>
                    <tr><th>Year Level:</th><td><?= htmlspecialchars($enrollment['year_level_id']) ?></td></tr>
                    <tr><th>Status:</th><td><?= htmlspecialchars($enrollment['status']) ?></td></tr>
                </table>
            </div>
        </div>

        <h5 class="border-bottom border-dark pb-2">Enrolled Subjects</h5>
        <table class="table table-bordered border-dark table-sm mt-3">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Units</th>
                    <th>Schedule</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($schedules as $sched): ?>
                    <tr>
                        <td><?= htmlspecialchars($sched['course_code']) ?></td>
                        <td><?= htmlspecialchars($sched['course_name']) ?></td>
                        <td><?= htmlspecialchars($sched['total_units']) ?></td>
                        <td><?= htmlspecialchars($sched['days'] . ' ' . date('h:i A', strtotime($sched['time_start'])) . '-' . date('h:i A', strtotime($sched['time_end']))) ?></td>
                        <td><?= htmlspecialchars($sched['room']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold">TOTAL UNITS:</td>
                    <td colspan="3" class="fw-bold"><?= number_format($enrollment['total_units'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-4">
            <div class="col-6">
                <table class="table table-sm table-borderless w-75">
                    <tr><td class="fw-bold">Assessment:</td><td></td></tr>
                    <tr><td>Total Amount Assessed:</td><td class="fw-bold">&#8369;<?= number_format($enrollment['assessed_amount'], 2) ?></td></tr>
                </table>
            </div>
            <div class="col-6 text-center mt-5">
                <div class="border-bottom border-dark w-75 mx-auto"></div>
                <small>University Registrar / Date</small>
            </div>
        </div>
    </div>
</body>
</html>
