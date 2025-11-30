<?php
session_start();
require_once 'db.php';

// Security: ensure user is logged in or has valid session
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die("Service temporarily unavailable. Please try again later.");
}

// Get booking details
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking = null;

if ($booking_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.*, 
                s.show_date, s.show_time, 
                m.title, m.genre, m.duration, m.rating, m.image_url, m.price
            FROM bookings b 
            JOIN showtimes s ON b.showtime_id = s.id 
            JOIN movies m ON s.movie_id = m.id 
            WHERE b.id = ? AND b.status = 'Confirmed'
            AND b.customer_email = ?
        ");
        $stmt->execute([$booking_id, $_SESSION['user_email'] ?? '']);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Booking fetch error for ID $booking_id: " . $e->getMessage());
    }
}

// Generate QR code data
$qr_data = $booking ? "BOOKING-ID:" . $booking['id'] . "|MOVIE:" . $booking['title'] . "|DATE:" . $booking['show_date'] . "|TIME:" . $booking['show_time'] . "|SEATS:" . $booking['seat_numbers'] : "";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);

// If booking not found, redirect to mybookings with error
if (!$booking) {
    header('Location: mybookings.php?error=not_found');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'bounce-in': 'bounceIn 0.8s ease-out',
                        'fade-in': 'fadeIn 0.6s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3) translateY(-50px); }
            50% { opacity: 1; transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen">
    <!-- Main Container -->
    <div class="min-h-screen py-4 px-4">
        <div class="max-w-md mx-auto">
            <!-- Header -->
            <div class="text-center mb-6 animate-fade-in">
                <div class="text-4xl mb-2">🎬</div>
                <h1 class="text-2xl font-bold text-white">MovieTix</h1>
                <p class="text-white/80 text-sm">Your ticket booking confirmation</p>
            </div>

            <!-- Confirmation Card -->
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl animate-bounce-in">
                <!-- Success Header -->
                <div class="bg-gradient-to-r from-green-400 to-green-500 px-6 py-8 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10"></div>
                    <div class="relative z-10">
                        <div class="bg-white/20 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-white text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">Booking Confirmed!</h2>
                        <p class="text-white/90 text-sm mb-3">Thank you for choosing MovieTix</p>
                        <div class="bg-white/20 rounded-full px-4 py-2 inline-block">
                            <span class="text-white font-medium">Booking ID: #<?= $booking['id'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Movie Details Section -->
                <div class="bg-gray-800 px-6 py-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-film text-purple-400 mr-2"></i>
                        <h3 class="text-white font-semibold">Movie Details</h3>
                    </div>
                    
                    <div class="flex space-x-4">
                        <?php if (!empty($booking['image_url'])): ?>
                            <img src="<?= htmlspecialchars($booking['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($booking['title']) ?>"
                                 class="w-20 h-28 object-cover rounded-lg shadow-lg">
                        <?php endif; ?>
                        
                        <div class="flex-1">
                            <h4 class="text-white font-bold text-lg mb-3"><?= htmlspecialchars($booking['title']) ?></h4>
                            
                            <div class="space-y-2">
                                <?php if ($booking['genre']): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300 text-sm">Genre:</span>
                                        <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                                            <?= htmlspecialchars($booking['genre']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['duration']): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300 text-sm">Duration:</span>
                                        <span class="text-gray-100 text-sm font-medium"><?= $booking['duration'] ?> minutes</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['rating']): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300 text-sm">Rating:</span>
                                        <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                                            <?= $booking['rating'] ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Show Details Section -->
                <div class="bg-gray-800 border-t border-gray-700 px-6 py-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-calendar-alt text-blue-400 mr-2"></i>
                        <h3 class="text-white font-semibold">Show Details</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Date:</span>
                            <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                <?= date('l, M j, Y', strtotime($booking['show_date'])) ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Time:</span>
                            <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                <?= date('g:i A', strtotime($booking['show_time'])) ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Tickets:</span>
                            <span class="text-white font-medium"><?= $booking['num_tickets'] ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Seats:</span>
                            <span class="bg-indigo-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                <?= htmlspecialchars($booking['seat_numbers']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="bg-gray-800 border-t border-gray-700 px-6 py-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-user text-green-400 mr-2"></i>
                        <h3 class="text-white font-semibold">Customer Information</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Name:</span>
                            <span class="text-white font-medium"><?= htmlspecialchars($booking['customer_name']) ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Email:</span>
                            <span class="text-gray-100 text-sm"><?= htmlspecialchars($booking['customer_email']) ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Phone:</span>
                            <span class="text-gray-100 text-sm">+91 <?= htmlspecialchars($booking['customer_phone']) ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Booking Time:</span>
                            <span class="text-gray-100 text-sm"><?= date('M j, Y \a\t g:i A', strtotime($booking['booking_date'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary Section -->
                <div class="bg-gray-800 border-t border-gray-700 px-6 py-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-receipt text-yellow-400 mr-2"></i>
                        <h3 class="text-white font-semibold">Payment Summary</h3>
                    </div>
                    
                    <div class="bg-gray-700 rounded-lg p-4 border-l-4 border-green-400">
                        <div class="flex justify-between items-center">
                            <span class="text-white font-semibold">Total Amount Paid:</span>
                            <span class="text-green-400 font-bold text-xl">₹<?= number_format($booking['total_amount'], 2) ?></span>
                        </div>
                        <p class="text-gray-300 text-sm mt-2">Payment successful via <?= $_POST['payment_method'] ?? 'Card' ?></p>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="bg-gray-900 px-6 py-8 text-center">
                    <div class="mb-4">
                        <i class="fas fa-mobile-alt text-white text-lg mr-2"></i>
                        <span class="text-white font-semibold">Mobile Ticket</span>
                    </div>
                    <p class="text-gray-300 text-sm mb-6">Show this QR code at the theater for quick entry</p>
                    
                    <div class="bg-white rounded-2xl p-6 inline-block mb-4 shadow-xl">
                        <img src="<?= $qr_url ?>" alt="QR Code" class="w-48 h-48 mx-auto">
                    </div>
                    
                    <p class="text-gray-400 text-xs">Booking ID: #<?= $booking['id'] ?></p>
                </div>

                <!-- Important Information -->
                <div class="bg-yellow-600 px-6 py-6">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-exclamation-triangle text-yellow-100 mr-2"></i>
                        <span class="text-yellow-100 font-semibold">Important Information</span>
                    </div>
                    
                    <ul class="text-yellow-100 text-sm space-y-1 list-disc list-inside">
                        <li>Please arrive at least 15 minutes before showtime</li>
                        <li>Present this confirmation or QR code at the theater</li>
                        <li>No cancellations or refunds 2 hours before showtime</li>
                        <li>Outside food and beverages are not permitted</li>
                        <li>For any queries, contact support at support@movietix.com</li>
                    </ul>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 space-y-3 animate-fade-in">
                <button onclick="printTicket()" 
                        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-print"></i>
                    <span>Print Ticket</span>
                </button>
                
                <a href="mybookings.php"
                   class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Go to My Bookings</span>
                </a>
                
                <a href="index.php" 
                   class="w-full bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 animate-fade-in">
                <p class="text-white/60 text-sm">Thank you for choosing MovieTix! 🎬</p>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style media="print">
        body { 
            background: white !important; 
            color: black !important; 
            font-size: 12pt !important;
        }
        .bg-gradient-to-br { background: white !important; }
        .bg-white\\/10, .bg-gray-800, .bg-gray-700, .bg-gray-900 { background: white !important; }
        .border-white\\/20 { border: 1px solid #ccc !important; }
        button, a:not([href]) { display: none !important; }
        .text-white { color: black !important; }
        .text-white\\/80, .text-white\\/70, .text-white\\/60, .text-gray-100, .text-gray-300 { color: #666 !important; }
        .text-green-400 { color: #059669 !important; }
        .text-purple-300 { color: #a78bfa !important; }
        .text-blue-300 { color: #93c5fd !important; }
        .bg-yellow-600 { background: #fbbf24 !important; color: #92400e !important; }
        .text-yellow-100 { color: #92400e !important; }
        .bg-blue-500 { background: #3b82f6 !important; }
        .bg-purple-500 { background: #a855f7 !important; }
        .bg-indigo-500 { background: #6366f1 !important; }
        .bg-green-500 { background: #059669 !important; }
    </style>

    <script>
        function printTicket() {
            window.print();
        }

        function shareTicket() {
            if (navigator.share) {
                navigator.share({
                    title: 'MovieTix Booking Confirmation',
                    text: `My booking for <?= htmlspecialchars($booking['title']) ?> is confirmed! Booking ID: #<?= $booking['id'] ?>`,
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Booking link copied to clipboard!');
            }
        }

        // Auto scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Auto-hide success page after 30 seconds
        setTimeout(() => {
            window.location.href = 'mybookings.php';
        }, 30000);
    </script>
</body>
</html>
