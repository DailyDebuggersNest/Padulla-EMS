<?php
require_once '../../config/db.php';
include_once '../../includes/header.php';

$allowedReports = ['enrollment', 'finance', 'capacity'];
$report = $_GET['report'] ?? 'enrollment';
if (!in_array($report, $allowedReports, true)) {
    $report = 'enrollment';
}

$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();

if (!$latestTerm) {
    $scheduleTermStmt = $pdo->query("SELECT academic_year, semester_id FROM schedules ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
    $latestTerm = $scheduleTermStmt->fetch();
}

$defaultTerm = $latestTerm['academic_year'] ?? date('Y') . '-' . (date('Y') + 1);
$defaultSem = isset($latestTerm['semester_id']) ? (int) $latestTerm['semester_id'] : 1;

$term = trim($_GET['term'] ?? $defaultTerm);
$sem = (int) ($_GET['sem'] ?? $defaultSem);
if (!in_array($sem, [1, 2, 3], true)) {
    $sem = $defaultSem;
}

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

$termOptionsStmt = $pdo->query("SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC");
$termOptions = $termOptionsStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($termOptions)) {
    $termOptions = [$defaultTerm];
}

$overview = [
    'records' => 0,
    'students' => 0,
    'assessed' => 0,
    'collected' => 0,
];

$overviewStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS records,
        COUNT(DISTINCT e.student_id) AS students,
        COALESCE(SUM(e.assessed_amount), 0) AS assessed,
        COALESCE(SUM(p.amount), 0) AS collected
     FROM enrollments e
     LEFT JOIN payments p ON p.enrollment_id = e.enrollment_id
     WHERE e.academic_year = ? AND e.semester_id = ?"
);
$overviewStmt->execute([$term, $sem]);
$overview = $overviewStmt->fetch() ?: $overview;

$enrollmentRows = [];
$financeRows = [];
$scheduleRows = [];

$financeStats = [
    'total_collections' => 0,
    'transaction_count' => 0,
    'avg_payment' => 0,
];

$capacityStats = [
    'total_sections' => 0,
    'total_capacity' => 0,
    'total_enrolled' => 0,
    'over_capacity' => 0,
    'near_full' => 0,
];

if ($report === 'enrollment') {
    $enrollmentStmt = $pdo->prepare(
        "SELECT
            COALESCE(p.program_code, 'N/A') AS program_code,
            COALESCE(yl.year_level_name, 'N/A') AS year_level_name,
            COUNT(*) AS total_enrollees,
            SUM(CASE WHEN e.status = 'Enrolled' THEN 1 ELSE 0 END) AS enrolled_count,
            SUM(CASE WHEN e.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN e.status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
            COALESCE(SUM(e.assessed_amount), 0) AS assessed_total
         FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         LEFT JOIN programs p ON p.program_id = COALESCE(e.program_id, s.program_id)
         LEFT JOIN year_levels yl ON yl.year_level_id = s.year_level_id
         WHERE e.academic_year = ? AND e.semester_id = ?
         GROUP BY p.program_code, yl.year_level_name
         ORDER BY p.program_code, yl.year_level_name"
    );
    $enrollmentStmt->execute([$term, $sem]);
    $enrollmentRows = $enrollmentStmt->fetchAll();
}

if ($report === 'finance') {
    $financeWhere = " WHERE e.academic_year = ? AND e.semester_id = ? ";
    $financeParams = [$term, $sem];

    if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $financeWhere .= " AND DATE(p.payment_date) >= ? ";
        $financeParams[] = $fromDate;
    }

    if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $financeWhere .= " AND DATE(p.payment_date) <= ? ";
        $financeParams[] = $toDate;
    }

    $financeStmt = $pdo->prepare(
        "SELECT
            DATE(p.payment_date) AS payment_day,
            COUNT(*) AS transaction_count,
            COALESCE(SUM(p.amount), 0) AS total_amount
         FROM payments p
         JOIN enrollments e ON e.enrollment_id = p.enrollment_id
         {$financeWhere}
         GROUP BY DATE(p.payment_date)
         ORDER BY payment_day DESC"
    );
    $financeStmt->execute($financeParams);
    $financeRows = $financeStmt->fetchAll();

    $financeSummaryStmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(p.amount), 0) AS total_collections,
            COUNT(*) AS transaction_count,
            COALESCE(AVG(p.amount), 0) AS avg_payment
         FROM payments p
         JOIN enrollments e ON e.enrollment_id = p.enrollment_id
         {$financeWhere}"
    );
    $financeSummaryStmt->execute($financeParams);
    $financeStats = $financeSummaryStmt->fetch() ?: $financeStats;
}

if ($report === 'capacity') {
    $capacityStmt = $pdo->prepare(
        "SELECT
            sc.section_code,
            c.course_code,
            c.course_name,
            sc.days,
            sc.time_start,
            sc.time_end,
            sc.room,
            sc.capacity,
            sc.enrolled_count,
            (sc.capacity - sc.enrolled_count) AS slots_left
         FROM schedules sc
         JOIN courses c ON c.course_id = sc.course_id
         WHERE sc.academic_year = ? AND sc.semester_id = ?
         ORDER BY (CASE WHEN sc.capacity > 0 THEN sc.enrolled_count / sc.capacity ELSE 0 END) DESC, sc.section_code"
    );
    $capacityStmt->execute([$term, $sem]);
    $scheduleRows = $capacityStmt->fetchAll();

    $capacitySummaryStmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_sections,
            COALESCE(SUM(capacity), 0) AS total_capacity,
            COALESCE(SUM(enrolled_count), 0) AS total_enrolled,
            SUM(CASE WHEN enrolled_count > capacity THEN 1 ELSE 0 END) AS over_capacity,
            SUM(CASE WHEN capacity > 0 AND enrolled_count >= capacity * 0.90 THEN 1 ELSE 0 END) AS near_full
         FROM schedules
         WHERE academic_year = ? AND semester_id = ?"
    );
    $capacitySummaryStmt->execute([$term, $sem]);
    $capacityStats = $capacitySummaryStmt->fetch() ?: $capacityStats;
}

$reportTitleMap = [
    'enrollment' => 'Enrollment Summary Report',
    'finance' => 'Financial Collection Report',
    'capacity' => 'Schedule Capacity Report',
];
?>

<div class="page-hero mb-4">
    <div class="page-hero-body">
        <span class="page-hero-kicker"><i class="fas fa-chart-bar"></i> Reports</span>
        <h2 class="page-hero-title">System Reports</h2>
        <p class="page-hero-text">Generate actionable enrollment, finance, and class-capacity insights by term.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light pb-0">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Report Type</label>
                <select name="report" class="form-select">
                    <option value="enrollment" <?= $report === 'enrollment' ? 'selected' : '' ?>>Enrollment Summary</option>
                    <option value="finance" <?= $report === 'finance' ? 'selected' : '' ?>>Financial Collection</option>
                    <option value="capacity" <?= $report === 'capacity' ? 'selected' : '' ?>>Schedule Capacity</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Academic Year</label>
                <select name="term" class="form-select">
                    <?php foreach ($termOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= $term === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Semester</label>
                <select name="sem" class="form-select">
                    <option value="1" <?= $sem === 1 ? 'selected' : '' ?>>1st Sem</option>
                    <option value="2" <?= $sem === 2 ? 'selected' : '' ?>>2nd Sem</option>
                    <option value="3" <?= $sem === 3 ? 'selected' : '' ?>>Summer</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-12 mb-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Generate Report</button>
                <a href="index.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-rotate-left me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Report Scope</div>
                <div class="fw-bold"><?= htmlspecialchars($term) ?> / <?= $sem === 3 ? 'Summer' : $sem . ' Sem' ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Enrollment Records</div>
                <div class="fw-bold"><?= number_format((int) $overview['records']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Unique Students</div>
                <div class="fw-bold"><?= number_format((int) $overview['students']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-bold">Collections</div>
                <div class="fw-bold">&#8369;<?= number_format((float) $overview['collected'], 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i><?= htmlspecialchars($reportTitleMap[$report]) ?></h5>
        <span class="badge bg-primary-subtle text-primary"><?= strtoupper($report) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if ($report === 'enrollment'): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Total</th>
                            <th>Enrolled</th>
                            <th>Pending</th>
                            <th>Cancelled</th>
                            <th>Assessed Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollmentRows)): ?>
                            <?php foreach ($enrollmentRows as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['program_code']) ?></td>
                                    <td><?= htmlspecialchars($row['year_level_name']) ?></td>
                                    <td><?= number_format((int) $row['total_enrollees']) ?></td>
                                    <td><?= number_format((int) $row['enrolled_count']) ?></td>
                                    <td><?= number_format((int) $row['pending_count']) ?></td>
                                    <td><?= number_format((int) $row['cancelled_count']) ?></td>
                                    <td>&#8369;<?= number_format((float) $row['assessed_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No enrollment summary data for this term.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report === 'finance'): ?>
            <div class="row g-3 p-3 border-bottom bg-light">
                <div class="col-md-4">
                    <div class="fw-bold text-muted small">Total Collections</div>
                    <div class="fs-5">&#8369;<?= number_format((float) $financeStats['total_collections'], 2) ?></div>
                </div>
                <div class="col-md-4">
                    <div class="fw-bold text-muted small">Transactions</div>
                    <div class="fs-5"><?= number_format((int) $financeStats['transaction_count']) ?></div>
                </div>
                <div class="col-md-4">
                    <div class="fw-bold text-muted small">Average Payment</div>
                    <div class="fs-5">&#8369;<?= number_format((float) $financeStats['avg_payment'], 2) ?></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Transactions</th>
                            <th>Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($financeRows)): ?>
                            <?php foreach ($financeRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M d, Y', strtotime($row['payment_day']))) ?></td>
                                    <td><?= number_format((int) $row['transaction_count']) ?></td>
                                    <td>&#8369;<?= number_format((float) $row['total_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4">No payment data found for the selected filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report === 'capacity'): ?>
            <div class="row g-3 p-3 border-bottom bg-light">
                <div class="col-md-3">
                    <div class="fw-bold text-muted small">Sections</div>
                    <div class="fs-5"><?= number_format((int) $capacityStats['total_sections']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fw-bold text-muted small">Total Capacity</div>
                    <div class="fs-5"><?= number_format((int) $capacityStats['total_capacity']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fw-bold text-muted small">Enrolled Seats</div>
                    <div class="fs-5"><?= number_format((int) $capacityStats['total_enrolled']) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="fw-bold text-muted small">Over / Near Full</div>
                    <div class="fs-5"><?= number_format((int) $capacityStats['over_capacity']) ?> / <?= number_format((int) $capacityStats['near_full']) ?></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Section</th>
                            <th>Course</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Enrolled</th>
                            <th>Slots Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($scheduleRows)): ?>
                            <?php foreach ($scheduleRows as $row): ?>
                                <?php
                                $slotsLeft = (int) $row['slots_left'];
                                $badgeClass = $slotsLeft < 0 ? 'bg-danger' : ($slotsLeft <= 4 ? 'bg-warning text-dark' : 'bg-success');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['section_code'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($row['course_code']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['course_name']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['days']) ?> <?= htmlspecialchars(substr($row['time_start'], 0, 5)) ?>-<?= htmlspecialchars(substr($row['time_end'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars($row['room'] ?? 'N/A') ?></td>
                                    <td><?= number_format((int) $row['capacity']) ?></td>
                                    <td><?= number_format((int) $row['enrolled_count']) ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= number_format($slotsLeft) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No schedule capacity data for this term.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>