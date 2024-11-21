<?php
ob_start(); // Start output buffering
$title = "Edit Subject";

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include dependencies
require_once '../../functions.php';
require_once '../partials/header.php';
require_once '../partials/side-bar.php';


// Initialize variables
$error_message = '';
$success_message = '';
$subject = null;

// Process request
$subject_id = getSubjectIdFromRequest();

if ($subject_id) {
    $subject = fetchSubjectById($subject_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
        handleUpdateSubject($subject_id, $_POST);
    }
} else {
    redirectToSubjectPage();
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Edit Subject</h1>

    <!-- Error Message -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Edit Subject Form -->
    <?php if ($subject): ?>
        <nav class="breadcrumb">
            <a class="breadcrumb-item" href="/admin/dashboard.php">Dashboard</a>
            <a class="breadcrumb-item" href="/admin/subject/add.php">Add Subject</a>
            <span class="breadcrumb-item active">Edit Subject</span>
        </nav>

        <div class="card mt-4">
            <div class="card-body">
                <form method="post">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="subject_code" name="subject_code" placeholder="Subject Code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" readonly>
                        <label for="subject_code">Subject Code</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="Subject Name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                        <label for="subject_name">Subject Name</label>
                    </div>
                    <div class="mb-3">
                        <button type="submit" name="update_subject" class="btn btn-primary w-100">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
require_once '../partials/footer.php';
ob_end_flush(); // Flush output buffer
?>