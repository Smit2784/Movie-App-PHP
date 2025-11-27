<?php
// Database configuration
$host = 'localhost';
$dbname = 'movie_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Use a more user-friendly error message in a production environment
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>