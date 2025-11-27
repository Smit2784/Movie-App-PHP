<?php
session_start();
require_once 'db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get booking details
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;
$booking = null;

if ($booking_id) {
    $stmt = $pdo->prepare("
        SELECT b.*, s.show_date, s.show_time, s.total_seats, s.available_seats, 
               m.title, m.genre, m.duration, m.rating, m.image_url, m.price
        FROM bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        WHERE b.id = ? AND b.status = 'Confirmed'
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Generate QR code data
$qr_data = $booking ? "BOOKING-ID:" . $booking['id'] . "|MOVIE:" . $booking['title'] . "|DATE:" . $booking['show_date'] . "|TIME:" . $booking['show_time'] : "";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
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
<body class="bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 min-h-screen">
    <?php if ($booking): ?>
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
                        <img src="<?= htmlspecialchars($booking['image_url']) ?>" 
                             alt="<?= htmlspecialchars($booking['title']) ?>"
                             class="w-20 h-28 object-cover rounded-lg shadow-lg">
                        
                        <div class="flex-1">
                            <h4 class="text-white font-bold text-lg mb-3"><?= htmlspecialchars($booking['title']) ?></h4>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300 text-sm">Genre:</span>
                                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-medium">
                                        <?= htmlspecialchars($booking['genre']) ?>
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300 text-sm">Duration:</span>
                                    <span class="text-gray-100 text-sm font-medium"><?= $booking['duration'] ?> minutes</span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-300 text-sm">Rating:</span>
                                    <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                                        <?= $booking['rating'] ?>
                                    </span>
                                </div>
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
                            <span class="text-gray-300 text-sm">Number of Tickets:</span>
                            <span class="text-white font-medium"><?= $booking['num_tickets'] ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Seat Numbers:</span>
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
                            <span class="text-gray-100 text-sm"><?= htmlspecialchars($booking['customer_phone']) ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 text-sm">Booking Date:</span>
                            <span class="text-gray-100 text-sm"><?= date('M j, Y g:i A', strtotime($booking['created_at'] ?? 'now')) ?></span>
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
                            <span class="text-green-400 font-bold text-xl">₹<?= number_format($booking['total_amount']) ?>.00</span>
                        </div>
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
                        <li>For any queries, contact support at support@MovieTix.com</li>
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
                
                <button onclick="shareTicket()" 
                        class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-share-alt"></i>
                    <span>Share Ticket</span>
                </button>
                
                <a href="index.php" 
                   class="w-full bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 animate-fade-in">
                <p class="text-white/60 text-sm">Thank you for choosing MovieTix! 🍿</p>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- No Booking Found -->
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center">
            <div class="text-6xl text-white/50 mb-4">🎫</div>
            <h1 class="text-2xl font-bold text-white mb-4">Booking Not Found</h1>
            <p class="text-white/70 mb-6">The booking you're looking for doesn't exist or has been cancelled.</p>
            <a href="index.php" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function printTicket() {
            window.print();
        }

        function shareTicket() {
            if (navigator.share) {
                navigator.share({
                    title: 'MovieTix Booking Confirmation',
                    text: `My booking for <?= htmlspecialchars($booking['title'] ?? '') ?> is confirmed! Booking ID: #<?= $booking['id'] ?? '' ?>`,
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Booking link copied to clipboard!');
            }
        }

        // Add some celebration effects
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti effect
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    createConfetti();
                }, i * 100);
            }
        });

        function createConfetti() {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: ${['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'][Math.floor(Math.random() * 5)]};
                left: ${Math.random() * 100}%;
                top: -10px;
                border-radius: 50%;
                pointer-events: none;
                animation: fall 3s linear forwards;
                z-index: 1000;
            `;
            
            document.body.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 3000);
        }

        // Add CSS for confetti animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
