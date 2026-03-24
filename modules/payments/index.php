<?php
// modules/payments/index.php
// Student payment search and assessment overview
require_once '../../config/db.php';
include_once '../../includes/header.php';

$search = $_GET['search'] ?? '';

// Basic query to fetch enrollments and their payment status
$sql = "
    SELECT e.*, s.first_name, s.last_name, p.program_code,
           COALESCE((SELECT SUM(amount) FROM payments WHERE enrollment_id = e.enrollment_id), 0) as total_paid
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    LEFT JOIN programs p ON s.program_id = p.program_id
    WHERE e.status = 'Enrolled'
";

$params = [];
if ($search) {
    $sql .= " AND (s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY e.enrollment_date DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

$summary = [
    'assessed' => 0,
    'paid' => 0,
    'balance' => 0,
    'settled' => 0,
    'pending' => 0,
];

foreach ($enrollments as $row) {
    $assessed = (float) $row['assessed_amount'];
    $paid = (float) $row['total_paid'];
    $balance = max(0, $assessed - $paid);

    $summary['assessed'] += $assessed;
    $summary['paid'] += $paid;
    $summary['balance'] += $balance;

    if ($balance <= 0.01) {
        $summary['settled']++;
    } else {
        $summary['pending']++;
    }
}

$collectionRate = $summary['assessed'] > 0 ? ($summary['paid'] / $summary['assessed']) * 100 : 0;

?>

<div class="page-hero mb-4">
    <div class="page-hero-body d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3">
        <div>
            <span class="page-hero-kicker"><i class="fas fa-money-check-alt"></i> Finance Desk</span>
            <h2 class="page-hero-title">Collections Dashboard</h2>
            <p class="page-hero-text">Track assessed tuition, monitor collection velocity, and jump into posting in one view.</p>
        </div>
        <div class="text-xl-end">
            <div class="small text-muted mb-1">Collection Efficiency</div>
            <div class="d-flex align-items-center gap-2 justify-content-xl-end">
                <div class="fw-bold fs-4 text-success"><?= number_format($collectionRate, 1) ?>%</div>
                <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                    <?= number_format($summary['paid'], 2) ?> collected
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Assessed</div>
                <div class="h4 mb-0">&#8369;<?= number_format($summary['assessed'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Collected</div>
                <div class="h4 mb-0 text-success">&#8369;<?= number_format($summary['paid'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Open Balance</div>
                <div class="h4 mb-0 text-danger">&#8369;<?= number_format($summary['balance'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-uppercase text-muted mb-1">Accounts</div>
                    <div class="h4 mb-0"><?= number_format(count($enrollments)) ?></div>
                </div>
                <div class="text-end">
                    <div class="small text-success"><i class="fas fa-check-circle"></i> <?= number_format($summary['settled']) ?> settled</div>
                    <div class="small text-warning-emphasis"><i class="fas fa-clock"></i> <?= number_format($summary['pending']) ?> pending</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small text-uppercase text-muted mb-1">Find Student</label>
                <input type="text" name="search" class="form-control" placeholder="Search by Student ID, First Name, or Last Name" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Apply Search</button>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="index.php" class="btn btn-outline-secondary w-100"><i class="fas fa-undo me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
        <div>
            <h5 class="mb-0">Student Accounts</h5>
            <small class="text-muted">Select an account to post payments or review transaction history.</small>
        </div>
        <span class="badge rounded-pill text-bg-dark">Showing <?= number_format(count($enrollments)) ?> records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Term</th>
                    <th class="text-end">Assessed Amount</th>
                    <th class="text-end">Total Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($enrollments) > 0): ?>
                    <?php foreach($enrollments as $row): ?>
                        <?php 
                            $assessed = (float) $row['assessed_amount'];
                            $paid = (float) $row['total_paid'];
                            $balance = max(0, $assessed - $paid);
                            $isSettled = $balance <= 0.01;
                            $progress = $assessed > 0 ? min(100, ($paid / $assessed) * 100) : 0;
                        ?>
                        <tr>
                            <td><span class="badge rounded-pill text-bg-secondary"><?= htmlspecialchars($row['student_id']) ?></span></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($row['program_code'] ?: 'No program') ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['academic_year'] . ' - ' . ($row['semester_id'] == 3 ? 'Summer' : $row['semester_id'] . ' Sem')) ?></td>
                            <td class="text-end">&#8369;<?= number_format($assessed, 2) ?></td>
                            <td class="text-end text-success">&#8369;<?= number_format($paid, 2) ?></td>
                            <td class="text-end fw-semibold <?= $isSettled ? 'text-success' : 'text-danger' ?>">&#8369;<?= number_format($balance, 2) ?></td>
                            <td class="text-center" style="min-width: 180px;">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?= $isSettled ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= number_format($progress, 2) ?>%" aria-valuenow="<?= number_format($progress, 2) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="small mt-1 <?= $isSettled ? 'text-success' : 'text-muted' ?>">
                                    <?= $isSettled ? 'Settled' : number_format($progress, 1) . '% collected' ?>
                                </div>
                            </td>
                            <td>
                                <a href="pay.php?enrollment_id=<?= $row['enrollment_id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-wallet me-1"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No enrolled students matched your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
