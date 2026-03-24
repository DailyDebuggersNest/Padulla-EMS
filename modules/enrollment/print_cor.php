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

$semesterLabel = ((int) $enrollment['semester_id'] === 3)
    ? 'Summer'
    : ((int) $enrollment['semester_id'] . ' Semester');

$subjectCount = count($schedules);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Registration - <?= htmlspecialchars($enrollment['student_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --paper: #ffffff;
            --ink: #1e2635;
            --muted: #59667a;
            --line: #d6deea;
            --brand: #124976;
            --brand-soft: #ecf5ff;
            --accent: #0b7b61;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 22px;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at top right, #f5f9ff 0%, #edf1f7 45%, #e7edf5 100%);
        }
        .cor-shell {
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--paper);
            box-shadow: 0 18px 40px rgba(16, 35, 58, 0.13);
        }
        .cor-head {
            padding: 24px 28px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(135deg, #f8fcff 0%, #eef5ff 65%, #edf3fb 100%);
        }
        .head-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: start;
        }
        .head-kicker {
            margin: 0 0 4px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }
        .head-title {
            margin: 0;
            font-size: 29px;
            font-weight: 900;
            letter-spacing: 0.4px;
            color: #133e66;
        }
        .head-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .head-badge {
            border: 1px solid #cfe0f2;
            border-radius: 12px;
            padding: 10px 13px;
            background: #fff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: #18486d;
            text-align: center;
            font-weight: 700;
            min-width: 220px;
        }
        .cor-body {
            padding: 20px 28px 26px;
        }
        .scope-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .scope-item {
            border: 1px solid var(--line);
            border-radius: 11px;
            padding: 10px 12px;
            background: #fcfdff;
        }
        .scope-item .label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--muted);
            margin-bottom: 2px;
            font-weight: 700;
        }
        .scope-item .value {
            font-size: 14px;
            font-weight: 700;
            color: #182a3f;
        }
        .student-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .student-card .card-head {
            background: var(--brand-soft);
            border-bottom: 1px solid #d5e7fb;
            padding: 9px 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #2a4f76;
        }
        .student-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            padding: 12px;
            font-size: 14px;
        }
        .student-grid .label {
            display: inline-block;
            width: 110px;
            color: var(--muted);
            font-weight: 600;
        }
        .section-title {
            margin: 0 0 8px;
            color: var(--brand);
            font-weight: 800;
            font-size: 16px;
        }
        .subject-table {
            margin-bottom: 0;
            border-color: #dbe4f1;
        }
        .subject-table thead th {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.8px;
            color: #56667c;
            font-weight: 800;
            background: #f8fbff;
            white-space: nowrap;
        }
        .subject-table td, .subject-table th {
            border-color: #dbe4f1;
            vertical-align: middle;
        }
        .course-pill {
            border: 1px solid #d5e2f2;
            border-radius: 999px;
            padding: 4px 9px;
            background: #f2f7fd;
            font-size: 11px;
            color: #314860;
            font-weight: 700;
            display: inline-block;
        }
        .summary-footer {
            margin-top: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: end;
        }
        .assessment-box {
            border: 1px solid #cde7dc;
            border-radius: 12px;
            padding: 11px 12px;
            background: linear-gradient(135deg, #f4fffb 0%, #ecf9f4 100%);
        }
        .assessment-box .title {
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 11px;
            color: #3e6e63;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .assessment-box .amount {
            font-size: 25px;
            font-weight: 900;
            color: var(--accent);
            line-height: 1;
        }
        .signature-box {
            text-align: center;
            padding-top: 22px;
        }
        .signature-line {
            width: 76%;
            margin: 0 auto;
            border-bottom: 1px solid #243244;
            height: 26px;
        }
        .signature-label {
            margin-top: 5px;
            font-size: 12px;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #4e5c71;
            font-weight: 700;
        }
        .action-bar {
            text-align: center;
            margin-bottom: 12px;
        }
        .action-bar .btn { min-width: 120px; }
        @media (max-width: 880px) {
            .head-row,
            .scope-grid,
            .student-grid,
            .summary-footer {
                grid-template-columns: 1fr;
            }
            .head-badge {
                text-align: left;
                min-width: 0;
            }
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .cor-shell {
                max-width: 100%;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
            .cor-head { padding-top: 8px; }
        }
    </style>
</head>
<body>
    <div class="action-bar no-print">
        <button onclick="window.print()" class="btn btn-primary">Print COR</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="cor-shell">
        <div class="cor-head">
            <div class="head-row">
                <div>
                    <p class="head-kicker">Enrollment Management System</p>
                    <h1 class="head-title">UA ACADEMY</h1>
                    <p class="head-subtitle">Office of the University Registrar</p>
                </div>
                <div class="head-badge">
                    Certificate of Registration<br>
                    Ref: <?= htmlspecialchars($enrollment['student_id']) ?>
                </div>
            </div>
        </div>

        <div class="cor-body">
            <div class="scope-grid">
                <div class="scope-item">
                    <span class="label">Academic Year</span>
                    <span class="value"><?= htmlspecialchars($enrollment['academic_year']) ?></span>
                </div>
                <div class="scope-item">
                    <span class="label">Semester</span>
                    <span class="value"><?= htmlspecialchars($semesterLabel) ?></span>
                </div>
                <div class="scope-item">
                    <span class="label">Enrollment Status</span>
                    <span class="value"><?= htmlspecialchars($enrollment['status']) ?></span>
                </div>
            </div>

            <div class="student-card">
                <div class="card-head">Student Profile</div>
                <div class="student-grid">
                    <div><span class="label">Student No:</span> <strong><?= htmlspecialchars($enrollment['student_id']) ?></strong></div>
                    <div><span class="label">Program:</span> <?= htmlspecialchars($enrollment['program_name'] ?? 'N/A') ?></div>
                    <div><span class="label">Name:</span> <?= htmlspecialchars(strtoupper($enrollment['last_name'] . ', ' . $enrollment['first_name'])) ?></div>
                    <div><span class="label">Year Level:</span> <?= htmlspecialchars($enrollment['year_level_id']) ?></div>
                </div>
            </div>

            <h5 class="section-title">Enrolled Subjects (<?= number_format($subjectCount) ?>)</h5>
            <table class="table table-bordered table-sm subject-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th class="text-end">Units</th>
                        <th>Schedule</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($subjectCount > 0): ?>
                        <?php foreach($schedules as $sched): ?>
                            <tr>
                                <td><span class="course-pill"><?= htmlspecialchars($sched['course_code']) ?></span></td>
                                <td><?= htmlspecialchars($sched['course_name']) ?></td>
                                <td class="text-end"><?= number_format((float)$sched['total_units'], 1) ?></td>
                                <td><?= htmlspecialchars($sched['days'] . ' ' . date('h:i A', strtotime($sched['time_start'])) . '-' . date('h:i A', strtotime($sched['time_end']))) ?></td>
                                <td><?= htmlspecialchars($sched['room']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No enrolled subject schedule was found for this registration.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end fw-bold">Total Units</td>
                        <td class="text-end fw-bold"><?= number_format((float)$enrollment['total_units'], 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="summary-footer">
                <div class="assessment-box">
                    <div class="title">Total Amount Assessed</div>
                    <div class="amount">&#8369;<?= number_format((float)$enrollment['assessed_amount'], 2) ?></div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">University Registrar / Date</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
