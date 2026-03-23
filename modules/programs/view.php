<?php
// modules/programs/view.php
// Curriculum View
require_once '../../config/db.php';
include_once '../../includes/header.php';

$program_id = $_GET['id'] ?? '';
if(!$program_id) die("<div class='alert alert-danger'>Program ID required.</div>");

// Get program details
$pStmt = $pdo->prepare("SELECT * FROM programs WHERE program_id = ?");
$pStmt->execute([$program_id]);
$program = $pStmt->fetch();

if(!$program) die("<div class='alert alert-danger'>Program not found.</div>");

// Fetch Curriculum organized by Year Level and Semester
$cStmt = $pdo->prepare("
    SELECT c.*, cr.year_level_id, cr.semester_id 
    FROM curriculum cr
    JOIN courses c ON cr.course_id = c.course_id
    WHERE cr.program_id = ?
    ORDER BY cr.year_level_id ASC, 
             cr.semester_id ASC, 
             c.course_code ASC
");
$cStmt->execute([$program_id]);
$raw_curriculum = $cStmt->fetchAll();

// Group data internally using an array
$curriculum = [];
foreach($raw_curriculum as $row) {
    $curriculum[$row['year_level_id']][$row['semester_id']][] = $row;
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-sitemap text-primary"></i> Curriculum Viewer</h2>
        <h5 class="text-muted"><?= htmlspecialchars($program['program_code'] . ' - ' . $program['program_name']) ?></h5>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php" class="btn btn-secondary mt-2"><i class="fas fa-arrow-left"></i> Back</a>
        <button class="btn btn-success mt-2"><i class="fas fa-plus"></i> Add Subject to Curriculum</button>
    </div>
</div>

<?php if(empty($curriculum)): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> No curriculum subjects assigned to this program yet.</div>
<?php else: ?>
    
    <div class="accordion" id="curriculumAccordion">
        <?php foreach($curriculum as $year => $semesters): ?>
            <?php foreach($semesters as $sem => $subjects): ?>
                <?php $acc_id = "collapse_" . $year . "_" . preg_replace('/[^A-Za-z0-9]/', '', $sem); ?>
                
                <div class="accordion-item mb-2 shadow-sm border-0">
                    <h2 class="accordion-header" id="heading_<?= $acc_id ?>">
                        <button class="accordion-button bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $acc_id ?>" aria-expanded="true" aria-controls="<?= $acc_id ?>">
                            Year <?= $year ?> - <?= $sem == 3 ? 'Summer' : $sem . ' Semester' ?>
                        </button>
                    </h2>
                    <div id="<?= $acc_id ?>" class="accordion-collapse collapse show" aria-labelledby="heading_<?= $acc_id ?>" data-bs-parent="#curriculumAccordion">
                        <div class="accordion-body p-0">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 15%">Course Code</th>
                                        <th style="width: 45%">Descriptive Title</th>
                                        <th style="width: 10%">Lec</th>
                                        <th style="width: 10%">Lab</th>
                                        <th style="width: 10%">Total</th>
                                        <th style="width: 10%">Pre-Req</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $total_units = 0;
                                        foreach($subjects as $sub): 
                                            $total_units += $sub['total_units'];
                                    ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($sub['course_code']) ?></td>
                                            <td><?= htmlspecialchars($sub['course_name']) ?></td>
                                            <td><?= htmlspecialchars($sub['units_lec']) ?></td>
                                            <td><?= htmlspecialchars($sub['units_lab']) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($sub['total_units']) ?></td>
                                            <td><?= htmlspecialchars($sub['prerequisite_course_id'] ?: 'None') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold">Total Units:</td>
                                        <td colspan="2" class="fw-bold"><?= number_format($total_units, 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>
