<?php
    function databaseConn(){
        $host = 'localhost';
        $user = 'root';
        $password = ''; // Default for Laragon
        $database = 'dct-ccs-finals';
    
        $connection = new mysqli($host, $user, $password, $database);
    
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }
    
        return $connection;
    }

    //User Authentication
    function userAuth($email,$password) {
        $connection = databaseConn();
        $password_hash = md5($password); // MD5 hash for password
    
        $query = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('ss', $email, $password_hash);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); 
        }
    
        return false; 
    }
    function userLogin() {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
    }
   
    function checkDuplicateSubjectData($subject_data) {
        $connection = databaseConn();
        $query = "SELECT * FROM subjects WHERE subject_code = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('s', $subject_data['subject_code']);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return "Subject code already exists. Please choose another."; // Return the error message for duplicates
        }
    
        return ''; // No duplicate found
    }
    function checkDuplicateSubjectName($subject_name) {
        $connection = databaseConn();
        $query = "SELECT * FROM subjects WHERE subject_name = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('s', $subject_name);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return "Subject name already exists. Please choose another."; // Return the error message for duplicates
        }
    
        return ''; // No duplicate found
    }

    function renderAlert($messages, $type = 'danger') {
        if (empty($messages)) {
            return '';
        }
        // Ensure messages is an array
        if (!is_array($messages)) {
            $messages = [$messages];
        }
    
        $html = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        $html .= '<ul>';
        foreach ($messages as $message) {
            $html .= '<li>' . htmlspecialchars($message) . '</li>';
        }
        $html .= '</ul>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
    
        return $html;
    }
    function handleAddSubject() {
        global $error_message, $success_message, $subject_code, $subject_name;
    
        $subject_code = trim($_POST['subject_code'] ?? '');
        $subject_name = trim($_POST['subject_name'] ?? '');
    
        // Validate inputs
        $errors = validateSubjectInputs($subject_code, $subject_name);
    
        if (empty($errors)) {
            // Check for duplicate entries
            $duplicate_code_error = checkDuplicateSubjectData(['subject_code' => $subject_code]);
            $duplicate_name_error = checkDuplicateSubjectName($subject_name);
    
            if (!empty($duplicate_code_error) || !empty($duplicate_name_error)) {
                $error_message = renderAlert(
                    array_filter([$duplicate_code_error, $duplicate_name_error]),
                    'danger'
                );
            } else {
                // Insert subject into the database
                if (addSubjectToDatabase($subject_code, $subject_name)) {
                    $success_message = renderAlert(["Subject added successfully!"], 'success');
                    $subject_code = $subject_name = ''; // Clear fields
                } else {
                    $error_message = renderAlert(["Error adding subject. Please try again."], 'danger');
                }
            }
        } else {
            $error_message = renderAlert($errors, 'danger');
        }
    }
    function validateSubjectInputs($subject_code, $subject_name) {
        $errors = [];
    
        if (empty($subject_code)) {
            $errors[] = "Subject Code is required.";
        } elseif (strlen($subject_code) > 4) {
            $errors[] = "Subject Code cannot be longer than 4 characters.";
        }
    
        if (empty($subject_name)) {
            $errors[] = "Subject Name is required.";
        }
    
        return $errors;
    }
    function addSubjectToDatabase($subject_code, $subject_name) {
        $connection = databaseConn();
        $query = "INSERT INTO subjects (subject_code, subject_name) VALUES (?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('ss', $subject_code, $subject_name);
    
        return $stmt->execute();
    }
    function handleDeleteSubject($subject_id) {
    global $error_message;
    
    $connection = databaseConn();
    $query = "DELETE FROM subjects WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $subject_id);

    if ($stmt->execute()) {
        redirectToSubjectPage();
    } else {
        $error_message = "Failed to delete the subject: " . $connection->error;
    }
}

/**
 * Redirect to the subject page
 */
function redirectToSubjectPage() {
    header("Location: /admin/subject/add.php");
    exit();
}
/**
 * Fetch subject details by ID
 */
function fetchSubjectById($subject_id) {
    $connection = databaseConn();
    $query = "SELECT * FROM subjects WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getSubjectIdFromRequest() {
    return isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : null;
}
/**
 * Handle subject update
 */
function handleUpdateSubject($subject_id, $data) {
    global $error_message;

    $subject_name = trim($data['subject_name']);
    $subject_code = trim($data['subject_code']); // Subject code is readonly in the form

    if (validateSubjectInput($subject_name, $subject_code)) {
        if (isDuplicateSubject($subject_name, $subject_code, $subject_id)) {
            $error_message = "A subject with the same name or code already exists.";
        } else {
            updateSubject($subject_id, $subject_name, $subject_code);
        }
    }
}

/**
 * Validate subject input
 */
function validateSubjectInput($subject_name, $subject_code) {
    global $error_message;

    if (empty($subject_name)) {
        $error_message = "Subject name cannot be empty.";
        return false;
    }
    if (empty($subject_code)) {
        $error_message = "Subject code cannot be empty.";
        return false;
    }
    return true;
}

/**
 * Check for duplicate subject name or code
 */
function isDuplicateSubject($subject_name, $subject_code, $subject_id) {
    $connection = databaseConn();
    $query = "SELECT * FROM subjects WHERE (subject_name = ? OR subject_code = ?) AND id != ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ssi', $subject_name, $subject_code, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Update subject in the database
 */
function updateSubject($subject_id, $subject_name, $subject_code) {
    global $error_message;

    $connection = databaseConn();
    $query = "UPDATE subjects SET subject_name = ?, subject_code = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ssi', $subject_name, $subject_code, $subject_id);

    if ($stmt->execute()) {
        header("Location: /admin/subject/add.php?message=Subject+updated+successfully");
        exit();
    } else {
        $error_message = "Failed to update the subject. Please try again.";
    }
}
/**
 * Process student registration
 */
function processStudentRegistration($student_data) {
    global $success_message;

    $errors = validateStudentData($student_data);

    if (!empty($errors)) {
        return renderAlert($errors, 'danger');
    }

    // Check for duplicate student ID
    $duplicateError = checkDuplicateStudentData($student_data);
    if (!empty($duplicateError)) {
        return renderAlert([$duplicateError], 'danger');
    }

    // Insert into the database
    $connection = db_connect();
    $student_id_unique = generateUniqueIdForStudents();
    $query = "INSERT INTO students (id, student_id, first_name, last_name) VALUES (?, ?, ?, ?)";

    $stmt = $connection->prepare($query);
    if ($stmt) {
        $stmt->bind_param('isss', $student_id_unique, $student_data['student_id'], $student_data['first_name'], $student_data['last_name']);
        if ($stmt->execute()) {
            $success_message = renderAlert(["Student successfully registered!"], 'success');
        } else {
            return renderAlert(["Failed to register student. Error: " . $stmt->error], 'danger');
        }
        $stmt->close();
    } else {
        return renderAlert(["Statement preparation failed: " . $connection->error], 'danger');
    }

    $connection->close();
    return '';
}

/**
 * Validate student data
 */
function validateStudentData($data) {
    $errors = [];
    if (empty($data['student_id'])) {
        $errors[] = "Student ID is required.";
    } elseif (strlen($data['student_id']) != 4) {
        $errors[] = "Student ID must be exactly 4 characters.";
    }
    if (empty($data['first_name'])) {
        $errors[] = "First name is required.";
    }
    if (empty($data['last_name'])) {
        $errors[] = "Last name is required.";
    }
    return $errors;
}

/**
 * Check for duplicate student data
 */
function checkDuplicateStudentData($data) {
    $connection = db_connect();
    $query = "SELECT * FROM students WHERE student_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $data['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        $connection->close();
        return "Student ID already exists.";
    }

    $stmt->close();
    $connection->close();
    return '';
}
/**
 * Validate and update the student record
 */
function validateAndUpdateStudent($student_id, $data) {
    if (empty($data['first_name']) || empty($data['last_name'])) {
        return "First Name and Last Name are required.";
    }

    $connection = db_connect();
    $query = "UPDATE students SET first_name = ?, last_name = ? WHERE id = ?";
    $stmt = $connection->prepare($query);

    if ($stmt) {
        $stmt->bind_param('ssi', $data['first_name'], $data['last_name'], $student_id);
        if (!$stmt->execute()) {
            $error = "Failed to update student record. Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Failed to prepare statement: " . $connection->error;
    }

    $connection->close();
    return $error ?? '';
}
// Handle detachment
function detachSubject($record_id, $student_id) {
    $connection = db_connect();
    $query = "DELETE FROM students_subjects WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $record_id);

    if ($stmt->execute()) {
        header("Location: attach-subject.php?id=" . htmlspecialchars($student_id));
        exit;
    } else {
        global $error_message;
        $error_message = "Failed to detach the subject. Please try again.";
    }

    $stmt->close();
    $connection->close();
}

// Validate and process GET parameter


// Helper Functions
function exitWithRedirect($url, $message) {
    $_SESSION['error_message'] = $message;
    header("Location: $url");
    exit;
}

function fetchAvailableSubjects($connection, $student_id) {
    $query = "SELECT * FROM subjects WHERE id NOT IN (SELECT subject_id FROM students_subjects WHERE student_id = ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    return $stmt->get_result();
}

function fetchAttachedSubjects($connection, $student_id) {
    $query = "SELECT subjects.subject_code, subjects.subject_name, students_subjects.grade, students_subjects.id 
              FROM subjects 
              JOIN students_subjects ON subjects.id = students_subjects.subject_id 
              WHERE students_subjects.student_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    return $stmt->get_result();
}

function attachSubjects($connection, $student_id, $subjects) {
    foreach ($subjects as $subject_id) {
        $query = "INSERT INTO students_subjects (student_id, subject_id, grade) VALUES (?, ?, ?)";
        $stmt = $connection->prepare($query);
        $grade = 0.00;
        $stmt->bind_param('iid', $student_id, $subject_id, $grade);
        $stmt->execute();
    }
    return "Subjects successfully attached to the student.";
}

function renderAvailableSubjects($available_subjects) {
    if ($available_subjects->num_rows > 0) {
        $html = '';
        while ($subject = $available_subjects->fetch_assoc()) {
            $id = htmlspecialchars($subject['id']);
            $label = htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']);
            $html .= "<div class='form-check'>
                        <input class='form-check-input' type='checkbox' name='subjects[]' value='$id' id='subject_$id'>
                        <label class='form-check-label' for='subject_$id'>$label</label>
                      </div>";
        }
        $html .= '<button type="submit" name="attach_subjects" class="btn btn-primary mt-3">Attach Subjects</button>';
        return $html;
    }
    return "<p>No subjects available to attach.</p>";
}

function renderAttachedSubjects($attached_subjects) {
    if ($attached_subjects->num_rows > 0) {
        $html = '';
        while ($row = $attached_subjects->fetch_assoc()) {
            $subject_code = htmlspecialchars($row['subject_code']);
            $subject_name = htmlspecialchars($row['subject_name']);
            $grade = $row['grade'] > 0 ? number_format($row['grade'], 2) : '--.--';
            $id = htmlspecialchars($row['id']);
            $html .= "<tr>
                        <td>$subject_code</td>
                        <td>$subject_name</td>
                        <td>$grade</td>
                        <td>
                            <form method='get' action='dettach-subject.php' style='display:inline-block;'>
                                <input type='hidden' name='id' value='$id'>
                                <button type='submit' class='btn btn-danger btn-sm'>Detach</button>
                            </form>
                            <form method='post' action='assign-grade.php' style='display:inline-block;'>
                                <input type='hidden' name='id' value='$id'>
                                <button type='submit' class='btn btn-success btn-sm'>Assign Grade</button>
                            </form>
                        </td>
                      </tr>";
        }
        return $html;
    }
    return "<tr><td colspan='4'>No subjects attached.</td></tr>";
}

function displayMessage($error_message, $success_message) {
    if (!empty($error_message)) {
        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                " . htmlspecialchars($error_message) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    } elseif (!empty($success_message)) {
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                " . htmlspecialchars($success_message) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}


   ?>