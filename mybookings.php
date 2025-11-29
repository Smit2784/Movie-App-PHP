<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    try {
        // First, verify the booking belongs to the user
        $stmt = $pdo->prepare("SELECT b.*, s.show_date, s.show_time FROM bookings b 
                               LEFT JOIN showtimes s ON b.showtime_id = s.id
                               WHERE b.id = ? AND b.customer_email = (SELECT email FROM users WHERE id = ?)");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            // Check if booking is already cancelled
            if ($booking['status'] === 'Cancelled') {
                $error_message = "This booking is already cancelled.";
            } else {
                // Check if show date/time has passed
                $show_datetime = $booking['show_date'] . ' ' . $booking['show_time'];
                $show_timestamp = strtotime($show_datetime);
                $current_timestamp = time();
                
                if ($show_timestamp < $current_timestamp) {
                    $error_message = "Cannot cancel booking. The show has already passed.";
                } else {
                    // Update booking status to Cancelled
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
                    $updateStmt->execute([$booking_id]);
                    
                    // Update available seats in showtimes table
                    $seatCount = $booking['num_tickets'];
                    $updateSeats = $pdo->prepare("UPDATE showtimes SET available_seats = available_seats + ? WHERE id = ?");
                    $updateSeats->execute([$seatCount, $booking['showtime_id']]);
                    
                    $success_message = "Booking #" . str_pad($booking_id, 4, '0', STR_PAD_LEFT) . " has been cancelled successfully. Refund will be processed within 5-7 business days.";
                }
            }
        } else {
            $error_message = "Booking not found or you don't have permission to cancel it.";
        }
    } catch (PDOException $e) {
        $error_message = "Error cancelling booking: " . $e->getMessage();
    }
}

// Fetch current user email
try {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $user['email'];
} catch (PDOException $e) {
    die("Could not fetch user data.");
}

// Fetch all bookings for the user with movie details
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.showtime_id,
            b.customer_name,
            b.customer_email,
            b.customer_phone,
            b.num_tickets,
            b.seat_numbers,
            b.total_amount,
            b.status,
            b.booking_date,
            m.title as movie_title,
            m.price as ticket_price,
            m.genre,
            m.image_url,
            s.show_date,
            s.show_time
        FROM bookings b
        LEFT JOIN showtimes s ON b.showtime_id = s.id
        LEFT JOIN movies m ON s.movie_id = m.id
        WHERE b.customer_email = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_email]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error_message = "Could not fetch bookings: " . $e->getMessage();
}

// Calculate statistics
$total_bookings = count($bookings);
$total_spent = array_sum(array_column($bookings, 'total_amount'));
$confirmed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'Confirmed'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .booking-card {
            transition: all 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen">
    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">🎬</div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">My Bookings</h1>
                        <p class="text-white/70">View your movie ticket history</p>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                            <i class="fas fa-cog"></i>
                            <span>Admin</span>
                        </a>
                    <?php endif; ?> 
                        <div class="relative group">
                            <button
                                class="flex items-center space-x-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-semibold px-4 py-2 rounded-full transition-all duration-300 transform hover:scale-105 shadow-lg">
                                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform group-hover:translate-y-0 translate-y-2 z-50">
                                <a href="profile.php"
                                    class="block px-4 py-3 text-gray-700 hover:bg-purple-50 rounded-t-lg transition-colors">
                                    <i class="fas fa-user-circle mr-2 text-purple-500"></i>
                                    My Profile
                                </a>
                                <a href="mybookings.php"
                                    class="block px-4 py-3 text-gray-700 hover:bg-purple-50 transition-colors">
                                    <!-- <i class="fas fa-ticket-alt mr-2 text-blue-500"></i> -->
                                    <i
                                        class="fas fa-calendar-check mr-2 group-hover:scale-110 transition-transform duration-300"></i>

                                    My Bookings
                                </a>
                                <hr class="my-1">
                                <a href="logout.php"
                                    class="block px-4 py-3 text-red-600 hover:bg-red-50 rounded-b-lg transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Alert Messages -->
        <?php if ($success_message): ?>
            <div class="max-w-6xl mx-auto mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg animate-slide-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="max-w-6xl mx-auto mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg animate-slide-up">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Bookings -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/70 text-sm font-medium mb-1">Total Bookings</p>
                        <h3 class="text-4xl font-bold text-white"><?= $total_bookings ?></h3>
                    </div>
                    <div class="bg-blue-500/20 rounded-full p-4">
                        <i class="fas fa-ticket-alt text-blue-400 text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl p-6 animate-fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/70 text-sm font-medium mb-1">Total Spent</p>
                        <h3 class="text-4xl font-bold text-white">₹<?= number_format($total_spent, 2) ?></h3>
                    </div>
                    <div class="bg-green-500/20 rounded-full p-4">
                        <i class="fas fa-rupee-sign text-green-400 text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Confirmed Bookings -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl p-6 animate-fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/70 text-sm font-medium mb-1">Confirmed</p>
                        <h3 class="text-4xl font-bold text-white"><?= $confirmed_bookings ?></h3>
                    </div>
                    <div class="bg-purple-500/20 rounded-full p-4">
                        <i class="fas fa-check-circle text-purple-400 text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl p-12 text-center animate-slide-up">
                <div class="text-white/30 mb-6">
                    <i class="fas fa-inbox text-8xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">No Bookings Yet</h3>
                <p class="text-white/70 mb-6">You haven't made any movie bookings yet. Start exploring and book your first show!</p>
                <a href="index.php" class="inline-flex items-center bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white font-bold py-3 px-8 rounded-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-film mr-2"></i>
                    Browse Movies
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($bookings as $index => $booking): ?>
                    <?php
                    // Check if show has passed
                    $show_datetime = $booking['show_date'] . ' ' . $booking['show_time'];
                    $show_timestamp = strtotime($show_datetime);
                    $current_timestamp = time();
                    $show_passed = $show_timestamp < $current_timestamp;
                    $can_cancel = ($booking['status'] === 'Confirmed' || $booking['status'] === 'Pending') && !$show_passed;
                    ?>
                    <div class="booking-card bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl overflow-hidden animate-slide-up" style="animation-delay: <?= $index * 0.1 ?>s">
                        <div class="md:flex">
                            <!-- Left Section - Movie Info -->
                            <div class="md:w-2/3 p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-bold text-white mb-2">
                                            <i class="fas fa-film text-yellow-400 mr-2"></i>
                                            <?= htmlspecialchars($booking['movie_title'] ?? 'Movie Title Unavailable') ?>
                                        </h3>
                                        <div class="flex flex-wrap gap-3 text-white/70 mb-3">
                                            <span class="flex items-center">
                                                <i class="fas fa-calendar-alt mr-2 text-blue-400"></i>
                                                <?= $booking['show_date'] ? date('D, M j, Y', strtotime($booking['show_date'])) : 'Date N/A' ?>
                                            </span>
                                            <span class="flex items-center">
                                                <i class="fas fa-clock mr-2 text-green-400"></i>
                                                <?= $booking['show_time'] ? date('g:i A', strtotime($booking['show_time'])) : 'Time N/A' ?>
                                            </span>
                                            <?php if ($booking['genre']): ?>
                                            <span class="flex items-center">
                                                <i class="fas fa-tag mr-2 text-purple-400"></i>
                                                <?= htmlspecialchars($booking['genre']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php 
                                    $statusColors = [
                                        'Confirmed' => 'bg-green-500',
                                        'Pending' => 'bg-yellow-500',
                                        'Cancelled' => 'bg-red-500'
                                    ];
                                    $statusColor = $statusColors[$booking['status']] ?? 'bg-gray-500';
                                    ?>
                                    <span class="<?= $statusColor ?> text-white px-4 py-2 rounded-full text-sm font-bold flex items-center ml-4">
                                        <i class="fas fa-circle text-xs mr-2"></i>
                                        <?= htmlspecialchars($booking['status']) ?>
                                    </span>
                                </div>

                                <!-- Booking Details -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="bg-white/5 rounded-lg p-4">
                                        <p class="text-white/60 text-sm mb-1">Booking ID</p>
                                        <p class="text-white font-bold text-lg">#<?= str_pad($booking['id'], 4, '0', STR_PAD_LEFT) ?></p>
                                    </div>
                                    <div class="bg-white/5 rounded-lg p-4">
                                        <p class="text-white/60 text-sm mb-1">Booking Date</p>
                                        <p class="text-white font-bold text-lg"><?= date('M j, Y', strtotime($booking['booking_date'])) ?></p>
                                    </div>
                                </div>

                                <!-- Seat Information -->
                                <div class="bg-gradient-to-r from-indigo-500/20 to-purple-500/20 rounded-lg p-4 border border-indigo-400/30">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-white/70 text-sm mb-2">Seat Numbers</p>
                                            <div class="flex flex-wrap gap-2">
                                                <?php 
                                                $seats = explode(',', $booking['seat_numbers']);
                                                foreach ($seats as $seat): 
                                                ?>
                                                    <span class="bg-indigo-500 text-white px-3 py-1 rounded-md font-mono text-sm font-bold">
                                                        <?= trim($seat) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <p class="text-white/70 text-sm mb-1">Tickets</p>
                                            <p class="text-3xl font-bold text-white"><?= $booking['num_tickets'] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Section - Payment Info -->
                            <div class="md:w-1/3 bg-gradient-to-br from-purple-600/30 to-pink-600/30 p-6 border-l border-white/20">
                                <h4 class="text-lg font-bold text-white mb-4 flex items-center">
                                    <i class="fas fa-receipt mr-2 text-yellow-400"></i>
                                    Payment Summary
                                </h4>
                                
                                <div class="space-y-3 mb-6">
                                    <div class="flex justify-between text-white/80">
                                        <span>Ticket Price</span>
                                        <span>₹<?= number_format($booking['ticket_price'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="flex justify-between text-white/80">
                                        <span>Quantity</span>
                                        <span>× <?= $booking['num_tickets'] ?></span>
                                    </div>
                                    <div class="border-t border-white/20 pt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-white font-bold text-lg">Total Amount</span>
                                            <span class="text-2xl font-bold text-green-400">₹<?= number_format($booking['total_amount'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white/10 rounded-lg p-4 mb-4">
                                    <p class="text-white/60 text-xs mb-2">Contact Information</p>
                                    <p class="text-white text-sm font-medium mb-1">
                                        <i class="fas fa-user mr-2 text-blue-400"></i>
                                        <?= htmlspecialchars($booking['customer_name']) ?>
                                    </p>
                                    <p class="text-white text-sm font-medium mb-1">
                                        <i class="fas fa-envelope mr-2 text-green-400"></i>
                                        <?= htmlspecialchars($booking['customer_email']) ?>
                                    </p>
                                    <p class="text-white text-sm font-medium">
                                        <i class="fas fa-phone mr-2 text-purple-400"></i>
                                        <?= htmlspecialchars($booking['customer_phone']) ?>
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <?php if ($can_cancel): ?>
                                    <button onclick="confirmCancellation(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['movie_title']) ?>')" 
                                            class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center justify-center mb-2">
                                        <i class="fas fa-times-circle mr-2"></i>
                                        Cancel Booking
                                    </button>
                                <?php elseif ($booking['status'] === 'Cancelled'): ?>
                                    <div class="bg-red-500/20 border border-red-500/50 text-red-300 rounded-lg p-3 text-center">
                                        <i class="fas fa-ban mr-2"></i>
                                        Booking Cancelled
                                    </div>
                                <?php elseif ($show_passed): ?>
                                    <div class="bg-gray-500/20 border border-gray-500/50 text-gray-300 rounded-lg p-3 text-center">
                                        <i class="fas fa-clock mr-2"></i>
                                        Show Completed
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white/5 backdrop-blur-md border-t border-white/20 mt-12">
        <div class="container mx-auto px-4 py-6 text-center text-white/70">
            <p>&copy; <?= date('Y') ?> MovieTix. All rights reserved.</p>
        </div>
    </footer>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 animate-slide-up">
            <div class="text-center">
                <div class="bg-red-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Cancel Booking?</h3>
                <p class="text-gray-600 mb-2">Are you sure you want to cancel this booking for:</p>
                <p class="text-lg font-bold text-purple-600 mb-4" id="movieTitleModal"></p>
                <p class="text-sm text-gray-500 mb-6">Refund will be processed within 5-7 business days.</p>
                
                <form method="POST" id="cancelForm">
                    <input type="hidden" name="cancel_booking" value="1">
                    <input type="hidden" name="booking_id" id="bookingIdToCancel">
                    
                    <div class="flex gap-4">
                        <button type="button" onclick="closeCancelModal()" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition-all duration-300">
                            No, Keep It
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300">
                            Yes, Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to top on page load
        window.scrollTo({ top: 0, behavior: 'smooth' });

        function confirmCancellation(bookingId, movieTitle) {
            document.getElementById('bookingIdToCancel').value = bookingId;
            document.getElementById('movieTitleModal').textContent = movieTitle;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCancelModal();
            }
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                header, footer, .bg-gradient-to-br, button { display: none !important; }
                body { background: white !important; }
                .booking-card { page-break-after: always; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
