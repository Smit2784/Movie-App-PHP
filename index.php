<?php
session_start();
require_once 'db.php';

// Fetch user email if logged in
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $_SESSION['user_email'] = $user_data['email'];
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
}


// Handle booking form submission
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tickets'])) {
    $showtime_id = intval($_POST['showtime_id']);
    $num_tickets = intval($_POST['num_tickets']);
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $customer_phone = trim($_POST['customer_phone']);
    $seat_numbers_str = trim($_POST['seat_numbers']);

    if ($showtime_id && $num_tickets > 0 && $customer_name && $customer_email && $customer_phone && $seat_numbers_str) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT s.*, m.price FROM showtimes s JOIN movies m ON s.movie_id = m.id WHERE s.id = ? FOR UPDATE");
            $stmt->execute([$showtime_id]);
            $showtime = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($showtime && $showtime['available_seats'] >= $num_tickets) {
                $total_amount = $showtime['price'] * $num_tickets;

                $stmt = $pdo->prepare("INSERT INTO bookings (showtime_id, customer_name, customer_email, customer_phone, num_tickets, seat_numbers, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$showtime_id, $customer_name, $customer_email, $customer_phone, $num_tickets, $seat_numbers_str, $total_amount]);

                $booking_id = $pdo->lastInsertId();

                $seats_array = explode(',', $seat_numbers_str);
                $seatStmt = $pdo->prepare("INSERT INTO booked_seats (booking_id, showtime_id, seat_identifier) VALUES (?, ?, ?)");
                foreach ($seats_array as $seat) {
                    $seatStmt->execute([$booking_id, $showtime_id, trim($seat)]);
                }

                $stmt = $pdo->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?");
                $stmt->execute([$num_tickets, $showtime_id]);

                $pdo->commit();
                header("Location: payment.php?booking_id=" . $booking_id);
                exit();
            } else {
                $error_message = "Not enough seats available or showtime not found.";
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $error_message = "Sorry, one or more of the seats you selected were just booked by someone else. Please try again.";
            } else {
                $error_message = "Booking failed due to a system error. Please try again.";
            }
        }
    }
}

$searchTerm = trim($_GET['search'] ?? '');

$sql = "
    SELECT DISTINCT m.*
    FROM movies m
    JOIN showtimes s ON m.id = s.movie_id
    WHERE s.show_date = CURDATE()
";

$params = [];

if (!empty($searchTerm)) {
    $sql .= " AND m.title LIKE ?";
    $params[] = '%' . $searchTerm . '%';
}

$sql .= " GROUP BY m.id HAVING COUNT(s.id) > 0 ORDER BY m.title";

// Prepare and execute the query securely
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieTix - Book Your Movie Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen">
    <!-- Header -->
    <!-- Enhanced Header with Navigation Links -->
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">

            <div class="flex justify-between items-center">
                <!-- Logo on Left -->
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">🎬</div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">MovieTix</h1>
                        <p class="text-white/80 text-sm">Your gateway to movies</p>
                    </div>
                </div>

                <!-- Navigation Menu (Center) -->
                <nav class="hidden md:flex items-center space-x-12">
                    <a href="index.php"
                        class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                        <i class="fas fa-home group-hover:scale-110 transition-transform duration-300"></i>
                        <span>Home</span>
                    </a>
                    <a href="about.php"
                        class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                        <i class="fas fa-info-circle group-hover:scale-110 transition-transform duration-300"></i>
                        <span>About Us</span>
                    </a>
                    <a href="contact.php"
                        class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                        <i class="fas fa-envelope group-hover:scale-110 transition-transform duration-300"></i>
                        <span>Contact Us</span>
                    </a>
                    <!-- <a href="mybookings.php"
                        class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                        <i class="fas fa-calendar-check group-hover:scale-110 transition-transform duration-300"></i>
                        <span>My Bookings</span>
                    </a> -->
                </nav>

                <!-- Auth Buttons on Right -->
                <div class="flex items-center space-x-4">

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php"
                            class="hidden md:flex bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 items-center space-x-2">
                            <i class="fas fa-cog"></i>
                            <span>Admin</span>
                        </a>
                    <?php endif; ?>
                    <!-- Desktop Auth Buttons -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="hidden md:flex text-white/80 items-center space-x-2">
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

                        </span>

                        <!-- <a href="logout.php"
                            class="hidden md:flex bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 items-center space-x-2">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a> -->
                    <?php else: ?>
                        <a href="auth.php"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                            <i class="fas fa-user"></i>
                            <span>Sign In</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </header>


    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section Title -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-4">🎭 Now Showing - Today</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full"></div>
        </div>

        <div class="container main-content">
            <div class="container mx-auto px-4 py-8">
                <!-- Error Message -->
                <?php if (!empty($error_message)): ?>
                    <div class="max-w-4xl mx-auto mb-8 animate-slide-up">
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-3 text-red-500"></i>
                                <div>
                                    <p class="font-medium">Oops! Something went wrong</p>
                                    <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Enhanced Search Section -->
                <div class="max-w-4xl mx-auto mb-12">
                    <!-- Search Form Container -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-6 shadow-2xl">
                        <form action="index.php" method="GET" class="relative">
                            <div class="flex flex-col md:flex-row gap-4">
                                <!-- Search Input with Icon -->
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-white/50 text-lg"></i>
                                    </div>
                                    <input type="text" name="search"
                                        class="w-full pl-12 pr-4 py-4 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 text-lg"
                                        placeholder="Search for movies playing today..."
                                        value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>" autocomplete="off">
                                    <!-- Search Suggestions Dropdown (Optional) -->
                                    <div id="searchSuggestions"
                                        class="absolute top-full left-0 right-0 mt-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl shadow-xl hidden z-10">
                                        <!-- Suggestions will be populated here via JavaScript -->
                                    </div>
                                </div>

                                <!-- Search Button -->
                                <button type="submit"
                                    class="bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold px-8 py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2 min-w-[140px]">
                                    <i class="fas fa-search text-lg"></i>
                                    <span class="hidden md:inline">Search</span>
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Search Stats/Info -->
                    <?php if (isset($searchTerm) && !empty($searchTerm)): ?>
                        <div class="text-center mt-6">
                            <p class="text-white/70">
                                <i class="fas fa-search mr-2"></i>
                                Searching for: <span
                                    class="text-purple-400 font-medium">"<?php echo htmlspecialchars($searchTerm); ?>"</span>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <style>
                @keyframes slideUp {
                    from {
                        transform: translateY(30px);
                        opacity: 0;
                    }

                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                .animate-slide-up {
                    animation: slideUp 0.6s ease-out;
                }

                /* Custom focus glow effect */
                input:focus {
                    box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
                }
            </style>
            <!-- Movies Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-4 md:gap-6">

                <?php foreach ($movies as $movie): ?>
                    <div
                        class="group relative bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:scale-105">
                        <!-- Movie Poster -->
                        <div class="aspect-[3/4] overflow-hidden relative">
                            <img src="<?= htmlspecialchars($movie['image_url']) ?>"
                                alt="<?= htmlspecialchars($movie['title']) ?>" class="w-full h-full object-cover">

                            <!-- Admin Actions Overlay -->
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <div class="absolute top-2 right-2 flex space-x-2">
                                    <a href="manage_movie.php?id=<?= $movie['id'] ?>"
                                        class="bg-blue-500 hover:bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form method="post" action="delete_movie.php" class="inline"
                                        onsubmit="return confirm('Delete this movie?')">
                                        <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                        <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Movie Info Section -->
                        <div class="p-4 bg-gradient-to-b from-purple-900 to-indigo-900 text-white">
                            <!-- Movie Title -->
                            <h3 class="font-bold text-lg mb-2 text-center truncate"><?= htmlspecialchars($movie['title']) ?>
                            </h3>

                            <!-- Movie Details -->
                            <div class="space-y-2 text-sm">
                                <!-- Genre and Duration -->
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-film mr-1 text-yellow-400"></i>
                                        <?= htmlspecialchars($movie['genre']) ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-clock mr-1 text-blue-400"></i>
                                        <?= htmlspecialchars($movie['duration']) ?>min
                                    </span>
                                </div>

                                <!-- Rating and Price -->
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-star mr-1 text-yellow-400"></i>
                                        <span
                                            class="text-yellow-400 font-medium"><?= htmlspecialchars($movie['rating']) ?></span>
                                    </span>
                                    <!-- <span class="text-green-400 font-bold text-lg">₹<?= number_format($movie['price']) ?>.00</span> -->
                                </div>
                            </div>

                            <!-- Book Now Button -->
                            <button
                                onclick="checkLoginAndBook(<?= $movie['id'] ?>, '<?= htmlspecialchars($movie['title']) ?>', '<?= htmlspecialchars($movie['image_url']) ?>')" 
                                class="w-full mt-4 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white py-3 px-4 rounded-xl font-bold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                <i class="fas fa-video mr-2"></i>Book Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>



            <?php if (empty($movies)): ?>
                <div class="text-center py-16">
                    <div class="text-white/50 text-6xl mb-4">🎭</div>
                    <h3 class="text-white text-2xl font-bold mb-2">No Shows Today</h3>
                    <p class="text-white/70">Check back tomorrow for new showtimes!</p>
                </div>
            <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-black/20 backdrop-blur-md border-t border-white/20 py-8 mt-16">
        <div class="container mx-auto px-4 ">
            <div class="text-white/60">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-6">
                    <!-- About Section -->
                    <div>
                        <h3 class="text-xl font-bold mb-3 flex items-center">
                            <span class="text-2xl mr-2">🎬</span> MovieTix
                        </h3>
                        <p class="text-gray-400 text-sm">
                            Your ultimate destination for booking movie tickets online.
                            Experience cinema like never before.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-lg font-semibold mb-3">Quick Links</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="guide.php">Booking Guide</a></li>
                            <li>Privacy Policy</li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h4 class="text-lg font-semibold mb-3">Contact Info</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><i class="fas fa-envelope mr-2"></i> support@movietix.com</li>
                            <li><i class="fas fa-phone mr-2"></i> +91 1234567890</li>
                            <li><i class="fas fa-map-marker-alt mr-2"></i> Surat, Gujarat, India</li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="border-t border-gray-700 pt-6 text-center">
                    <p class="text-gray-400 text-sm">
                        &copy; <?= date('Y') ?> MovieTix. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>


    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden ">
        <div class="flex items-center justify-center min-h-screen p-4 ">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto [scrollbar-width:none]">
                <!-- Modal Header -->
                <div
                    class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold">Book Tickets</h2>
                        <button onclick="closeBookingModal()" class="text-white hover:text-gray-200 text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form method="post" id="bookingForm" class="p-6">
                    <input type="hidden" name="book_tickets" value="1">
                    <input type="hidden" name="showtime_id" id="selectedShowtimeId">

                    <!-- Movie Info -->
                    <div class="flex items-center space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <img id="modalMoviePoster" src="" alt="" class="w-16 h-24 object-cover rounded">
                        <div>
                            <h3 id="modalMovieTitle" class="font-bold text-lg"></h3>
                            <p id="modalMoviePrice" class="text-green-600 font-medium"></p>
                        </div>
                    </div>

                    <!-- Show Times (Today Only) -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-3">Select Show Time (Today): *</label>
                        <div id="showtimesContainer" class="grid grid-cols-2 gap-2"></div>
                    </div>

                    <!-- Customer Details -->
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Full Name *</label>
                            <input type="text" name="customer_name" required
                                value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Email *</label>
                            <input type="email" name="customer_email" required readonly
                                value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '' ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Using your account email
                            </p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Phone *</label>
                            <input type="tel" name="customer_phone" required pattern="[0-9]{10} " maxlength="10"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="10-digit mobile number" oninput="FormatNumber(this);clearPhoneError();">
                        </div>
                    </div>

                    <!-- Seat Selection -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Number of Tickets: *</label>
                        <div class="flex items-center space-x-4">
                            <input type="number" name="num_tickets" id="numTickets" min="1" max="10" value="1" required
                                class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center"
                                oninput="validateTickets(this)">
                            <button type="button" onclick="openSeatSelection()"
                                class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2">
                                <i class="fas fa-couch"></i>
                                <span>Select Seats</span>
                            </button>
                        </div>
                        <input type="hidden" name="seat_numbers" id="selectedSeats">
                        <div id="selectedSeatsDisplay" class="mt-2 text-sm text-gray-600"></div>
                    </div>

                    <!-- Submit Button -->
                    <button type="button" onclick="validateAndSubmit()" id="bookTicketsBtn"
                        class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-credit-card mr-2"></i>Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Seat Selection Modal -->
    <div id="seatModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto [scrollbar-width:none]">
                <!-- Seat Modal Header -->
                <div
                    class="bg-gradient-to-r from-purple-500 to-pink-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold">Select Your Seats</h2>
                        <button onclick="closeSeatModal()" class="text-white hover:text-gray-200 text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Seat Map -->
                <div class="p-6">
                    <div class="text-center mb-8">
                        <div class="bg-gray-800 text-white py-2 px-8 rounded-lg inline-block mb-4">
                            🎬 SCREEN 🎬
                        </div>
                    </div>

                    <div id="seatMapContainer" class="flex flex-col items-center space-y-2 mb-6"></div>

                    <!-- Seat Legend -->
                    <div class="flex justify-center space-x-6 mb-6 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-gray-300 rounded"></div>
                            <span>Available</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded"></div>
                            <span>Selected</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-gray-700 rounded"></div>
                            <span>Booked</span>
                        </div>
                    </div>

                    <!-- Selected Seats Info -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <p class="text-center">
                            Selected Seats: <span id="selectedSeatsList" class="font-bold text-purple-600">None</span>
                        </p>
                    </div>

                    <!-- Confirm Button -->
                    <button onclick="confirmSeatSelection()"
                        class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white py-3 px-6 rounded-lg font-medium transition-all duration-300">
                        <i class="fas fa-check-circle mr-2"></i>Confirm Seat Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentMovieId = null;
        let currentMoviePrice = 0;
        let selectedSeats = [];

        function openBookingModal(movieId, title, imageUrl, price) {
            currentMovieId = movieId;
            currentMoviePrice = price;

            document.getElementById('modalMovieTitle').textContent = title;
            document.getElementById('modalMoviePoster').src = imageUrl;
            document.getElementById('modalMoviePrice').textContent = `₹${price} per ticket`;

            // Load today's showtimes only
            loadTodaysShowtimes(movieId);
            document.getElementById('bookingModal').classList.remove('hidden');
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function loadTodaysShowtimes(movieId) {
            fetch(`api.php?action=get_showtimes&movie_id=${movieId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('showtimesContainer');
                    if (data.success && data.showtimes.length > 0) {
                        container.innerHTML = data.showtimes.map(showtime => `
                            <button type="button" onclick="selectShowtime(${showtime.id}, '${showtime.show_time}', ${showtime.available_seats}, this)"
                                    class="p-3 border border-gray-300 rounded-lg hover:border-blue-500 transition-colors bg-white ${showtime.available_seats === 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                                    ${showtime.available_seats === 0 ? 'disabled' : ''}>
                                <div class="font-medium">${showtime.show_time}</div>
                                <div class="text-sm text-gray-600">${showtime.available_seats} seats</div>
                            </button>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500 col-span-2">No showtimes available today</p>';
                    }
                });
        }

        function checkLoginAndBook(movieId, movieTitle, movieImage) {
            <?php if (isset($_SESSION['user_id'])): ?>
                // User is logged in - open booking modal
                openBookingModal(movieId, movieTitle, movieImage);
            <?php else: ?>
                // User not logged in - show alert and redirect
                if (confirm('You need to sign in to book tickets. Would you like to sign in now?')) {
                    window.location.href = 'auth.php';
                }
            <?php endif; ?>
        }


        function selectShowtime(showtimeId, time, availableSeats, button) {
            // Remove selection from all showtime buttons
            document.querySelectorAll('#showtimesContainer button').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                btn.classList.add('bg-white', 'border-gray-300');
            });

            // Select clicked showtime
            button.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            button.classList.remove('bg-white', 'border-gray-300');

            document.getElementById('selectedShowtimeId').value = showtimeId;
        }

        function openSeatSelection() {
            const showtimeId = document.getElementById('selectedShowtimeId').value;
            const numTickets = parseInt(document.getElementById('numTickets').value);

            if (!showtimeId) {
                alert('Please select a showtime first');
                return;
            }

            // Hide booking modal and show seat modal
            document.getElementById('bookingModal').classList.add('hidden');
            generateSeatMap();
            loadBookedSeats(showtimeId);
            document.getElementById('seatModal').classList.remove('hidden');
        }

        function closeSeatModal() {
            document.getElementById('seatModal').classList.add('hidden');
            // Show booking modal again
            document.getElementById('bookingModal').classList.remove('hidden');
        }

        function generateSeatMap() {
            const container = document.getElementById('seatMapContainer');
            const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            const seatsPerRow = 12;

            container.innerHTML = rows.map(row => `
                <div class="flex items-center space-x-2">
                    <div class="w-6 text-center font-bold text-gray-600">${row}</div>
                    <div class="flex space-x-1">
                        ${Array.from({ length: seatsPerRow }, (_, i) => {
                const seatNumber = i + 1;
                const seatId = `${row}${seatNumber}`;
                return `
                                <button type="button" onclick="toggleSeat('${seatId}')" 
                                        class="w-8 h-8 rounded seat-btn bg-gray-300 hover:bg-gray-400 transition-colors text-xs font-bold"
                                        data-seat="${seatId}">
                                    ${seatNumber}
                                </button>
                            `;
            }).join('')}
                    </div>
                    <div class="w-6 text-center font-bold text-gray-600">${row}</div>
                </div>
            `).join('');
        }

        function loadBookedSeats(showtimeId) {
            fetch(`api.php?action=get_booked_seats&showtime_id=${showtimeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mark booked seats
                        data.booked_seats.forEach(seat => {
                            const seatBtn = document.querySelector(`[data-seat="${seat}"]`);
                            if (seatBtn) {
                                seatBtn.classList.remove('bg-gray-300', 'hover:bg-gray-400');
                                seatBtn.classList.add('bg-gray-700', 'cursor-not-allowed', 'text-white');
                                seatBtn.disabled = true;
                            }
                        });
                    }
                });
        }

        function toggleSeat(seatId) {
            const seatBtn = document.querySelector(`[data-seat="${seatId}"]`);
            const numTickets = parseInt(document.getElementById('numTickets').value);

            if (seatBtn.disabled) return;

            if (selectedSeats.includes(seatId)) {
                // Deselect seat
                selectedSeats = selectedSeats.filter(s => s !== seatId);
                seatBtn.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-purple-600', 'text-white');
                seatBtn.classList.add('bg-gray-300', 'hover:bg-gray-400');
            } else {
                // Select seat
                if (selectedSeats.length < numTickets) {
                    selectedSeats.push(seatId);
                    seatBtn.classList.remove('bg-gray-300', 'hover:bg-gray-400');
                    seatBtn.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-purple-600', 'text-white');
                } else {
                    alert(`You can only select ${numTickets} seat(s)`);
                }
            }

            updateSelectedSeatsDisplay();
        }

        function updateSelectedSeatsDisplay() {
            const display = document.getElementById('selectedSeatsList');
            display.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
        }

        function confirmSeatSelection() {
            const numTickets = parseInt(document.getElementById('numTickets').value);

            if (selectedSeats.length !== numTickets) {
                alert(`Please select exactly ${numTickets} seat(s)`);
                return;
            }

            document.getElementById('selectedSeats').value = selectedSeats.join(',');
            document.getElementById('selectedSeatsDisplay').innerHTML =
                `<strong>Selected Seats:</strong> ${selectedSeats.join(', ')}`;

            closeSeatModal();
        }

        // Update seat selection when number of tickets changes
        document.getElementById('numTickets').addEventListener('change', function () {
            selectedSeats = [];
            updateSelectedSeatsDisplay();
            document.getElementById('selectedSeats').value = '';
            document.getElementById('selectedSeatsDisplay').innerHTML = '';

            // Reset all seat buttons
            document.querySelectorAll('.seat-btn').forEach(btn => {
                if (!btn.disabled) {
                    btn.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-purple-600', 'text-white');
                    btn.classList.add('bg-gray-300', 'hover:bg-gray-400');
                }
            });
        });

        function FormatNumber(input) {
            let value = input.value.replace(/\D/g, '');
            input.value = value;
        }

        validateTickets = (input) => {
            let val = parseInt(input.value, 10);

            if (isNaN(val)) {
                input.value = '';
                return;
            }

            if (val < 1) {
                input.value = 1;
            } else if (val > 10) {
                alert("⚠️ You can only book up to 10 seats at a time.");
                input.value = 10;
            }
        }

        function validateAndSubmit() {
            const btn = document.getElementById('bookTicketsBtn');
            const form = document.querySelector('#bookingModal form');

            // prevent double click
            if (btn.dataset.submitting === '1') {
                return;
            }

            const numTickets = document.getElementById('numTickets').value;
            const selectedSeats = document.getElementById('selectedSeats').value;
            const customerName = document.querySelector('input[name="customer_name"]').value;
            const customerEmail = document.querySelector('input[name="customer_email"]').value;
            const customerPhone = document.querySelector('input[name="customer_phone"]').value;
            const showtimeId = document.getElementById('selectedShowtimeId').value;

            // Validate showtime selection
            if (!showtimeId) {
                alert('⚠️ Please select a show time!');
                return false;
            }

            // Validate customer details
            if (!customerName || customerName.trim() === '') {
                alert('⚠️ Please enter your full name!');
                return false;
            }

            if (!customerEmail || customerEmail.trim() === '') {
                alert('⚠️ Please enter your email!');
                return false;
            }

            if (!customerPhone || customerPhone.trim() === '') {
                alert('⚠️ Please enter your phone number!');
                return false;
            }

            // Validate phone number (10 digits)
            if (!/^[0-9]{10}$/.test(customerPhone)) {
                alert('⚠️ Please enter a valid 10-digit phone number!');
                return false;
            }

            if (/^(\d)\1{9}$/.test(customerPhone)) {
                alert('⚠️ Please enter a realistic phone number (not all same digits)!');
                return false;
            }

            // Validate seat selection
            if (!selectedSeats || selectedSeats.trim() === '') {
                alert('🎫 Please select your seats first!\n\nClick on "Select Seats" button to choose your preferred seats.');
                return false;
            }

            // Count selected seats
            const selectedSeatsArray = selectedSeats.split(',').filter(seat => seat.trim() !== '');

            // Validate number of tickets matches selected seats
            if (selectedSeatsArray.length !== parseInt(numTickets)) {
                alert(`⚠️ Please select exactly ${numTickets} seat(s)!\n\nYou have selected ${selectedSeatsArray.length} seat(s).`);
                return false;
            }

            // If all validations pass, submit the form    
            btn.dataset.submitting = '1';
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            form.submit();
        }

    </script>
</body>

</html>