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



   
?>