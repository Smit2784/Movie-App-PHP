<?php
session_start();
require_once 'admin_check.php';
require_once 'db.php';

$standard_genres = [
    'Action', 'Comedy', 'Drama', 'Horror', 'Romance', 
    'Sci-Fi', 'Thriller', 'Animation', 'Documentary', 
    'Biography', 'Musical', 'Adventure', 'Fantasy', 'Crime'
];

$dbGenresStmt = $pdo->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != ''");
$db_genres = $dbGenresStmt->fetchAll(PDO::FETCH_COLUMN, 0);

$all_genres = array_unique(array_merge($standard_genres, $db_genres));
sort($all_genres);

$error_message = '';
$success_message = '';

// Initialize movie data
$movie = [
    'id' => '',
    'title' => '',
    'genre' => '',
    'duration' => '',
    'rating' => '',
    'price' => '',
    'image_url' => '',
    'description' => ''
];

$page_title = "Add New Movie";
$action = "add_movie";

// Check if an ID is provided for editing
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($movie) {
        $page_title = "Edit Movie";
        $action = "edit_movie";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $movie_id = $_POST['movie_id'] ?? null;
    $title = trim($_POST['title']);
    // ... (get all other form fields)
    $genre = $_POST['genre'];
    $duration = $_POST['duration'];
    $rating = $_POST['rating'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];
    $description = $_POST['description'];
    $language = $_POST['language'] ?? null;
    $director = $_POST['director'] ?? null;
    $cast = $_POST['cast'] ?? null;
    $release_date = $_POST['release_date'] ?? null;

    try {
        // Start a database transaction
        $pdo->beginTransaction();

        if ($action === 'add_movie') {
            // 1. INSERT the new movie into the 'movies' table
            $sql = "INSERT INTO movies (title, genre, duration, rating, price, image_url, description, language, director, cast, release_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $genre, $duration, $rating, $price, $image_url, $description, $language, $director, $cast, $release_date]);
            
            // --- NEW AUTOMATIC SHOWTIME GENERATION ---
            $new_movie_id = $pdo->lastInsertId();

            if ($new_movie_id) {
                // Define the default schedule parameters
                $numberOfDays = 15;
                $showsPerDay = ['10:00:00', '13:15:00', '16:30:00', '19:45:00', '23:00:00'];
                $totalSeats = 100;
                $startDate = new DateTime('now', new DateTimeZone('Asia/Kolkata')); // Use your local timezone

                $showtimeStmt = $pdo->prepare(
                    "INSERT INTO showtimes (movie_id, show_date, show_time, total_seats, available_seats) VALUES (?, ?, ?, ?, ?)"
                );

                // Loop for 15 days and insert 5 showtimes for each day
                for ($i = 0; $i < $numberOfDays; $i++) {
                    $currentDateStr = $startDate->format('Y-m-d');
                    foreach ($showsPerDay as $time) {
                        $showtimeStmt->execute([$new_movie_id, $currentDateStr, $time, $totalSeats, $totalSeats]);
                    }
                    $startDate->modify('+1 day');
                }
            }
            // --- END OF NEW LOGIC ---

        } elseif ($action === 'edit_movie' && $movie_id) {
            // UPDATE logic for editing a movie (this remains unchanged)
            $sql = "UPDATE movies SET title=?, genre=?, duration=?, rating=?, price=?, image_url=?, description=?, language=?, director=?, cast=?, release_date=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $genre, $duration, $rating, $price, $image_url, $description, $language, $director, $cast, $release_date, $movie_id]);
        }
        
        // If everything was successful, commit the transaction
        $pdo->commit();
        
        // Redirect back to the admin dashboard
        header("Location: admin_dashboard.php");
        exit();

    } catch (PDOException $e) {
        // If anything went wrong, roll back the transaction
        $pdo->rollBack();
        $error_message = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - MovieTix Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-purple-900 to-indigo-900 min-h-screen">
    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">🎬</div>
                    <div>
                        <h1 class="text-3xl font-bold text-white"><?= $page_title ?></h1>
                        <p class="text-white/70">Manage your movie catalog</p>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex items-center space-x-4">
                    <a href="admin_dashboard.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Alert Messages -->
            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?= htmlspecialchars($success_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Movie Form -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden shadow-2xl">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-film mr-3"></i>
                        Movie Information
                    </h2>
                    <p class="text-white/80 mt-1">Fill in the details below to <?= isset($_GET['id']) ? 'update' : 'add' ?> the movie</p>
                </div>

                <!-- Form Body -->
                <form method="post" class="p-8">
                    <input type="hidden" name="action" value="<?= $action ?>">
                    <?php if (isset($_GET['id'])): ?>
                        <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']) ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Movie Title -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-film mr-2 text-blue-400"></i>Movie Title *
                                </label>
                                <input type="text" name="title" value="<?= htmlspecialchars($movie['title']) ?>" required 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="Enter movie title">
                            </div>

                            <!-- Genre -->
                            <div>
    <label class="block text-white font-semibold mb-3">
        <i class="fas fa-tags mr-2 text-purple-400"></i>Genre *
    </label>
    <select name="genre" required 
            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300">
        <option value="">Select Genre</option>
        
        <?php foreach ($all_genres as $genre_option): ?>
            <option value="<?php echo htmlspecialchars($genre_option); ?>" 
                    <?php if ($movie['genre'] === $genre_option) echo 'selected'; ?>>
                <?php echo htmlspecialchars($genre_option); ?>
            </option>
        <?php endforeach; ?>

    </select>
</div>

                            <!-- Duration -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-clock mr-2 text-green-400"></i>Duration (minutes) *
                                </label>
                                <input type="number" name="duration" value="<?= htmlspecialchars($movie['duration']) ?>" required min="1" max="500"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="e.g., 120">
                            </div>

                            <!-- Rating -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-star mr-2 text-yellow-400"></i>Rating (1-10) *
                                </label>
                                <input type="number" name="rating" value="<?= htmlspecialchars($movie['rating']) ?>" required min="1" max="10" step="0.1"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="e.g., 8.5">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Price -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-rupee-sign mr-2 text-emerald-400"></i>Ticket Price (₹) *
                                </label>
                                <input type="number" name="price" value="<?= htmlspecialchars($movie['price']) ?>" required min="1" step="0.01"
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="e.g., 250.00">
                            </div>

                            <!-- Image URL -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-image mr-2 text-pink-400"></i>Movie Poster URL *
                                </label>
                                <input type="url" name="image_url" value="<?= htmlspecialchars($movie['image_url']) ?>" required 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="https://example.com/movie-poster.jpg">
                            </div>

                            <!-- Image Preview -->
                            <div>
                                <label class="block text-white font-semibold mb-3">
                                    <i class="fas fa-eye mr-2 text-cyan-400"></i>Poster Preview
                                </label>
                                <div class="bg-white/5 border border-white/20 rounded-xl p-4 min-h-[200px] flex items-center justify-center">
                                    <img id="posterPreview" src="<?= htmlspecialchars($movie['image_url']) ?>" alt="Poster Preview" 
                                         class="max-w-full max-h-48 rounded-lg shadow-lg <?= empty($movie['image_url']) ? 'hidden' : '' ?>">
                                    <div id="noPreview" class="text-white/50 text-center <?= !empty($movie['image_url']) ? 'hidden' : '' ?>">
                                        <i class="fas fa-image text-4xl mb-2"></i>
                                        <p>Poster preview will appear here</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description (Full Width) -->
                    <div class="mt-8">
                        <label class="block text-white font-semibold mb-3">
                            <i class="fas fa-align-left mr-2 text-orange-400"></i>Movie Description
                        </label>
                        <textarea name="description" rows="4" 
                                  class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 resize-none"
                                  placeholder="Enter a brief description of the movie..."><?= htmlspecialchars($movie['description']) ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-8 pt-6 border-t border-white/20">
                        <button type="submit" 
                                class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-save"></i>
                            <span><?= isset($_GET['id']) ? 'Update Movie' : 'Add Movie' ?></span>
                        </button>
                        
                        <a href="admin_dashboard.php" 
                           class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Image preview functionality
        document.querySelector('input[name="image_url"]').addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('posterPreview');
            const noPreview = document.getElementById('noPreview');
            
            if (url) {
                preview.src = url;
                preview.classList.remove('hidden');
                noPreview.classList.add('hidden');
                
                // Handle image load error
                preview.onerror = function() {
                    this.classList.add('hidden');
                    noPreview.classList.remove('hidden');
                };
            } else {
                preview.classList.add('hidden');
                noPreview.classList.remove('hidden');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Auto-hide success message
        <?php if ($success_message): ?>
        setTimeout(function() {
            const successAlert = document.querySelector('.bg-green-100');
            if (successAlert) {
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 300);
            }
        }, 5000);
        <?php endif; ?>
    </script>

    <style>
        /* Custom select styling */
        select option {
            background-color: #1f2937;
            color: white;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</body>
</html>
