<?php
require_once "../../config/db.php";
include_once "../../includes/header.php";

$student_id = $_GET["student_id"] ?? "";

if (!$student_id) {
    echo "<div class=\"alert alert-danger mx-4 mt-4\">Student ID is required.</div>";
    include_once "../../includes/footer.php";
    exit;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_student") {
    $first_name = $_POST["first_name"] ?? "";
    $last_name = $_POST["last_name"] ?? "";
    $gender = $_POST["gender"] ?? null;
    $birthdate = !empty($_POST["birthdate"]) ? $_POST["birthdate"] : null;
    $contact_number = $_POST["contact_number"] ?? null;
    $email = $_POST["email"] ?? null;

    try {
        $stmt = $pdo->prepare("UPDATE students SET first_name=?, last_name=?, gender=?, birthdate=?, contact_number=?, email=? WHERE student_id=?");
        $stmt->execute([$first_name, $last_name, $gender, $birthdate, $contact_number, $email, $student_id]);
        echo "<div class=\"alert alert-success alert-dismissible fade show mx-4 mt-4\"><i class=\"fas fa-check-circle\"></i> Student updated successfully! <a href=\"view.php?student_id=".urlencode($student_id)."\" class=\"alert-link\">View Profile</a><button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button></div>";
    } catch (PDOException $e) {
        echo "<div class=\"alert alert-danger mx-4 mt-4\">Error updating student: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch Student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class=\"alert alert-danger mx-4 mt-4\">Student not found.</div>";
    include_once "../../includes/footer.php";
    exit;
}

// Fetch Programs for Dropdown
$programs = $pdo->query("SELECT * FROM programs ORDER BY program_code")->fetchAll();

?>
<div class="page-hero mb-4">
    <div class="page-hero-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
        <div>
            <span class="page-hero-kicker"><i class="fas fa-user-edit"></i> Student Update</span>
            <h2 class="page-hero-title">Edit Student Profile</h2>
            <p class="page-hero-text">Update core student information for <?= htmlspecialchars($student["student_id"]) ?>.</p>
        </div>
        <div class="action-toolbar">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Masterlist</a>
            <a href="view.php?student_id=<?= urlencode($student_id) ?>" class="btn btn-primary"><i class="fas fa-eye me-1"></i> View Profile</a>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header panel-header-strong">
        <h5 class="mb-0"><i class="fas fa-id-card"></i> Student Information</h5>
    </div>
    <div class="card-body bg-light">
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_student">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Student ID</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($student["student_id"]) ?>" disabled>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($student["first_name"]) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($student["last_name"]) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="Male" <?= $student["gender"] == "Male" ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= $student["gender"] == "Female" ? "selected" : "" ?>>Female</option>
                        <option value="Other" <?= $student["gender"] == "Other" ? "selected" : "" ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Birthdate</label>
                    <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($student["birthdate"] ?? "") ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($student["contact_number"] ?? "") ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student["email"] ?? "") ?>">
                </div>
            </div>

            <div class="mt-4 border-top pt-3">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Build / Update Account</button>
            </div>
        </form>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>

