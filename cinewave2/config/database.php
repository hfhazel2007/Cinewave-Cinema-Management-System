<?php
// config/database.php

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host     = $_ENV['DB_HOST'] ?? 'aws-1-eu-central-1.pooler.supabase.com';
        $port     = $_ENV['DB_PORT'] ?? '6543';
        $dbname   = $_ENV['DB_NAME'] ?? 'postgres';
        $user     = $_ENV['DB_USER'] ?? 'postgres.ypmqiojwcmxpgvxefwmt';
        $password = $_ENV['DB_PASS'] ?? 'FQ8S4aWqOzKAu2i3';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}