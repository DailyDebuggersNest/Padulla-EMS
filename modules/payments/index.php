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

?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-money-bill-alt text-primary"></i> Payment Tracking</h2>
        <p class="text-muted">Manage student payments, view balances, and issue Official Receipts.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
        <form method="GET" class="row">
            <div class="col-md-5 mb-2">
                <input type="text" name="search" class="form-control" placeholder="Search Student ID or Name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3 mb-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search Student</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Term</th>
                    <th>Assessed Amount</th>
                    <th>Total Paid</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($enrollments) > 0): ?>
                    <?php foreach($enrollments as $row): ?>
                        <?php 
                            $balance = $row['assessed_amount'] - $row['total_paid']; 
                            $badgeClass = $balance <= 0 ? 'bg-success' : 'bg-warning text-dark';
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['student_id']) ?></span></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                            <td><?= htmlspecialchars($row['academic_year'] . ' - ' . ($row['semester_id'] == 3 ? 'Summer' : $row['semester_id'] . ' Sem')) ?></td>
                            <td>&#8369;<?= number_format($row['assessed_amount'], 2) ?></td>
                            <td class="text-success">&#8369;<?= number_format($row['total_paid'], 2) ?></td>
                            <td><span class="badge <?= $badgeClass ?>">&#8369;<?= number_format(max(0, $balance), 2) ?></span></td>
                            <td>
                                <a href="pay.php?enrollment_id=<?= $row['enrollment_id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-coins"></i> Manage Payments
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4">No enrolled students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
