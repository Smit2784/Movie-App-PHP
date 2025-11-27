<?php
session_start();
require_once 'db.php';

$error_message = '';
$success_message = '';

// Determine action (signin or signup)
$action = isset($_POST['action']) ? $_POST['action'] : 'signin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- SIGN UP LOGIC ---
    if ($action === 'signup') {
        $username = trim($_POST['username']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($username) || !$email || empty($password)) {
            $error_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error_message = "Username or email already taken.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // Insert the new user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $success_message = "Registration successful! You can now sign in.";
                    } else {
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- SIGN IN LOGIC ---
    if ($action === 'signin') {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];

        if (!$email || empty($password)) {
            $error_message = "Please enter both email and password.";
        } else {
            try {
                // Fetch user from the database
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password
                if ($user && password_verify($password, $user['password'])) {
                    // Password is correct, start a session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: index.php"); // Redirect to the main page
                    exit();
                } else {
                    $error_message = "Invalid email or password.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieTix - Sign In / Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
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
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8 animate-fade-in">
            <h1 class="text-4xl font-bold text-white mb-2">🎬 MovieTix</h1>
            <p class="text-white/80">Your gateway to the latest movies</p>
        </div>

        <!-- Auth Container -->
        <div class="bg-white/10 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 animate-slide-up">
            <!-- Tab Navigation -->
            <div class="flex rounded-t-2xl overflow-hidden">
                <button onclick="showSignIn()" id="signinTab" 
                        class="flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white text-gray-800">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
                <button onclick="showSignUp()" id="signupTab" 
                        class="flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white/20 text-white hover:bg-white/30">
                    <i class="fas fa-user-plus mr-2"></i>Sign Up
                </button>
            </div>

            <!-- Alert Messages -->
            <?php if ($error_message): ?>
                <div class="mx-6 mt-6 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="mx-6 mt-6 p-4 bg-green-100 border border-green-300 text-green-700 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?= htmlspecialchars($success_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sign In Form -->
            <form method="post" id="signinForm" class="p-6">
                <input type="hidden" name="action" value="signin">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300"
                               placeholder="Enter your email">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="signinPassword" required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300 pr-12"
                                   placeholder="Enter your password">
                            <button type="button" onclick="togglePassword('signinPassword', this)" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full mt-6 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Sign Up Form -->
            <form method="post" id="signupForm" class="p-6 hidden">
                <input type="hidden" name="action" value="signup">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-user mr-2"></i>Username
                        </label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300"
                               placeholder="Choose a username">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300"
                               placeholder="Enter your email">
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="signupPassword" required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300 pr-12"
                                   placeholder="Create a password" minlength="6">
                            <button type="button" onclick="togglePassword('signupPassword', this)" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="text-white/60 text-sm mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div>
                        <label class="block text-white font-medium mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirm Password
                        </label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirmPassword" required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-300 pr-12"
                                   placeholder="Confirm your password">
                            <button type="button" onclick="togglePassword('confirmPassword', this)" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full mt-6 bg-gradient-to-r from-green-500 to-blue-600 hover:from-green-600 hover:to-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i>Sign Up
                </button>
            </form>

            <!-- Footer -->
            <div class="px-6 pb-6 text-center">
                <a href="index.php" class="text-white/70 hover:text-white transition-colors duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="text-center mt-6 text-white/60">
            <p class="text-sm">Secure authentication powered by MovieTix</p>
        </div>
    </div>

    <script>
        function showSignIn() {
            document.getElementById('signinForm').classList.remove('hidden');
            document.getElementById('signupForm').classList.add('hidden');
            
            document.getElementById('signinTab').className = 'flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white text-gray-800';
            document.getElementById('signupTab').className = 'flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white/20 text-white hover:bg-white/30';
        }

        function showSignUp() {
            document.getElementById('signinForm').classList.add('hidden');
            document.getElementById('signupForm').classList.remove('hidden');
            
            document.getElementById('signinTab').className = 'flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white/20 text-white hover:bg-white/30';
            document.getElementById('signupTab').className = 'flex-1 py-4 px-6 font-medium transition-all duration-300 bg-white text-gray-800';
        }

        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Password confirmation validation
        document.getElementById("confirmPassword").addEventListener('input', function() {
            const password = document.querySelector('input[name="password"]').value.trim();
            const confirmPassword = this.value.trim();
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('border-red-400');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-400');
            }
        });

        // Show appropriate form based on URL or action
        <?php if ($action === 'signup' || $success_message): ?>
            showSignUp();
        <?php endif; ?>
    </script>
</body>
</html>
