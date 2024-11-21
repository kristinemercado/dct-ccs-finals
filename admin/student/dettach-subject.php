<?php
ob_start();
$title = "Detach a Subject";
require_once '../partials/header.php';
require_once '../partials/side-bar.php';


// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$error_message = '';
$success_message = '';
$record = [];

// Function to fetch student-subject record
function fetchRecord($record_id) {
    $connection = databaseConn();

    if (!$connection || $connection->connect_error) {
        die("Database connection failed: " . $connection->connect_error);
    }

    $query = "SELECT students.id AS student_id, students.first_name, students.last_name, 
                     subjects.subject_code, subjects.subject_name 
              FROM students_subjects 
              JOIN students ON students_subjects.student_id = students.id 
              JOIN subjects ON students_subjects.subject_id = subjects.id 
              WHERE students_subjects.id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    $connection->close();

    return $data;
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Detach a Subject</h1>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../student/register.php">Register Student</a></li>
            <li class="breadcrumb-item"><a href="attach-subject.php?id=<?php echo htmlspecialchars($record['student_id'] ?? ''); ?>">Attach Subject to Student</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detach Subject from Student</li>
        </ol>
    </nav>

    <!-- Display messages -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($record)): ?>
        <div class="card">
            <div class="card-body">
                <p>Are you sure you want to detach this subject from this student record?</p>
                <ul>
                    <li><strong>Student ID:</strong> <?php echo htmlspecialchars($record['student_id']); ?></li>
                    <li><strong>First Name:</strong> <?php echo htmlspecialchars($record['first_name']); ?></li>
                    <li><strong>Last Name:</strong> <?php echo htmlspecialchars($record['last_name']); ?></li>
                    <li><strong>Subject Code:</strong> <?php echo htmlspecialchars($record['subject_code']); ?></li>
                    <li><strong>Subject Name:</strong> <?php echo htmlspecialchars($record['subject_name']); ?></li>
                </ul>

                <form method="post">
                    <a href="attach-subject.php?id=<?php echo htmlspecialchars($record['student_id']); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="detach_subject" class="btn btn-danger">Detach Subject from Student</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once '../partials/footer.php'; ?>
<?php ob_end_flush(); ?>