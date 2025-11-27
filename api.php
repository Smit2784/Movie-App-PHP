<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? null;

try {
    if ($action == 'get_showtimes' && isset($_GET['movie_id'])) {
        $movieId = intval($_GET['movie_id']);
        $stmt = $pdo->prepare("
            SELECT s.id, s.show_time, s.available_seats, m.price
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            WHERE s.movie_id = ? AND s.show_date = CURDATE()
            ORDER BY s.show_time
        ");
        $stmt->execute([$movieId]);
        $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'showtimes' => $showtimes]);
    } 
    // NEW ACTION: Get all booked seats for a specific showtime
    elseif ($action == 'get_booked_seats' && isset($_GET['showtime_id'])) {
        $showtimeId = intval($_GET['showtime_id']);
        $stmt = $pdo->prepare("SELECT seat_identifier FROM booked_seats WHERE showtime_id = ?");
        $stmt->execute([$showtimeId]);
        // Fetch just the seat identifiers into a simple array, e.g., ["A1", "B5", "C8"]
        $bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); 
        echo json_encode(['success' => true, 'booked_seats' => $bookedSeats]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid API call.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>