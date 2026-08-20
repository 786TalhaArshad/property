<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    error_log('DB connection failed: ' . $mysqli->connect_error . ' | Host: ' . DB_HOST . ' | DB: ' . DB_NAME);
    die('<div style="font-family:sans-serif;padding:40px;max-width:640px;margin:60px auto;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.06)">
            <h2 style="margin-top:0">Database Connection Failed</h2>
            <p>Could not connect to the database. Please check your configuration.</p>
            <p>Please create the database by importing <code>database.sql</code> from the project root, then set credentials in <code>includes/config.php</code>.</p>
          </div>');
}

$mysqli->set_charset('utf8mb4');
