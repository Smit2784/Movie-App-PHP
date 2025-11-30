<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if ($booking_id) {
        try {
            // Start transaction for safety
            $pdo->beginTransaction();

            // Update booking status from 'Pending' to 'Confirmed'
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ? AND status = 'Pending'");
            $result = $stmt->execute([$booking_id]);

            if ($result) {
                // Check if row was actually affected (prevents double confirmation)
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    $pdo->commit();
                    
                    // Redirect to confirmation with booking ID
                    header("Location: confirmation.php?booking_id=" . $booking_id);
                    exit();
                } else {
                    // Already confirmed or invalid
                    $pdo->rollBack();
                    header("Location: index.php?error=already_confirmed");
                    exit();
                }
            } else {
                $pdo->rollBack();
                die("Booking confirmation failed. Please contact support.");
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            die("Payment processing failed. Please try again.");
        }
    } else {
        header("Location: index.php?error=invalid_booking");
        exit();
    }
} else {
    // Direct access
    header("Location: index.php");
    exit();
}
?>
