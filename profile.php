<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

require_once 'db.php';

$error_message = '';
$success_message = '';
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // --- Logic for updating username ---
    if ($action === 'update_username') {
        $new_username = trim($_POST['new_username']);

        if (empty($new_username)) {
            $error_message = "Username cannot be empty.";
        } elseif (strlen($new_username) < 3) {
            $error_message = "Username must be at least 3 characters long.";
        } else {
            try {
                // Check if the new username is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$new_username, $user_id]);
                if ($stmt->fetch()) {
                    $error_message = "This username is already taken. Please choose another.";
                } else {
                    // Update the username in the database
                    $updateStmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $updateStmt->execute([$new_username, $user_id]);

                    // Update the username in the session so it reflects immediately
                    $_SESSION['username'] = $new_username;
                    $success_message = "Username updated successfully!";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- Logic for updating password ---
    if ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Please fill all password fields.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } else {
            try {
                // First, get the current hashed password from the DB
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify if the provided current password is correct
                if ($user_data && password_verify($current_password, $user_data['password'])) {
                    // Hash the new password
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update the password in the database
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$hashed_new_password, $user_id]);

                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Incorrect current password.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch current user data to display on the page
try {
    $stmt = $pdo->prepare("SELECT username, email, created_at, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user's booking count
    $bookingStmt = $pdo->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE customer_email = ?");
    $bookingStmt->execute([$user['email']]);
    $bookingData = $bookingStmt->fetch(PDO::FETCH_ASSOC);
    $booking_count = $bookingData['booking_count'];
    
} catch (PDOException $e) {
    die("Could not fetch user data.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
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
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen">
    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <div class="text-3xl">🎬</div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">My Profile</h1>
                        <p class="text-white/70">Manage your account details</p>
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
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
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
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg animate-slide-up">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg animate-slide-up">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?= htmlspecialchars($success_message) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Overview Card -->
            <div class="bgTotal Bookings-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl mb-8 animate-fade-in">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-t-2xl p-8">
                    <div class="flex items-center space-x-6">
                        <div class="bg-white/20 rounded-full w-24 h-24 flex items-center justify-center">
                            <i class="fas fa-user text-white text-3xl"></i>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-3xl font-bold text-white mb-2">
                                Welcome, <?= htmlspecialchars($user['username']) ?>!
                            </h2>
                            <p class="text-white/80 text-lg"><?= htmlspecialchars($user['email']) ?></p>
                            <div class="flex items-center space-x-4 mt-4">
                                <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-crown mr-1"></i>
                                    <?= ucfirst($user['role']) ?>
                                </span>
                                <span class="text-white/80 text-sm">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Joined <?= date('M Y', strtotime($user['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Management Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Update Username Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl animate-slide-up">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-t-2xl p-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-user-edit mr-3"></i>
                            Update Username
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">Change your display name</p>
                    </div>
                    
                    <form method="post" class="p-6">
                        <input type="hidden" name="action" value="update_username">
                        
                        <div class="mb-6">
                            <label class="block text-white font-medium mb-3">
                                <i class="fas fa-at mr-2 text-blue-400"></i>Current Username
                            </label>
                            <div class="bg-white/5 border border-white/20 rounded-lg p-3 text-white/70">
                                <?= htmlspecialchars($user['username']) ?>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-white font-medium mb-3">
                                <i class="fas fa-user mr-2 text-cyan-400"></i>New Username
                            </label>
                            <input type="text" name="new_username" required minlength="3" 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent transition-all duration-300"
                                   placeholder="Enter new username">
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>Update Username
                        </button>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl animate-slide-up">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-t-2xl p-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-key mr-3"></i>
                            Change Password
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">Update your account security</p>
                    </div>
                    
                    <form method="post" class="p-6">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-white font-medium mb-2">
                                    <i class="fas fa-lock mr-2 text-purple-400"></i>Current Password
                                </label>
                                <div class="relative">
                                    <input type="password" name="current_password" id="currentPass" required 
                                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 pr-12"
                                           placeholder="Enter current password">
                                    <button type="button" onclick="togglePassword('currentPass', this)" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-white font-medium mb-2">
                                    <i class="fas fa-key mr-2 text-pink-400"></i>New Password
                                </label>
                                <div class="relative">
                                    <input type="password" name="new_password" id="newPass" required minlength="6"
                                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 pr-12"
                                           placeholder="Enter new password">
                                    <button type="button" onclick="togglePassword('newPass', this)" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <p class="text-white/60 text-sm mt-1">Minimum 6 characters</p>
                            </div>
                            
                            <div>
                                <label class="block text-white font-medium mb-2">
                                    <i class="fas fa-check-double mr-2 text-green-400"></i>Confirm New Password
                                </label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirmPass" required 
                                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-300 pr-12"
                                           placeholder="Confirm new password">
                                    <button type="button" onclick="togglePassword('confirmPass', this)" 
                                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-shield-alt mr-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl mt-8 animate-fade-in">
                <div class="bg-gradient-to-r from-gray-700 to-gray-800 rounded-t-2xl p-6">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-info-circle mr-3"></i>
                        Account Information
                    </h3>
                    <p class="text-gray-300 text-sm mt-1">Your account details and settings</p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-white/70 text-sm font-medium mb-1">Email Address</label>
                                <p class="text-white text-lg"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <div>
                                <label class="block text-white/70 text-sm font-medium mb-1">Account Type</label>
                                <span class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    <?= ucfirst($user['role']) ?> Account
                                </span>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-white/70 text-sm font-medium mb-1">Member Since</label>
                                <p class="text-white text-lg"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                            </div>
                            <div>
                                <label class="block text-white/70 text-sm font-medium mb-1">Last Login</label>
                                <p class="text-green-400 text-lg">Currently Active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('border-red-400');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-400');
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

        // Form submission loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                button.disabled = true;
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            });
        });
    </script>
</body>
</html>
