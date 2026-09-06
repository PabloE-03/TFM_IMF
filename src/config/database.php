<?php
    function getDBConnection(): PDO{
        $host = "localhost";
        $db = "vuln_db";
        $user = "root";
        $pass = "admin";

        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return new PDO($dsn, $user, $pass, $options);

    }
?>