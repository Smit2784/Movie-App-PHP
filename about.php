<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.8s ease-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'float': 'float 3s ease-in-out infinite',
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
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
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
                <a href="index.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                    <i class="fas fa-home group-hover:scale-110 transition-transform duration-300"></i>
                    <span>Home</span>
                </a>
                <a href="about.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                    <i class="fas fa-info-circle group-hover:scale-110 transition-transform duration-300"></i>
                    <span>About Us</span>
                </a>
                <a href="contact.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-2 group">
                    <i class="fas fa-envelope group-hover:scale-110 transition-transform duration-300"></i>
                    <span>Contact Us</span>
                </a>
            </nav>
            
            <!-- Auth Buttons on Right -->
            <div class="flex items-center space-x-4">
                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="md:hidden text-white p-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Desktop Auth Buttons -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="hidden md:flex text-white/80 items-center space-x-2">
                        <a href="profile.php" class="bg-white/10 hover:bg-white/20 text-white font-semibold px-3 py-1 rounded-full transition-all duration-300 transform hover:scale-105">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                    </span>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="hidden md:flex bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 items-center space-x-2">
                            <i class="fas fa-cog"></i>
                            <span>Admin</span>
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="hidden md:flex bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="auth.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 flex items-center space-x-2">
                        <i class="fas fa-user"></i>
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobileMenu" class="md:hidden hidden mt-4 pb-4 border-t border-white/20 pt-4">
            <div class="flex flex-col space-y-3">
                <a href="index.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="about.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-info-circle"></i>
                    <span>About Us</span>
                </a>
                <a href="contact.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Us</span>
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="border-t border-white/20 pt-3 mt-3">
                        <a href="profile.php" class="text-white/80 hover:text-white font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                            <i class="fas fa-user"></i>
                            <span>Profile (<?= htmlspecialchars($_SESSION['username']) ?>)</span>
                        </a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin_dashboard.php" class="text-yellow-400 hover:text-yellow-300 font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                                <i class="fas fa-cog"></i>
                                <span>Admin Dashboard</span>
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="text-red-400 hover:text-red-300 font-medium transition-colors duration-300 flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>


    <!-- Hero Section -->
    <section class="py-16 px-4">
        <div class="container mx-auto text-center">
            <div class="animate-fade-in">
                <h1 class="text-5xl md:text-7xl font-bold text-white mb-6">
                    About <span class="bg-gradient-to-r from-cyan-400 to-purple-500 bg-clip-text text-transparent">MovieTix</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                    Your ultimate destination for a seamless movie booking experience. We're passionate about bringing the magic of cinema to you.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12">
        <!-- Our Story Section -->
        <div class="max-w-6xl mx-auto mb-16">
            <div class="bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 overflow-hidden shadow-2xl animate-slide-up">
                <div class="lg:flex items-center">
                    <!-- Story Content -->
                    <div class="lg:w-1/2 p-8 lg:p-12">
                        <div class="flex items-center mb-6">
                            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full p-3 mr-4">
                                <i class="fas fa-book-open text-white text-2xl"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-white">Our Story</h2>
                        </div>
                        <p class="text-white/90 text-lg leading-relaxed mb-6">
                            Founded in <span class="font-bold text-cyan-400">2024</span> in <span class="font-bold text-purple-400">Surat, Gujarat</span>, MovieTix started with a simple idea: to make movie booking easy, fast, and enjoyable.
                        </p>
                        <p class="text-white/80 leading-relaxed">
                            We noticed the hassle people went through to book tickets and decided to create a platform that would eliminate the queues and bring the box office to your fingertips. From a small passion project, we have grown into a trusted platform for movie lovers across the region.
                        </p>
                    </div>
                    
                    <!-- Story Visual -->
                    <div class="lg:w-1/2 p-8 lg:p-12">
                        <div class="bg-gradient-to-br from-purple-500/20 to-cyan-500/20 rounded-2xl p-8 text-center">
                            <div class="text-6xl mb-6 animate-float">🎭</div>
                            <div class="grid grid-cols-2 gap-4 text-white">
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-3xl font-bold text-cyan-400">2024</div>
                                    <div class="text-sm">Founded</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-3xl font-bold text-purple-400">Surat</div>
                                    <div class="text-sm">Based In</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

     <!-- Features Section -->
        <div class="max-w-6xl mx-auto mb-16">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-white mb-4">Why Choose MovieTix?</h2>
                <div class="w-24 h-1 bg-gradient-to-r from-cyan-400 to-purple-500 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-6 text-center shadow-xl hover:transform hover:scale-105 transition-all duration-300">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full p-4 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-lightning-bolt text-white text-xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3">Lightning Fast</h4>
                    <p class="text-white/80">Book your tickets in seconds with our streamlined booking process.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-6 text-center shadow-xl hover:transform hover:scale-105 transition-all duration-300">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-full p-4 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3">Secure Payments</h4>
                    <p class="text-white/80">Your transactions are protected with industry-standard encryption.</p>
                </div>

                <!-- Feature 3 -->
                <!-- <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-6 text-center shadow-xl hover:transform hover:scale-105 transition-all duration-300">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-full p-4 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-white text-xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-3">Mobile Friendly</h4>
                    <p class="text-white/80">Book on-the-go with our responsive mobile-optimized platform.</p>
                </div> -->
            </div>
        </div>
    </main>

    <!-- CTA Section -->
    <section class="py-16 px-4">
        <div class="container mx-auto text-center">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-4xl font-bold text-white mb-6">Ready to Book Your Next Movie?</h2>
                <p class="text-xl text-white/80 mb-8">Join thousands of movie lovers who trust MovieTix for their cinema experience.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="index.php" class="bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-ticket-alt mr-2"></i>Browse Movies
                    </a>
                    <a href="contact.php" class="bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-envelope mr-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black/20 backdrop-blur-md border-t border-white/20 py-8">
        <div class="container mx-auto px-4 text-center">
        </div>
    </footer>
</body>
</html>
