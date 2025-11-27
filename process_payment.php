<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if ($booking_id) {
        try {
            // In a real application, you would process payment here with a gateway
            // like Stripe, PayPal, or Razorpay.
            // For this simulation, we will just assume the payment is successful.

            // Update the booking status from 'Pending' to 'Confirmed'
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);

            // Redirect to the confirmation page
            header("Location: confirmation.php?booking_id=" . $booking_id);
            exit();

        } catch (PDOException $e) {
            // Handle any database errors
            die("Payment processing failed. Please try again.");
        }
    }
}

// If accessed directly or without a booking ID, redirect to homepage
header("Location: index.php");
exit();
?>