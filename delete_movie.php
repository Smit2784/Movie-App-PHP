<?php
require_once 'admin_check.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT);

    if ($movie_id) {
        try {
            // Deleting a movie will cascade and delete related showtimes and bookings
            // due to the FOREIGN KEY constraints with ON DELETE CASCADE
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$movie_id]);
        } catch (PDOException $e) {
            // Handle error, maybe set a session error message
        }
    }
}

// Redirect back to the dashboard
header("Location: admin_dashboard.php");
exit();