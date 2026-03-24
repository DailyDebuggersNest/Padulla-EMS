<?php
require_once '../../config/db.php';
include_once '../../includes/header.php';

$stmt = $pdo->query(
    "SELECT
        p.*, 
        COUNT(DISTINCT c.course_id) AS total_courses,
        COUNT(c.curriculum_id) AS curriculum_entries
     FROM programs p
     LEFT JOIN curriculum c ON c.program_id = p.program_id
     GROUP BY p.program_id
     ORDER BY p.program_code"
);
$programs = $stmt->fetchAll();

$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_programs,
        COUNT(DISTINCT department) AS total_departments
     FROM programs"
);
$summary = $summaryStmt->fetch() ?: ['total_programs' => 0, 'total_departments' => 0];

$curriculumCount = (int) ($pdo->query("SELECT COUNT(*) FROM curriculum")->fetchColumn() ?: 0);

$departments = [];
foreach ($programs as $program) {
    $dept = trim((string) ($program['department'] ?? 'General'));
    if ($dept === '') {
        $dept = 'General';
    }
    $departments[$dept] = true;
}
$departmentList = array_keys($departments);
sort($departmentList);
?>

<style>
    .program-hub-hero {
        border-radius: 16px;
        border: 1px solid #d6e3f4;
        background: linear-gradient(105deg, #f7fbff, #ebf4ff 62%, #e8f8f6);
        box-shadow: 0 12px 24px rgba(30, 58, 95, 0.1);
        overflow: hidden;
        position: relative;
    }

    .program-hub-hero::after {
        content: '';
        position: absolute;
        right: -90px;
        top: -95px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle at center, rgba(30, 58, 95, 0.15), transparent 72%);
    }

    .program-hub-hero .hero-body {
        position: relative;
        z-index: 1;
        padding: 18px;
    }

    .mini-stat {
        border: 1px solid #d8e4f2;
        border-radius: 12px;
        background: #fff;
        padding: 10px 12px;
        height: 100%;
    }

    .mini-stat .label {
        font-size: 0.64rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #6b7d92;
        font-weight: 800;
    }

    .mini-stat .value {
        margin-top: 4px;
        font-family: 'Montserrat', sans-serif;
        color: #1e3a5f;
        font-size: 1.2rem;
        font-weight: 900;
        line-height: 1.1;
    }

    .program-toolbar {
        border: 1px solid #d8e4f2;
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }

    .dept-chip {
        border: 1px solid #d2deee;
        border-radius: 999px;
        background: #f4f8ff;
        color: #3f5673;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 5px 11px;
        margin-right: 6px;
        margin-bottom: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dept-chip.active,
    .dept-chip:hover {
        border-color: #1e3a5f;
        background: #1e3a5f;
        color: #fff;
    }

    .program-card {
        border: 1px solid #d9e4f2;
        border-radius: 14px;
        box-shadow: 0 9px 18px rgba(30, 58, 95, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }

    .program-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 28px rgba(30, 58, 95, 0.12);
    }

    .program-code-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        background: #eaf1fb;
        color: #1e3a5f;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.4px;
    }

    .program-name {
        font-size: 1rem;
        line-height: 1.35;
        font-weight: 800;
        color: #1e3a5f;
        margin: 10px 0 8px;
    }

    .program-meta {
        color: #65778e;
        font-size: 0.78rem;
        margin-bottom: 10px;
    }

    .meta-pair {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-size: 0.78rem;
        padding: 6px 0;
        border-bottom: 1px dashed #dce6f2;
    }

    .meta-pair:last-of-type {
        border-bottom: none;
    }
</style>

<div class="program-hub-hero mb-3">
    <div class="hero-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
        <div>
            <span class="page-hero-kicker"><i class="fas fa-book-reader"></i> Academic Catalog</span>
            <h2 class="page-hero-title">Programs and Curriculum Maps</h2>
            <p class="page-hero-text">Browse program offerings, compare curriculum sizes, and open detailed curriculum structures by term.</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6">
        <div class="mini-stat">
            <div class="label">Total Programs</div>
            <div class="value"><?= number_format((int) $summary['total_programs']) ?></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="mini-stat">
            <div class="label">Departments</div>
            <div class="value"><?= number_format((int) $summary['total_departments']) ?></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="mini-stat">
            <div class="label">Curriculum Entries</div>
            <div class="value"><?= number_format($curriculumCount) ?></div>
        </div>
    </div>
</div>

<div class="program-toolbar mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-lg-5">
            <input type="text" id="programSearch" class="form-control" placeholder="Search by code, name, or department...">
        </div>
        <div class="col-lg-7">
            <div class="d-flex flex-wrap" id="deptFilterWrap">
                <button type="button" class="dept-chip active" data-dept="all">All Departments</button>
                <?php foreach ($departmentList as $dept): ?>
                    <button type="button" class="dept-chip" data-dept="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3" id="programGrid">
    <?php foreach($programs as $prog): ?>
        <?php $dept = trim((string) ($prog['department'] ?? 'General')) ?: 'General'; ?>
        <div class="col-xl-4 col-md-6 program-col" data-dept="<?= htmlspecialchars($dept) ?>" data-search="<?= htmlspecialchars(strtolower($prog['program_code'] . ' ' . $prog['program_name'] . ' ' . $dept)) ?>">
            <div class="card program-card">
                <div class="card-body">
                    <span class="program-code-pill"><i class="fas fa-graduation-cap me-1"></i><?= htmlspecialchars($prog['program_code']) ?></span>
                    <div class="program-name"><?= htmlspecialchars($prog['program_name']) ?></div>
                    <div class="program-meta"><i class="fas fa-building me-1"></i><?= htmlspecialchars($dept) ?></div>

                    <div class="meta-pair">
                        <span class="text-muted">Curriculum Courses</span>
                        <strong><?= number_format((int) $prog['total_courses']) ?></strong>
                    </div>
                    <div class="meta-pair mb-2">
                        <span class="text-muted">Curriculum Entries</span>
                        <strong><?= number_format((int) $prog['curriculum_entries']) ?></strong>
                    </div>

                    <a href="view.php?id=<?= (int) $prog['program_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-sitemap me-1"></i> View Curriculum
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if(count($programs) == 0): ?>
        <div class="col-12"><div class="alert alert-info mb-0">No programs defined yet in the programs table.</div></div>
    <?php endif; ?>
</div>

<script>
    (function () {
        const searchInput = document.getElementById('programSearch');
        const chipWrap = document.getElementById('deptFilterWrap');
        const cards = Array.from(document.querySelectorAll('.program-col'));

        let activeDept = 'all';

        function applyFilters() {
            const query = (searchInput?.value || '').toLowerCase().trim();

            cards.forEach(function (card) {
                const dept = (card.getAttribute('data-dept') || '').toLowerCase();
                const searchText = (card.getAttribute('data-search') || '').toLowerCase();

                const matchDept = activeDept === 'all' || dept === activeDept;
                const matchSearch = query === '' || searchText.indexOf(query) !== -1;

                card.style.display = (matchDept && matchSearch) ? '' : 'none';
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        if (chipWrap) {
            chipWrap.addEventListener('click', function (event) {
                const btn = event.target.closest('.dept-chip');
                if (!btn) return;

                chipWrap.querySelectorAll('.dept-chip').forEach(function (chip) {
                    chip.classList.remove('active');
                });

                btn.classList.add('active');
                activeDept = (btn.getAttribute('data-dept') || 'all').toLowerCase();
                applyFilters();
            });
        }
    })();
</script>

<?php include_once '../../includes/footer.php'; ?>