<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

?>