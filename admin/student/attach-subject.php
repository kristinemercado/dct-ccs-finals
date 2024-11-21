<?php
// Page Setup
$title = "Attach a Subject";
require_once '../partials/header.php';
require_once '../partials/side-bar.php';

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$error_message = '';
$success_message = '';

// Validate Student ID
if (!isset($_GET['id'])) {
    $error_message = "No student selected.";
    exitWithRedirect('../dashboard.php', $error_message);
}

$student_id = intval($_GET['id']);


// Fetch Subjects
$connection = db_connect();
if (!$connection || $connection->connect_error) {
    die("Database connection failed: " . $connection->connect_error);
}

// Fetch Available Subjects
$available_subjects = fetchAvailableSubjects($connection, $student_id);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attach_subjects'])) {
    if (!empty($_POST['subjects'])) {
        $success_message = attachSubjects($connection, $student_id, $_POST['subjects']);
        // Refresh available subjects
        $available_subjects = fetchAvailableSubjects($connection, $student_id);
    } else {
        $error_message = "Please select at least one subject to attach.";
    }
}

// Fetch Attached Subjects
$attached_subjects = fetchAttachedSubjects($connection, $student_id);

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Attach Subject to Student</h1>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../student/register.php">Register Student</a></li>
            <li class="breadcrumb-item active" aria-current="page">Attach Subject to Student</li>
        </ol>
    </nav>

    <?php displayMessage($error_message, $success_message); ?>

    <div class="card">
        <div class="card-body">
            <p><strong>Selected Student Information:</strong></p>
            <ul>
                <li><strong>Student ID:</strong> <?= htmlspecialchars($student_data['student_id']); ?></li>
                <li><strong>Name:</strong> <?= htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></li>
            </ul>

            <form method="post" action="">
                <p><strong>Select Subjects to Attach:</strong></p>
                <?= renderAvailableSubjects($available_subjects); ?>
            </form>
        </div>
    </div>

    <hr>

    <h3>Attached Subject List</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Grade</th>
                <th>Option</th>
            </tr>
        </thead>
        <tbody>
            <?= renderAttachedSubjects($attached_subjects); ?>
        </tbody>
    </table>
</main>