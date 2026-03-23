<?php
// modules/payments/print_receipt.php
require_once '../../config/db.php';

$payment_id = $_GET['id'] ?? '';
if(!$payment_id) die("Payment ID required.");

// Fetch payment, enrollment and student info
$stmt = $pdo->prepare("
    SELECT p.*, e.academic_year, e.semester_id, s.first_name, s.last_name, s.student_id as student_no, pr.program_name 
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.enrollment_id
    JOIN students s ON p.student_id = s.student_id
    LEFT JOIN programs pr ON s.program_id = pr.program_id
    WHERE p.payment_id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if(!$payment) die("Payment record not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt - <?= htmlspecialchars($payment['or_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .receipt-container { background: #fff; border: 1px dashed #000; padding: 40px; margin: 30px auto; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .school-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .school-header h2 { margin: 0; font-weight: bold; font-family: serif; font-size: 24px;}
        .receipt-title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px; letter-spacing: 2px; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table th { width: 30%; text-align: left; padding: 5px 0; font-weight: 600; color: #555; }
        .info-table td { padding: 5px 0; border-bottom: 1px solid #eee; }
        .amount-box { background: #f0f8ff; border: 1px solid #b8daff; padding: 15px; text-align: center; margin: 30px 0; border-radius: 8px; }
        .amount-box h3 { margin: 0; color: #0056b3; font-weight: bold; font-size: 28px; }
        .amount-box p { margin: 5px 0 0 0; color: #666; font-size: 14px; text-transform: uppercase;}
        .signature-box { margin-top: 50px; text-align: right; }
        .signature-line { border-top: 1px solid #000; width: 200px; display: inline-block; padding-top: 5px; text-align: center; font-weight: bold;}
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; margin: 0;}
            .receipt-container { border: none; box-shadow: none; margin: 0 auto; padding: 20px;}
        }
    </style>
</head>
<body>
    <div class="text-center mt-4 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="receipt-container">
        <div class="school-header">
            <h2>UA ACADEMY</h2>
            <p class="mb-0 text-muted" style="font-size: 14px;">Cashier's Office</p>
        </div>

        <div class="receipt-title">Official Receipt</div>

        <table class="info-table">
            <tr><th>O.R. Number:</th><td><strong class="text-danger"><?= htmlspecialchars($payment['or_number']) ?></strong></td></tr>
            <tr><th>Date:</th><td><?= date('F d, Y h:i A', strtotime($payment['payment_date'])) ?></td></tr>
            <tr><th>Student ID:</th><td><?= htmlspecialchars($payment['student_no']) ?></td></tr>
            <tr><th>Received From:</th><td><strong><?= htmlspecialchars(strtoupper($payment['last_name'] . ', ' . $payment['first_name'])) ?></strong></td></tr>
            <tr><th>Program:</th><td><?= htmlspecialchars($payment['program_name'] ?? 'N/A') ?></td></tr>
            <tr><th>Term Applied:</th><td><?= $payment['semester_id'] == 3 ? 'Summer, A.Y. ' : htmlspecialchars($payment['semester_id'] . ' Semester, A.Y. ') ?><?= htmlspecialchars($payment['academic_year']) ?></td></tr>
            <tr><th>Particulars:</th><td><?= htmlspecialchars($payment['remarks'] ?: 'Tuition Fee Payment') ?></td></tr>
        </table>

        <div class="amount-box">
            <h3>&#8369; <?= number_format($payment['amount'], 2) ?></h3>
            <p>Amount Paid</p>
        </div>

        <div class="signature-box">
            <div class="signature-line">Authorized Cashier</div>
        </div>
    </div>
</body>
</html>
