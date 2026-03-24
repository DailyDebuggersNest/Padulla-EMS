<?php
// modules/payments/print_ledger.php
require_once '../../config/db.php';

$enrollment_id = $_GET['enrollment_id'] ?? '';
if(!$enrollment_id) die("Enrollment ID required.");

// Fetch enrollment and student info
$stmt = $pdo->prepare("
    SELECT e.*, s.first_name, s.last_name, p.program_code, p.program_name 
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    LEFT JOIN programs p ON s.program_id = p.program_id
    WHERE e.enrollment_id = ?
");
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch();

if(!$enrollment) die("Enrollment record not found.");

// Fetch All payments
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE enrollment_id = ? ORDER BY payment_date ASC");
$payStmt->execute([$enrollment_id]);
$payments = $payStmt->fetchAll();

$total_paid = 0;
foreach($payments as $p) {
    $total_paid += $p['amount'];
}
$balance = $enrollment['assessed_amount'] - $total_paid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Ledger - <?= htmlspecialchars($enrollment['student_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; font-family: 'Inter', 'Segoe UI', sans-serif; color: #1f2a37; }
        .ledger-container { padding: 34px; margin: 0 auto; max-width: 980px; background: #fff; border: 1px solid #d6e0ee; border-radius: 16px; box-shadow: 0 12px 30px rgba(30,58,95,0.12); }
        .school-header { text-align: center; border-bottom: 2px solid #1e3a5f; padding-bottom: 18px; margin-bottom: 24px; }
        .school-header h2 { margin: 0; font-weight: 900; letter-spacing: 0.5px; color: #1e3a5f; }
        .info-table th { width: 150px; text-align: left; color: #5f6f84; }
        .table-ledger th { background-color: #eff5fd !important; border-bottom: 1px solid #d3e0f2; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.6px; }
        .table-ledger td, .table-ledger th { border-color: #dfe8f4; }
        .summary-box { border: 1px solid #d2e0f1; padding: 15px; margin-top: 30px; border-radius: 12px; background: #f7fbff; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .ledger-container { padding: 0; border: none; border-radius: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="text-center mt-4 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Ledger</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="ledger-container">
        <div class="school-header">
            <h2>UA ACADEMY</h2>
            <p class="mb-0">Accounting Office</p>
            <h4 class="mt-3 text-uppercase fw-bold">Student Payment Ledger</h4>
        </div>

        <div class="row mb-4">
            <div class="col-sm-7">
                <table class="info-table">
                    <tr><th>Student No:</th><td><strong><?= htmlspecialchars($enrollment['student_id']) ?></strong></td></tr>
                    <tr><th>Name:</th><td><?= htmlspecialchars(strtoupper($enrollment['last_name'] . ', ' . $enrollment['first_name'])) ?></td></tr>
                    <tr><th>Program:</th><td><?= htmlspecialchars($enrollment['program_name'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="col-sm-5 text-end">
                <table class="info-table ms-auto">
                    <tr><th>Academic Year:</th><td><?= htmlspecialchars($enrollment['academic_year']) ?></td></tr>
                    <tr><th>Semester:</th><td><?= htmlspecialchars($enrollment['semester_id']) ?></td></tr>
                    <tr><th>Date Printed:</th><td><?= date('M d, Y') ?></td></tr>
                </table>
            </div>
        </div>

        <table class="table table-bordered table-ledger">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference / O.R.</th>
                    <th>Particulars</th>
                    <th class="text-end">Charge (Debit)</th>
                    <th class="text-end">Payment (Credit)</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <!-- Initial Assessment -->
                <tr>
                    <td><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></td>
                    <td>Assessment</td>
                    <td>Tuition and Miscellaneous Fees</td>
                    <td class="text-end">&#8369;<?= number_format($enrollment['assessed_amount'], 2) ?></td>
                    <td class="text-end">-</td>
                    <td class="text-end fw-bold">&#8369;<?= number_format($enrollment['assessed_amount'], 2) ?></td>
                </tr>
                
                <!-- Payments -->
                <?php 
                $running_balance = $enrollment['assessed_amount'];
                foreach($payments as $p): 
                    $running_balance -= $p['amount'];
                ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                        <td><?= htmlspecialchars($p['or_number']) ?></td>
                        <td><?= htmlspecialchars($p['remarks'] ?: 'Payment') ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end text-success">&#8369;<?= number_format($p['amount'], 2) ?></td>
                        <td class="text-end fw-bold">&#8369;<?= number_format($running_balance, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row mt-4">
            <div class="col-md-6 offset-md-6">
                <div class="summary-box bg-light">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Assessment:</span>
                        <strong>&#8369;<?= number_format($enrollment['assessed_amount'], 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Payments:</span>
                        <strong class="text-success">&#8369;<?= number_format($total_paid, 2) ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5" style="color:#c12b38;">
                        <span><strong>Current Balance:</strong></span>
                        <strong>&#8369;<?= number_format($balance, 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 pt-5 text-end">
            <div class="d-inline-block text-center" style="width: 250px;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">
                    <strong>Accountant / Cashier</strong><br>
                    <small>Authorized Signature</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>