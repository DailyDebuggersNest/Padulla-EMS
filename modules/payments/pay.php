<?php
// modules/payments/pay.php
// Individual payment page showing history and accepting new payment
require_once '../../config/db.php';
include_once '../../includes/header.php';

$enrollment_id = $_GET['enrollment_id'] ?? '';

if (!$enrollment_id) {
    die('Invalid enrollment ID.');
}

// Fetch Enrollment and Student Info
$stmt = $pdo->prepare("
    SELECT e.*, s.first_name, s.last_name, p.program_code 
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    LEFT JOIN programs p ON s.program_id = p.program_id
    WHERE e.enrollment_id = ?
");
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) die('Enrollment not found.');

// Fetch existing payments
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE enrollment_id = ? ORDER BY payment_date DESC");
$payStmt->execute([$enrollment_id]);
$payments = $payStmt->fetchAll();

$total_paid = 0;
foreach($payments as $p) $total_paid += $p['amount'];

$balance = $enrollment['assessed_amount'] - $total_paid;
$safeBalance = max(0, (float)$balance);
$completionRate = ((float)$enrollment['assessed_amount'] > 0)
    ? min(100, ($total_paid / (float)$enrollment['assessed_amount']) * 100)
    : 0;

// Process new payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    $amount = (float)$_POST['amount'];
    $or_number = $_POST['or_number'];
    $remarks = $_POST['remarks'] ?? '';

    if ($amount > 0 && $amount <= $balance) {
        $ins = $pdo->prepare("INSERT INTO payments (student_id, enrollment_id, or_number, amount, remarks) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$enrollment['student_id'], $enrollment_id, $or_number, $amount, $remarks]);
        
        header("Location: pay.php?enrollment_id=$enrollment_id&success=1");
        exit;
    } else {
        $error = "Invalid amount. Ensure amount is greater than 0 and does not exceed balance.";
    }
}
?>

<div class="page-hero mb-4">
    <div class="page-hero-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
        <div>
            <span class="page-hero-kicker"><i class="fas fa-wallet"></i> Collections Workspace</span>
            <h2 class="page-hero-title">Student Payment Ledger</h2>
            <p class="page-hero-text">Post collections, monitor settlement progress, and print official financial documents.</p>
        </div>
        <div class="d-flex flex-column align-items-lg-end gap-2">
            <span class="badge rounded-pill text-bg-dark px-3 py-2"><?= number_format($completionRate, 1) ?>% settled</span>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Assessed</div>
                <div class="h4 mb-0">&#8369;<?= number_format((float)$enrollment['assessed_amount'], 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Paid</div>
                <div class="h4 mb-0 text-success">&#8369;<?= number_format((float)$total_paid, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Current Balance</div>
                <div class="h4 mb-0 <?= $safeBalance > 0 ? 'text-danger' : 'text-success' ?>">&#8369;<?= number_format($safeBalance, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Transactions</div>
                <div class="h4 mb-1"><?= number_format(count($payments)) ?></div>
                <div class="small text-muted">OR entries posted</div>
            </div>
        </div>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> Payment successfully recorded.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-1">
                <h5 class="mb-0"><i class="fas fa-id-card me-1"></i> Account Snapshot</h5>
            </div>
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-2">Student Information</div>
                <div class="mb-2"><span class="text-muted">ID:</span> <strong><?= htmlspecialchars($enrollment['student_id']) ?></strong></div>
                <div class="mb-2"><span class="text-muted">Name:</span> <strong><?= htmlspecialchars($enrollment['last_name'] . ', ' . $enrollment['first_name']) ?></strong></div>
                <div class="mb-2"><span class="text-muted">Program:</span> <?= htmlspecialchars($enrollment['program_code'] ?: 'N/A') ?></div>
                <div class="mb-3"><span class="text-muted">Term:</span> <?= htmlspecialchars($enrollment['academic_year'] . ' | ' . ((int)$enrollment['semester_id'] === 3 ? 'Summer' : $enrollment['semester_id'] . ' Semester')) ?></div>

                <div class="small text-uppercase text-muted mb-2">Collection Progress</div>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar <?= $safeBalance > 0 ? 'bg-primary' : 'bg-success' ?>" role="progressbar" style="width: <?= number_format($completionRate, 2) ?>%" aria-valuenow="<?= number_format($completionRate, 2) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted mb-3"><?= number_format($completionRate, 1) ?>% of assessment collected</div>

                <div class="d-flex justify-content-between mb-1">
                    <span>Assessed</span>
                    <strong>&#8369;<?= number_format((float)$enrollment['assessed_amount'], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Paid</span>
                    <strong class="text-success">&#8369;<?= number_format((float)$total_paid, 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-2 p-2 rounded border bg-light">
                    <span class="fw-semibold">Balance</span>
                    <strong class="<?= $safeBalance > 0 ? 'text-danger' : 'text-success' ?>">&#8369;<?= number_format($safeBalance, 2) ?></strong>
                </div>
            </div>
        </div>

        <?php if ($safeBalance > 0): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 pt-3 pb-1">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-1"></i> Post New Payment</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">O.R. Number <span class="text-danger">*</span></label>
                            <input type="text" name="or_number" class="form-control" placeholder="e.g. OR-100234" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (&#8369;) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" max="<?= $safeBalance ?>" name="amount" class="form-control" required>
                            <div class="form-text">Maximum payable now: &#8369;<?= number_format($safeBalance, 2) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Tuition partial, cash basis, installment note, etc.">
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-1"></i> Submit Payment</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success text-center mb-0">
                <h5 class="mb-1"><i class="fas fa-star me-1"></i> Fully Paid</h5>
                <p class="mb-0">This enrollment term is fully settled.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
                <a href="print_ledger.php?enrollment_id=<?= $enrollment_id ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print me-1"></i> Print Ledger</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>O.R. Number</th>
                            <th class="text-end">Amount</th>
                            <th>Remarks</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($payments) > 0): ?>
                            <?php foreach($payments as $p): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                                    <td><span class="badge rounded-pill text-bg-secondary"><?= htmlspecialchars($p['or_number']) ?></span></td>
                                    <td class="text-end text-success fw-semibold">&#8369;<?= number_format((float)$p['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($p['remarks'] ?: 'Payment posted') ?></td>
                                    <td class="text-center">
                                        <a href="print_receipt.php?id=<?= $p['payment_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print Receipt">
                                            <i class="fas fa-receipt me-1"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>


