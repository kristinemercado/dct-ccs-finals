<?php
$title = "Register a New Student"; // Page title
require_once '../partials/header.php';
require_once '../partials/side-bar.php';
require_once '../../functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize messages
$error_message = '';
$success_message = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_data = [
        'student_id' => generateValidStudentId(trim($_POST['student_id'] ?? '')),
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? '')
    ];

    // Validate and process the student data
    $error_message = processStudentRegistration($student_data);
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-5">
    <h1 class="h2">Register a New Student</h1>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Register Student</li>
        </ol>
    </nav>

    <!-- Display messages -->
    <?php if (!empty($error_message)): ?>
        <?php echo $error_message; ?>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <?php echo $success_message; ?>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="post" action="">
        <div class="mb-3">
            <label for="student_id" class="form-label">Student ID</label>
            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter Student ID" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter First Name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter Last Name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary w-100">Add Student</button>
        </div>
    </form>

    <hr>

    <!-- Student List -->
    <h2 class="h4">Student List</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">Student ID</th>
                <th scope="col">First Name</th>
                <th scope="col">Last Name</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $connection = databaseConn();
            $query = "SELECT * FROM students";
            $result = $connection->query($query);

            while ($student = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="delete.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                        <a href="attach-subject.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">Attach Subject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php $connection->close(); ?>
        </tbody>
    </table>
</main>

<?php require_once '../partials/footer.php'; ?>