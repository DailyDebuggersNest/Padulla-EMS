<?php
// modules/enrollment/records.php
// To view all historical enrollments
require_once '../../config/db.php';
include_once '../../includes/header.php';

$term = $_GET['term'] ?? '2025-2026';
$sem = $_GET['sem'] ?? 1;

$stmt = $pdo->prepare("
    SELECT e.*, s.first_name, s.last_name, p.program_code
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    LEFT JOIN programs p ON s.program_id = p.program_id
    WHERE e.academic_year = ? AND e.semester_id = ?
    ORDER BY e.enrollment_date DESC
");
$stmt->execute([$term, $sem]);
$records = $stmt->fetchAll();

$summary = [
    'count' => count($records),
    'units' => 0,
    'assessed' => 0,
    'enrolled' => 0,
];

foreach ($records as $row) {
    $summary['units'] += (float) ($row['total_units'] ?? 0);
    $summary['assessed'] += (float) ($row['assessed_amount'] ?? 0);
    if (strcasecmp((string) ($row['status'] ?? ''), 'Enrolled') === 0) {
        $summary['enrolled']++;
    }
}

$avgUnits = $summary['count'] > 0 ? $summary['units'] / $summary['count'] : 0;
$avgAssessment = $summary['count'] > 0 ? $summary['assessed'] / $summary['count'] : 0;

if ((int) $sem === 3) {
    $semesterLabel = 'Summer';
} elseif ((int) $sem === 2) {
    $semesterLabel = '2nd Semester';
} else {
    $semesterLabel = '1st Semester';
}
?>

<div class="page-hero mb-4">
    <div class="page-hero-body d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3">
        <div>
            <span class="page-hero-kicker"><i class="fas fa-archive"></i> Registrar Archive</span>
            <h2 class="page-hero-title">Enrollment Records</h2>
            <p class="page-hero-text">Review archived enrollments, compare term volume, and print official COR records fast.</p>
        </div>
        <div class="text-xl-end">
            <div class="small text-muted mb-1">Loaded Scope</div>
            <span class="badge rounded-pill text-bg-dark px-3 py-2"><?= htmlspecialchars($term) ?> | <?= htmlspecialchars($semesterLabel) ?></span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Record Count</div>
                <div class="h4 mb-0"><?= number_format($summary['count']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Units</div>
                <div class="h4 mb-0"><?= number_format($summary['units'], 1) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Assessed</div>
                <div class="h4 mb-0 text-primary">&#8369;<?= number_format($summary['assessed'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Average Per Student</div>
                <div class="small">Units: <strong><?= number_format($avgUnits, 1) ?></strong></div>
                <div class="small">Assessment: <strong>&#8369;<?= number_format($avgAssessment, 2) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-uppercase text-muted mb-1">Academic Year</label>
                <input type="text" name="term" class="form-control" placeholder="e.g. 2025-2026" value="<?= htmlspecialchars($term) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-uppercase text-muted mb-1">Semester</label>
                <select name="sem" class="form-select">
                    <option value="1" <?= (int)$sem === 1 ? 'selected' : '' ?>>1st Semester</option>
                    <option value="2" <?= (int)$sem === 2 ? 'selected' : '' ?>>2nd Semester</option>
                    <option value="3" <?= (int)$sem === 3 ? 'selected' : '' ?>>Summer</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Apply</button>
            </div>
            <div class="col-md-2">
                <a href="records.php" class="btn btn-outline-secondary w-100"><i class="fas fa-undo me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Enrollment List</h5>
            <small class="text-muted"><?= number_format($summary['enrolled']) ?> marked Enrolled in this selected scope.</small>
        </div>
        <span class="badge rounded-pill text-bg-secondary"><?= number_format($summary['count']) ?> rows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th class="text-end">Total Units</th>
                    <th class="text-end">Assessment</th>
                    <th class="text-center">Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($records) > 0): ?>
                    <?php foreach($records as $rec): ?>
                        <?php
                            $isEnrolled = strcasecmp((string)($rec['status'] ?? ''), 'Enrolled') === 0;
                        ?>
                        <tr>
                            <td><span class="badge rounded-pill text-bg-secondary"><?= htmlspecialchars($rec['student_id']) ?></span></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?></div>
                                <div class="small text-muted"><?= date('M d, Y', strtotime($rec['enrollment_date'])) ?></div>
                            </td>
                            <td><?= htmlspecialchars($rec['program_code'] ?: 'No Program') ?></td>
                            <td class="text-end"><?= number_format((float)$rec['total_units'], 1) ?></td>
                            <td class="text-end">&#8369;<?= number_format((float)$rec['assessed_amount'], 2) ?></td>
                            <td class="text-center"><span class="badge <?= $isEnrolled ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($rec['status']) ?></span></td>
                            <td>
                                <a href="print_cor.php?id=<?= $rec['enrollment_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-1"></i> COR
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No enrollment records found for <?= htmlspecialchars($term) ?> (<?= htmlspecialchars($semesterLabel) ?>).</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>