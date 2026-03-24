<?php
require_once __DIR__ . '/config/db.php';
include_once __DIR__ . '/includes/header.php';

$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();
$active_academic_year = $latestTerm ? $latestTerm['academic_year'] : '2025-2026';
$active_semester = $latestTerm ? (int) $latestTerm['semester_id'] : 1;

$totalEnrolled = (int) $pdo->query("SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE status != 'Cancelled'")->fetchColumn();
$totalPayments = (float) ($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn() ?: 0);

$termEnrollmentStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS term_records,
        SUM(CASE WHEN status = 'Enrolled' THEN 1 ELSE 0 END) AS term_enrolled,
        COALESCE(SUM(assessed_amount), 0) AS term_assessed
     FROM enrollments
     WHERE academic_year = ? AND semester_id = ?"
);
$termEnrollmentStmt->execute([$active_academic_year, $active_semester]);
$termMetrics = $termEnrollmentStmt->fetch() ?: ['term_records' => 0, 'term_enrolled' => 0, 'term_assessed' => 0];

$termCollectionStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(p.amount), 0)
     FROM payments p
     JOIN enrollments e ON e.enrollment_id = p.enrollment_id
     WHERE e.academic_year = ? AND e.semester_id = ?"
);
$termCollectionStmt->execute([$active_academic_year, $active_semester]);
$termCollections = (float) ($termCollectionStmt->fetchColumn() ?: 0);

$paymentTrendStmt = $pdo->query(
    "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, COALESCE(SUM(amount), 0) AS total
     FROM payments
     WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY ym
     ORDER BY ym"
);
$paymentTrendMap = [];
while ($row = $paymentTrendStmt->fetch()) {
    $paymentTrendMap[$row['ym']] = (float) $row['total'];
}

$paymentTrendLabels = [];
$paymentTrendValues = [];
for ($i = 5; $i >= 0; $i--) {
    $month = new DateTime("first day of -{$i} month");
    $ym = $month->format('Y-m');
    $paymentTrendLabels[] = $month->format('M Y');
    $paymentTrendValues[] = $paymentTrendMap[$ym] ?? 0;
}

$statusStmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS total
     FROM enrollments
     WHERE academic_year = ? AND semester_id = ?
     GROUP BY status"
);
$statusStmt->execute([$active_academic_year, $active_semester]);
$statusMap = ['Enrolled' => 0, 'Pending' => 0, 'Cancelled' => 0];
while ($row = $statusStmt->fetch()) {
    if (array_key_exists($row['status'], $statusMap)) {
        $statusMap[$row['status']] = (int) $row['total'];
    }
}

$programScopeLabel = 'Current term';

$programStmt = $pdo->prepare(
    "SELECT COALESCE(p.program_code, 'N/A') AS program_code, COUNT(DISTINCT e.student_id) AS total
     FROM programs p
     LEFT JOIN students s ON s.program_id = p.program_id
     LEFT JOIN enrollments e ON e.student_id = s.student_id
         AND e.academic_year = ?
         AND e.semester_id = ?
         AND e.status != 'Cancelled'
     GROUP BY p.program_code
     ORDER BY p.program_code"
);
$programStmt->execute([$active_academic_year, $active_semester]);
$programRows = $programStmt->fetchAll();

$nonZeroPrograms = 0;
foreach ($programRows as $row) {
    if ((int) $row['total'] > 0) {
        $nonZeroPrograms++;
    }
}

// If current term data is too narrow, show a broader all-active snapshot for better accuracy visibility.
if ($nonZeroPrograms <= 1) {
    $programFallbackStmt = $pdo->query(
        "SELECT COALESCE(p.program_code, 'N/A') AS program_code, COUNT(*) AS total
         FROM students s
         LEFT JOIN programs p ON p.program_id = s.program_id
         WHERE s.status IN ('Regular', 'Irregular', 'Alumni')
         GROUP BY p.program_code
         ORDER BY p.program_code"
    );
    $programRows = $programFallbackStmt->fetchAll();
    $programScopeLabel = 'Active students';
}

$programLabels = [];
$programValues = [];
foreach ($programRows as $row) {
    $programLabels[] = $row['program_code'];
    $programValues[] = (int) $row['total'];
}

$recentPaymentsStmt = $pdo->query(
    "SELECT p.or_number, p.amount, p.payment_date, s.first_name, s.last_name
     FROM payments p
     LEFT JOIN students s ON s.student_id = p.student_id
     ORDER BY p.payment_date DESC
     LIMIT 6"
);
$recentPayments = $recentPaymentsStmt->fetchAll();

$recentEnrollmentsStmt = $pdo->prepare(
    "SELECT e.student_id, e.enrollment_date, e.status, s.first_name, s.last_name, COALESCE(pr.program_code, 'N/A') AS program_code
     FROM enrollments e
     JOIN students s ON s.student_id = e.student_id
     LEFT JOIN programs pr ON pr.program_id = COALESCE(e.program_id, s.program_id)
     WHERE e.academic_year = ? AND e.semester_id = ?
     ORDER BY e.enrollment_date DESC
     LIMIT 8"
);
$recentEnrollmentsStmt->execute([$active_academic_year, $active_semester]);
$recentEnrollments = $recentEnrollmentsStmt->fetchAll();

$enrollmentScopeLabel = 'Current term';
if (count($recentEnrollments) <= 1) {
    $recentEnrollmentsFallbackStmt = $pdo->query(
        "SELECT e.student_id, e.enrollment_date, e.status, s.first_name, s.last_name, COALESCE(pr.program_code, 'N/A') AS program_code
         FROM enrollments e
         JOIN students s ON s.student_id = e.student_id
         LEFT JOIN programs pr ON pr.program_id = COALESCE(e.program_id, s.program_id)
         ORDER BY e.enrollment_date DESC
         LIMIT 8"
    );
    $recentEnrollments = $recentEnrollmentsFallbackStmt->fetchAll();
    $enrollmentScopeLabel = 'All recent terms';
}
?>

<style>
    .dashboard-hero {
        background: linear-gradient(100deg, #f7fbff, #ebf4ff 62%, #e7f9f7);
        border: 1px solid #d6e4f5;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 28px rgba(30, 58, 95, 0.12);
    }

    .dashboard-hero::after {
        content: '';
        position: absolute;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        right: -110px;
        top: -120px;
        background: radial-gradient(circle at center, rgba(42, 95, 143, 0.2), transparent 72%);
    }

    .dashboard-hero-body {
        position: relative;
        z-index: 1;
        padding: 20px;
    }

    .dashboard-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #1e3a5f;
        color: #fff;
        font-size: 0.66rem;
        letter-spacing: 0.9px;
        text-transform: uppercase;
        font-weight: 800;
        font-family: 'Montserrat', sans-serif;
        margin-bottom: 9px;
    }

    .dashboard-title {
        margin: 0;
        color: #1e3a5f;
        font-size: 1.35rem;
        line-height: 1.2;
        letter-spacing: -0.3px;
        font-weight: 900;
        font-family: 'Montserrat', sans-serif;
    }

    .dashboard-subtitle {
        margin: 6px 0 0;
        color: #5f6f84;
        font-size: 0.86rem;
        max-width: 650px;
    }

    .hero-pills {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #cfdeef;
        color: #3d5470;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .kpi-card {
        border: 1px solid #d9e4f2;
        border-radius: 14px;
        background: linear-gradient(170deg, #ffffff, #f8fbff);
        box-shadow: 0 8px 18px rgba(30, 58, 95, 0.08);
    }

    .kpi-card .card-body {
        padding: 14px;
    }

    .kpi-label {
        text-transform: uppercase;
        letter-spacing: 0.75px;
        font-size: 0.66rem;
        color: #5f7288;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .kpi-value {
        margin: 0;
        color: #1e3a5f;
        font-size: 1.35rem;
        line-height: 1.1;
        font-weight: 900;
        font-family: 'Montserrat', sans-serif;
    }

    .kpi-sub {
        margin-top: 4px;
        font-size: 0.74rem;
        color: #708196;
    }

    .panel-card {
        border-radius: 14px;
        overflow: hidden;
    }

    .panel-card .card-header {
        background: linear-gradient(110deg, #f8fbff, #edf5ff);
    }

    .chart-wrap {
        height: 245px;
        position: relative;
    }

    .chart-wrap-small {
        height: 175px;
        position: relative;
    }

    .activity-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .activity-item {
        position: relative;
        padding: 8px 0 8px 14px;
        border-bottom: 1px dashed #dce6f2;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 14px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #0ea5a4;
        box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.15);
    }

    .activity-title {
        margin: 0;
        color: #1e3a5f;
        font-weight: 700;
        font-size: 0.84rem;
    }

    .activity-meta {
        margin: 2px 0 0;
        color: #6d7e93;
        font-size: 0.74rem;
    }

    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }

    @media (max-width: 991.98px) {
        .dashboard-hero-body {
            padding: 16px;
        }

        .dashboard-title {
            font-size: 1.2rem;
        }

        .chart-wrap {
            height: 220px;
        }
    }
</style>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="dashboard-hero h-100">
            <div class="dashboard-hero-body">
                <span class="dashboard-kicker"><i class="fas fa-chart-line"></i> Enrollment Dashboard</span>
                <h2 class="dashboard-title">Enrollment Operations Overview</h2>
                <p class="dashboard-subtitle">Track term performance, payment flow, and student movement from one command center designed for day-to-day school operations.</p>
                <div class="hero-pills">
                    <span class="hero-pill"><i class="fas fa-calendar-alt"></i> Academic Year: <?= htmlspecialchars($active_academic_year) ?></span>
                    <span class="hero-pill"><i class="fas fa-book-open"></i> <?= $active_semester === 3 ? 'Summer Term' : $active_semester . ' Semester' ?></span>
                    <span class="hero-pill"><i class="fas fa-file-signature"></i> Enrollments: <?= number_format((int) $termMetrics['term_records']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-sm-6">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Total Students</div>
                <p class="kpi-value"><?= number_format($totalStudents) ?></p>
                <div class="kpi-sub">Registered records</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Active Enrolled</div>
                <p class="kpi-value"><?= number_format($totalEnrolled) ?></p>
                <div class="kpi-sub">Across all terms</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Term Collections</div>
                <p class="kpi-value">&#8369;<?= number_format($termCollections, 2) ?></p>
                <div class="kpi-sub">Current selected term</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="kpi-label">Term Assessed</div>
                <p class="kpi-value">&#8369;<?= number_format((float) $termMetrics['term_assessed'], 2) ?></p>
                <div class="kpi-sub">Tuition assessment base</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card panel-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Collections Trend (Last 6 Months)</h6>
                <span class="badge bg-primary-subtle text-primary">Live</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="paymentsTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card panel-card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-layer-group text-success me-2"></i>Enrollment Status Mix</h6>
            </div>
            <div class="card-body">
                <div class="chart-wrap-small">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="mt-2 small text-muted">
                    <div><span class="status-dot" style="background:#16a34a"></span>Enrolled: <?= number_format($statusMap['Enrolled']) ?></div>
                    <div><span class="status-dot" style="background:#f59e0b"></span>Pending: <?= number_format($statusMap['Pending']) ?></div>
                    <div><span class="status-dot" style="background:#ef4444"></span>Cancelled: <?= number_format($statusMap['Cancelled']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card panel-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-school text-primary me-2"></i>Enrollment by Program</h6>
                <span class="text-muted small"><?= htmlspecialchars($programScopeLabel) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($programLabels)): ?>
                    <div class="chart-wrap">
                        <canvas id="programChart"></canvas>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No program enrollment data available for this term.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card panel-card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-receipt text-primary me-2"></i>Recent Payment Activity</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($recentPayments)): ?>
                    <ul class="activity-list">
                        <?php foreach ($recentPayments as $payment): ?>
                            <li class="activity-item">
                                <p class="activity-title">
                                    OR <?= htmlspecialchars($payment['or_number']) ?>
                                    <span class="float-end text-success">&#8369;<?= number_format((float) $payment['amount'], 2) ?></span>
                                </p>
                                <p class="activity-meta">
                                    <?= htmlspecialchars(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?: 'Unknown Student' ?>
                                    • <?= date('M d, Y h:i A', strtotime($payment['payment_date'])) ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No payment activity recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card panel-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-user-check text-primary me-2"></i>Latest Enrollments</h6>
        <span class="text-muted small"><?= htmlspecialchars($enrollmentScopeLabel) ?></span>
        <a href="<?= BASE_PATH ?>modules/enrollment/records.php?term=<?= urlencode($active_academic_year) ?>&sem=<?= $active_semester ?>" class="btn btn-sm btn-outline-primary">
            View Full Records
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th>Enrolled At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentEnrollments)): ?>
                        <?php foreach ($recentEnrollments as $enroll): ?>
                            <?php
                            $status = $enroll['status'] ?? 'Pending';
                            $statusClass = $status === 'Enrolled' ? 'bg-success' : ($status === 'Cancelled' ? 'bg-danger' : 'bg-warning text-dark');
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($enroll['student_id']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars(($enroll['last_name'] ?? '') . ', ' . ($enroll['first_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($enroll['program_code']) ?></td>
                                <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td><?= date('M d, Y h:i A', strtotime($enroll['enrollment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No enrollments posted for this term yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trendCtx = document.getElementById('paymentsTrendChart');
        const statusCtx = document.getElementById('statusChart');
        const programCtx = document.getElementById('programChart');

        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($paymentTrendLabels) ?>,
                    datasets: [{
                        label: 'Collections',
                        data: <?= json_encode($paymentTrendValues) ?>,
                        borderColor: '#1e3a5f',
                        backgroundColor: 'rgba(14, 165, 164, 0.18)',
                        fill: true,
                        tension: 0.32,
                        borderWidth: 3,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#0ea5a4'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return ' PHP ' + Number(context.raw || 0).toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(72,96,126,0.12)' },
                            ticks: {
                                callback: function (value) {
                                    return 'PHP ' + Number(value).toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Enrolled', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: <?= json_encode(array_values($statusMap)) ?>,
                        backgroundColor: ['#16a34a', '#f59e0b', '#ef4444'],
                        borderColor: '#ffffff',
                        borderWidth: 3
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 12,
                                font: { weight: '600' }
                            }
                        }
                    }
                }
            });
        }

        if (programCtx) {
            new Chart(programCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($programLabels) ?>,
                    datasets: [{
                        label: 'Enrollees',
                        data: <?= json_encode($programValues) ?>,
                        backgroundColor: ['#1e3a5f', '#2a5f8f', '#0ea5a4', '#16a34a', '#f59e0b', '#0891b2', '#7c3aed', '#ef4444'],
                        borderRadius: 8,
                        maxBarThickness: 38
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(72,96,126,0.10)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
