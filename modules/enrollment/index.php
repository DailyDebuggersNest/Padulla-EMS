<?php
// modules/enrollment/index.php 
// Enrollment Dashboard - Quick lookup and Records redirect
require_once '../../config/db.php';
include_once '../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-user-plus text-primary"></i> Enrollment Process</h2>
        <p class="text-muted">Start a new enrollment by searching for a student or selecting from the masterlist.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 text-center">
        <div class="card shadow-sm mb-4">
            <div class="card-body py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4>Search & Enroll</h4>
                <p>Quick lookup by Student ID to start their enrollment.</p>
                <form action="step1.php" method="GET" class="mt-4 px-4">
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <input type="text" name="term_code" id="term_code" class="form-control" placeholder="Term Code (e.g. 251)" required title="251=2024-2025 1st Sem, 252=2nd Sem, 250=Summer" onkeyup="previewTerm(this.value)">
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="student_id" class="form-control" placeholder="Student ID (e.g. 2025-001)" required>
                        </div>
                    </div>
                    <div id="term_preview" class="text-start mb-3 text-primary fw-bold" style="min-height: 24px; font-size: 0.9em;"></div>
                    <button class="btn btn-primary w-100" type="submit">Start Enrollment</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-center">
        <div class="card shadow-sm mb-4">
            <div class="card-body py-5">
                <i class="fas fa-list fa-4x text-muted mb-3"></i>
                <h4>Browse Masterlist</h4>
                <p>View all students and filter by program or year level to enroll.</p>
                <div class="mt-4">
                    <a href="/EMS/modules/students/index.php" class="btn btn-secondary">Go to Student List <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewTerm(code) {
    const preview = document.getElementById('term_preview');
    if(code.length === 3) {
        let yrPrefix = parseInt(code.substring(0,2));
        let termSuffix = code.substring(2,3);
        
        if(isNaN(yrPrefix)) return;
        
        let endYear = 2000 + yrPrefix;
        let startYear = endYear - 1;
        let acadYear = startYear + '-' + endYear;
        
        let semester_id = '';
        if(termSuffix === '1') semester_id = '1st Sem';
        else if(termSuffix === '2') semester_id = '2nd Sem';
        else if(termSuffix === '0') semester_id = 'Summer';
        else {
            preview.innerHTML = '<span class="text-danger">Invalid Term Suffix</span>';
            return;
        }
        
        preview.innerHTML = `<i class="fas fa-check-circle"></i> SY ${acadYear}, ${semester_id}`;
    } else {
        preview.innerHTML = '';
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>