<?php
// db.php - Database connection file

$host = 'localhost';
$dbname = 'student_records_project_sem1';
$username = 'root';  // Change this to your MySQL username
$password = '';      // Change this to your MySQL password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>