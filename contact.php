<?php
session_start();

$success_message = '';
$error_message = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Here you would typically send the email or save to database
        // For now, we'll just show a success message
        $success_message = "Thank you for your message! We'll get back to you soon.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - MovieTix</title>
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
                    Get In <span class="bg-gradient-to-r from-cyan-400 to-purple-500 bg-clip-text text-transparent">Touch</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                    We'd love to hear from you! Whether you have a question, feedback, or need assistance, our team is ready to help.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div class="bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 overflow-hidden shadow-2xl animate-slide-up">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-8">
                        <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                            <i class="fas fa-envelope mr-3"></i>
                            Send us a Message
                        </h2>
                        <p class="text-purple-100">We'll get back to you within 24 hours</p>
                    </div>

                    <form method="post" class="p-8">
                        <input type="hidden" name="send_message" value="1">

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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-white font-medium mb-3">
                                    <i class="fas fa-user mr-2 text-cyan-400"></i>Your Name *
                                </label>
                                <input type="text" name="name" required 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block text-white font-medium mb-3">
                                    <i class="fas fa-envelope mr-2 text-purple-400"></i>Email Address *
                                </label>
                                <input type="email" name="email" required 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300"
                                       placeholder="john@example.com">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-white font-medium mb-3">
                                <i class="fas fa-tag mr-2 text-pink-400"></i>Subject *
                            </label>
                            <select name="subject" required 
                                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300">
                                <option value="">Select a subject</option>
                                <option value="booking_issue">Booking Issue</option>
                                <option value="payment_problem">Payment Problem</option>
                                <option value="general_inquiry">General Inquiry</option>
                                <option value="feedback">Feedback</option>
                                <option value="partnership">Partnership</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-8">
                            <label class="block text-white font-medium mb-3">
                                <i class="fas fa-comment mr-2 text-green-400"></i>Message *
                            </label>
                            <textarea name="message" required rows="5" 
                                      class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 resize-none"
                                      placeholder="Tell us how we can help you..."></textarea>
                        </div>

                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i>Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="space-y-8">
                    <!-- Contact Details Card -->
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 p-8 shadow-2xl animate-slide-up">
                        <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <i class="fas fa-map-marker-alt mr-3 text-red-400"></i>
                            Contact Information
                        </h3>

                        <div class="space-y-6">
                            <!-- Address -->
                            <div class="flex items-start space-x-4">
                                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full p-3 flex-shrink-0">
                                    <i class="fas fa-building text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-semibold mb-2">MovieTix Headquarters</h4>
                                    <p class="text-white/80">123 Cinema Lane, Movie City,<br>Surat, Gujarat, 395006</p>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-center space-x-4">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-full p-3 flex-shrink-0">
                                    <i class="fas fa-phone text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-semibold mb-1">Phone</h4>
                                    <a href="tel:+919876543210" class="text-emerald-400 hover:text-emerald-300 transition-colors">
                                        +91 98765 43210
                                    </a>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-center space-x-4">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-full p-3 flex-shrink-0">
                                    <i class="fas fa-envelope text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-semibold mb-1">Email</h4>
                                    <a href="mailto:support@movietix.com" class="text-purple-400 hover:text-purple-300 transition-colors">
                                        support@movietix.com
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 p-8 shadow-2xl animate-slide-up">
                        <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <i class="fas fa-clock mr-3 text-yellow-400"></i>
                            Business Hours
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-white/80">Monday - Friday</span>
                                <span class="text-white font-medium">9:00 AM - 6:00 PM</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/80">Saturday</span>
                                <span class="text-white font-medium">10:00 AM - 4:00 PM</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/80">Sunday</span>
                                <span class="text-red-400 font-medium">Closed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl border border-white/20 p-8 shadow-2xl animate-slide-up">
                        <h3 class="text-2xl font-bold text-white mb-6 flex items-center">
                            <i class="fas fa-link mr-3 text-cyan-400"></i>
                            Quick Links
                        </h3>
                        <div class="space-y-3">
                            <a href="about.php" class="block text-white/80 hover:text-cyan-400 transition-colors">
                                <i class="fas fa-info-circle mr-2"></i>About Us
                            </a>
                            <a href="index.php" class="block text-white/80 hover:text-cyan-400 transition-colors">
                                <i class="fas fa-film mr-2"></i>Browse Movies
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-black/20 backdrop-blur-md border-t border-white/20 py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <div class="text-white/60">
                <p class="mt-2 text-sm">We're here to help make your movie experience unforgettable!</p>
            </div>
        </div>
    </footer>

    <script>
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

        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>
