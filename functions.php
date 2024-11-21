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
?>