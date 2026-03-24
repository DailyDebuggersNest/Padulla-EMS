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

$semesterLabel = ((int) $payment['semester_id'] === 3)
    ? 'Summer'
    : ((int) $payment['semester_id'] . ' Semester');

$particulars = trim((string) ($payment['remarks'] ?? ''));
if ($particulars === '') {
    $particulars = 'Tuition Fee Payment';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt - <?= htmlspecialchars($payment['or_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --paper: #ffffff;
            --ink: #182132;
            --muted: #5f6b7a;
            --line: #d7deea;
            --brand: #0f5e9c;
            --brand-soft: #eaf5ff;
            --accent: #008062;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 30px 16px;
            color: var(--ink);
            background: radial-gradient(circle at top left, #f3f9ff 0%, #eef2f8 45%, #e7edf5 100%);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }
        .receipt-shell {
            max-width: 760px;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(17, 38, 66, 0.14);
        }
        .receipt-head {
            padding: 24px 28px 18px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(130deg, #f8fcff 0%, #eef6ff 60%, #edf3fb 100%);
        }
        .head-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .brand-kicker {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--muted);
            margin-bottom: 5px;
            font-weight: 700;
        }
        .brand-title {
            margin: 0;
            font-size: 27px;
            letter-spacing: 0.4px;
            font-weight: 800;
            color: #123a60;
        }
        .brand-sub {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .receipt-badge {
            border: 1px solid #cfe2f6;
            background: #fff;
            color: #104671;
            border-radius: 12px;
            padding: 9px 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            text-align: center;
            min-width: 190px;
        }
        .receipt-body {
            padding: 22px 28px 26px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .meta-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            background: #fcfdff;
        }
        .meta-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--muted);
            margin-bottom: 3px;
            font-weight: 700;
        }
        .meta-value {
            font-size: 15px;
            font-weight: 700;
            color: #13253a;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .detail-table th,
        .detail-table td {
            border-bottom: 1px solid #e4ebf5;
            padding: 9px 0;
            vertical-align: top;
        }
        .detail-table th {
            width: 170px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--muted);
            font-weight: 700;
        }
        .detail-table td {
            font-size: 14px;
            color: #182132;
            font-weight: 600;
        }
        .amount-band {
            margin: 20px 0 12px;
            border-radius: 12px;
            border: 1px solid #cae8dc;
            background: linear-gradient(130deg, #f4fffb 0%, #ecf9f4 100%);
            padding: 14px;
            text-align: center;
        }
        .amount-band .label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #397161;
            margin-bottom: 3px;
            font-weight: 700;
        }
        .amount-band .value {
            font-size: 33px;
            letter-spacing: 0.5px;
            color: var(--accent);
            font-weight: 900;
            line-height: 1;
        }
        .receipt-foot {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            align-items: end;
        }
        .note-box {
            border: 1px dashed #cfd8e6;
            border-radius: 10px;
            padding: 10px;
            font-size: 12px;
            color: #57657a;
            min-height: 72px;
        }
        .sign-box {
            text-align: right;
        }
        .sign-line {
            display: inline-block;
            width: 230px;
            border-bottom: 1px solid #243143;
            height: 32px;
        }
        .sign-label {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #4a586d;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }
        .action-bar {
            margin-bottom: 12px;
            text-align: center;
        }
        .action-bar .btn {
            min-width: 140px;
        }
        @media (max-width: 640px) {
            .head-grid,
            .meta-grid,
            .receipt-foot {
                grid-template-columns: 1fr;
            }
            .receipt-badge,
            .sign-box {
                text-align: left;
            }
            .detail-table th {
                width: 130px;
            }
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
            .receipt-shell {
                border: none;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            .receipt-head { padding-top: 8px; }
        }
    </style>
</head>
<body>
    <div class="action-bar no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="receipt-shell">
        <div class="receipt-head">
            <div class="head-grid">
                <div>
                    <div class="brand-kicker">Enrollment Management System</div>
                    <h1 class="brand-title">UA ACADEMY</h1>
                    <p class="brand-sub">Cashier Services | Official Collection Document</p>
                </div>
                <div class="receipt-badge">
                    Official Receipt<br>
                    No. <?= htmlspecialchars($payment['or_number']) ?>
                </div>
            </div>
        </div>

        <div class="receipt-body">
            <div class="meta-grid">
                <div class="meta-card">
                    <span class="meta-label">Posting Date</span>
                    <div class="meta-value"><?= date('F d, Y h:i A', strtotime($payment['payment_date'])) ?></div>
                </div>
                <div class="meta-card">
                    <span class="meta-label">Term Applied</span>
                    <div class="meta-value"><?= htmlspecialchars($semesterLabel . ', A.Y. ' . $payment['academic_year']) ?></div>
                </div>
            </div>

            <table class="detail-table">
                <tr><th>Student ID</th><td><?= htmlspecialchars($payment['student_no']) ?></td></tr>
                <tr><th>Received From</th><td><?= htmlspecialchars(strtoupper($payment['last_name'] . ', ' . $payment['first_name'])) ?></td></tr>
                <tr><th>Program</th><td><?= htmlspecialchars($payment['program_name'] ?? 'N/A') ?></td></tr>
                <tr><th>Particulars</th><td><?= htmlspecialchars($particulars) ?></td></tr>
            </table>

            <div class="amount-band">
                <span class="label">Amount Paid</span>
                <div class="value">&#8369; <?= number_format((float) $payment['amount'], 2) ?></div>
            </div>

            <div class="receipt-foot">
                <div class="note-box">
                    This document certifies that the stated amount has been officially received and posted to the specified enrollment account.
                </div>
                <div class="sign-box">
                    <span class="sign-line"></span>
                    <span class="sign-label">Authorized Cashier</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
