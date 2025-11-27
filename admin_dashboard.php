<?php
session_start();
require_once 'admin_check.php';
require_once 'db.php';

// Get all movies
$movies = $pdo->query("SELECT * FROM movies ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MovieTix</title>
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
                        <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
                        <p class="text-white/70">Manage your movie theater</p>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex items-center space-x-4">
                    <span class="text-white/80">Welcome, <span class="font-semibold text-white"><?= htmlspecialchars($_SESSION['username']) ?></span></span>
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-home"></i>
                        <span>View Site</span>
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">Manage Movies</h2>
                <p class="text-white/70">Add, edit, or remove movies from your catalog</p>
            </div>
            <a href="manage_movie.php" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 transform hover:scale-105 shadow-lg flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>Add New Movie</span>
            </a>
        </div>

        <!-- Movies Table -->
        <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 overflow-hidden shadow-2xl">
            <?php if (empty($movies)): ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="text-white/50 text-6xl mb-4">🎬</div>
                    <h3 class="text-white text-2xl font-bold mb-2">No Movies Added</h3>
                    <p class="text-white/70 mb-6">Start by adding your first movie to the catalog</p>
                    <a href="manage_movie.php" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-2"></i>Add Your First Movie
                    </a>
                </div>
            <?php else: ?>
                <!-- Table Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <div class="grid grid-cols-12 gap-4 font-bold text-white">
                        <div class="col-span-3">
                            <i class="fas fa-film mr-2"></i>Title
                        </div>
                        <div class="col-span-2">
                            <i class="fas fa-tags mr-2"></i>Genre
                        </div>
                        <div class="col-span-2">
                            <i class="fas fa-clock mr-2"></i>Duration
                        </div>
                        <div class="col-span-1">
                            <i class="fas fa-star mr-2"></i>Rating
                        </div>
                        <div class="col-span-2">
                            <i class="fas fa-rupee-sign mr-2"></i>Price
                        </div>
                        <div class="col-span-2 text-center">
                            <i class="fas fa-cogs mr-2"></i>Actions
                        </div>
                    </div>
                </div>

                <!-- Table Body -->
                <div class="divide-y divide-white/10">
                    <?php foreach ($movies as $movie): ?>
                        <div class="px-6 py-4 hover:bg-white/5 transition-colors duration-200">
                            <div class="grid grid-cols-12 gap-4 items-center">
                                <!-- Movie Title with Poster -->
                                <div class="col-span-3 flex items-center space-x-3">
                                    <img src="<?= htmlspecialchars($movie['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($movie['title']) ?>"
                                         class="w-12 h-16 object-cover rounded-lg shadow-md">
                                    <div>
                                        <h3 class="text-white font-semibold"><?= htmlspecialchars($movie['title']) ?></h3>
                                        <p class="text-white/60 text-sm truncate"><?= htmlspecialchars(substr($movie['description'], 0, 50)) ?>...</p>
                                    </div>
                                </div>

                                <!-- Genre -->
                                <div class="col-span-2">
                                    <span class="bg-purple-500/20 text-purple-200 px-3 py-1 rounded-full text-sm font-medium">
                                        <?= htmlspecialchars($movie['genre']) ?>
                                    </span>
                                </div>

                                <!-- Duration -->
                                <div class="col-span-2 text-white/80">
                                    <i class="fas fa-clock mr-2 text-blue-400"></i>
                                    <?= htmlspecialchars($movie['duration']) ?> min
                                </div>

                                <!-- Rating -->
                                <div class="col-span-1 text-white/80">
                                    <div class="flex items-center">
                                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                                        <span class="font-semibold"><?= htmlspecialchars($movie['rating']) ?></span>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="col-span-2 text-white/80">
                                    <span class="text-green-400 font-bold text-lg">₹<?= number_format($movie['price']) ?></span>
                                </div>

                                <!-- Actions -->
                                <div class="col-span-2 flex justify-center space-x-2">
                                    <!-- Edit Button -->
                                    <a href="manage_movie.php?id=<?= $movie['id'] ?>" 
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 transform hover:scale-105 shadow-md">
                                        <i class="fas fa-edit"></i>
                                        <span class="hidden sm:inline">Edit</span>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="post" action="delete_movie.php" class="inline" onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($movie['title'])) ?>')">
                                        <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                                        <button type="submit" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2 transform hover:scale-105 shadow-md">
                                            <i class="fas fa-trash"></i>
                                            <span class="hidden sm:inline">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Table Footer -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                    <div class="flex justify-between items-center text-white/70">
                        <span>Total Movies: <span class="font-bold text-white"><?= count($movies) ?></span></span>
                        <span>Last Updated: <?= date('M j, Y g:i A') ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

       
    </main>

    <script>
        function confirmDelete(movieTitle) {
            return confirm(`Are you sure you want to delete "${movieTitle}"? This action cannot be undone.`);
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on page load
            const stats = document.querySelectorAll('.grid .text-2xl');
            stats.forEach((stat, index) => {
                setTimeout(() => {
                    stat.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        stat.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });
        });
    </script>

    <style>
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
