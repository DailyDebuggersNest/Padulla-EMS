<?php
// modules/enrollment/index.php
require_once '../../config/db.php';
include_once '../../includes/header.php';

$latestTermStmt = $pdo->query("SELECT academic_year, semester_id FROM enrollments ORDER BY academic_year DESC, semester_id DESC LIMIT 1");
$latestTerm = $latestTermStmt->fetch();
$activeTerm = $latestTerm['academic_year'] ?? '2025-2026';
$activeSem = isset($latestTerm['semester_id']) ? (int) $latestTerm['semester_id'] : 1;

$termSummaryStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'Enrolled' THEN 1 ELSE 0 END) AS enrolled_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count
     FROM enrollments
     WHERE academic_year = ? AND semester_id = ?"
);
$termSummaryStmt->execute([$activeTerm, $activeSem]);
$termSummary = $termSummaryStmt->fetch() ?: ['total_records' => 0, 'enrolled_count' => 0, 'pending_count' => 0];

$recentEnrollmentsStmt = $pdo->prepare(
    "SELECT e.student_id, e.enrollment_date, e.status, s.first_name, s.last_name, COALESCE(p.program_code, 'N/A') AS program_code
     FROM enrollments e
     JOIN students s ON s.student_id = e.student_id
     LEFT JOIN programs p ON p.program_id = COALESCE(e.program_id, s.program_id)
     WHERE e.academic_year = ? AND e.semester_id = ?
     ORDER BY e.enrollment_date DESC
     LIMIT 8"
);
$recentEnrollmentsStmt->execute([$activeTerm, $activeSem]);
$recentEnrollments = $recentEnrollmentsStmt->fetchAll();
?>

<style>
    .enroll-hub-hero {
        border-radius: 16px;
        border: 1px solid #d5e3f3;
        background: linear-gradient(110deg, #f7fbff, #eaf3ff 62%, #e6f8f5);
        box-shadow: 0 12px 24px rgba(30, 58, 95, 0.1);
        position: relative;
        overflow: hidden;
    }

    .enroll-hub-hero::after {
        content: '';
        position: absolute;
        width: 240px;
        height: 240px;
        right: -90px;
        top: -90px;
        border-radius: 50%;
        background: radial-gradient(circle at center, rgba(30, 58, 95, 0.18), transparent 72%);
    }

    .enroll-hub-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 18px;
    }

    .term-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #c8d9ee;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #385272;
    }

    .hub-stat {
        border: 1px solid #d8e4f3;
        border-radius: 12px;
        background: #fff;
        padding: 11px 12px;
        height: 100%;
    }

    .hub-stat .label {
        font-size: 0.64rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #66798f;
        font-weight: 800;
    }

    .hub-stat .value {
        margin-top: 4px;
        font-family: 'Montserrat', sans-serif;
        color: #1e3a5f;
        font-size: 1.22rem;
        line-height: 1.1;
        font-weight: 900;
    }

    .enroll-search-card {
        border: 1px solid #d8e4f3;
        border-radius: 14px;
        background: #fff;
    }

    .enroll-search-card .card-header,
    .enroll-shortcut .card-header,
    .enroll-recent .card-header {
        background: linear-gradient(120deg, #f7fbff, #edf5ff);
    }

    .enroll-search-card .card-body,
    .enroll-shortcut .card-body {
        padding: 14px;
    }

    .term-preview {
        min-height: 24px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .hint-list {
        margin: 8px 0 0;
        padding-left: 1rem;
        color: #6b7d91;
        font-size: 0.78rem;
    }

    .shortcut-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 0.95rem;
        font-weight: 800;
        color: #1e3a5f;
        margin-bottom: 4px;
    }

    .shortcut-text {
        font-size: 0.8rem;
        color: #65778d;
        margin-bottom: 10px;
    }
</style>

<div class="enroll-hub-hero mb-3">
    <div class="hero-body">
        <span class="page-hero-kicker"><i class="fas fa-user-plus"></i> Enrollment Hub</span>
        <h2 class="page-hero-title">Enrollment Management</h2>
        <p class="page-hero-text">Process new enrollments quickly, move through student lookup flows, and monitor the latest term activity from one module.</p>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <span class="term-chip"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($activeTerm) ?></span>
            <span class="term-chip"><i class="fas fa-book-open"></i> <?= $activeSem === 3 ? 'Summer Term' : $activeSem . ' Semester' ?></span>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6">
        <div class="hub-stat">
            <div class="label">Total Term Records</div>
            <div class="value"><?= number_format((int) $termSummary['total_records']) ?></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="hub-stat">
            <div class="label">Enrolled</div>
            <div class="value"><?= number_format((int) $termSummary['enrolled_count']) ?></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="hub-stat">
            <div class="label">Pending</div>
            <div class="value"><?= number_format((int) $termSummary['pending_count']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="card enroll-search-card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-search me-2 text-primary"></i>Search and Start Enrollment</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2" style="font-size:0.82rem;">Enter the term code and student ID to start the step-by-step enrollment process.</p>
                <form action="step1.php" method="GET">
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <input type="text" name="term_code" id="term_code" class="form-control" placeholder="Term Code (e.g. 251)" required title="251=2024-2025 1st Sem, 252=2nd Sem, 250=Summer" onkeyup="previewTerm(this.value)">
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="student_id" class="form-control" placeholder="Student ID (e.g. 2025-001)" required>
                        </div>
                    </div>
                    <div id="term_preview" class="term-preview text-primary"></div>
                    <button class="btn btn-primary w-100 mt-2" type="submit"><i class="fas fa-play-circle me-1"></i> Start Enrollment</button>
                </form>

                <ul class="hint-list">
                    <li>Use 3-digit term code format: first 2 digits for end year, last digit for semester.</li>
                    <li>Semester suffix: <strong>1</strong> = 1st, <strong>2</strong> = 2nd, <strong>0</strong> = Summer.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card enroll-shortcut mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Student Masterlist</h6>
            </div>
            <div class="card-body">
                <div class="shortcut-title">Find Existing Student Profiles</div>
                <div class="shortcut-text">Open the student list to search, verify, and begin enrollment from profile actions.</div>
                <a href="<?= BASE_PATH ?>modules/students/index.php" class="btn btn-outline-primary w-100">Go to Student List <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
        <div class="card enroll-shortcut">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-folder-open me-2 text-primary"></i>Enrollment Records</h6>
            </div>
            <div class="card-body">
                <div class="shortcut-title">Review Posted Enrollments</div>
                <div class="shortcut-text">Check completed records, filter by term, and print COR documents when needed.</div>
                <a href="<?= BASE_PATH ?>modules/enrollment/records.php?term=<?= urlencode($activeTerm) ?>&sem=<?= $activeSem ?>" class="btn btn-outline-secondary w-100">Open Records <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="card enroll-recent">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-clock me-2 text-primary"></i>Recent Enrollment Activity (Current Term)</h6>
        <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($activeTerm) ?> / <?= $activeSem === 3 ? 'Summer' : $activeSem . ' Sem' ?></span>
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
                        <?php foreach ($recentEnrollments as $item): ?>
                            <?php
                            $status = $item['status'] ?? 'Pending';
                            $badgeClass = $status === 'Enrolled' ? 'bg-success' : ($status === 'Cancelled' ? 'bg-danger' : 'bg-warning text-dark');
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($item['student_id']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars(($item['last_name'] ?? '') . ', ' . ($item['first_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($item['program_code']) ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td><?= date('M d, Y h:i A', strtotime($item['enrollment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No enrollment activity posted for the selected term yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function previewTerm(code) {
    const preview = document.getElementById('term_preview');
    const trimmed = (code || '').trim();

    if (trimmed.length === 3) {
        const yrPrefix = parseInt(trimmed.substring(0, 2), 10);
        const termSuffix = trimmed.substring(2, 3);

        if (Number.isNaN(yrPrefix)) {
            preview.innerHTML = '<span class="text-danger"><i class="fas fa-circle-xmark"></i> Invalid term code.</span>';
            return;
        }

        const endYear = 2000 + yrPrefix;
        const startYear = endYear - 1;
        const acadYear = startYear + '-' + endYear;

        let semester = '';
        if (termSuffix === '1') semester = '1st Semester';
        else if (termSuffix === '2') semester = '2nd Semester';
        else if (termSuffix === '0') semester = 'Summer';
        else {
            preview.innerHTML = '<span class="text-danger"><i class="fas fa-circle-xmark"></i> Invalid term suffix. Use 1, 2, or 0.</span>';
            return;
        }

        preview.innerHTML = '<i class="fas fa-circle-check"></i> Interpreted as <strong>' + acadYear + '</strong> (' + semester + ')';
    } else {
        preview.innerHTML = '';
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>
