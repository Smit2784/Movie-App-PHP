<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Guide - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen text-white">
    <!-- Header -->
    <header class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="text-3xl">🎬</div>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold">Booking Guide</h1>
                    <p class="text-white/70 text-xs md:text-sm">Step by step help for booking tickets</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm md:text-base transition-all duration-300 flex items-center space-x-2">
                    <i class="fas fa-home"></i><span>Home</span>
                </a>
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
    </header>

    <!-- Main -->
    <main class="container mx-auto px-4 py-8">
        <div
            class="max-w-3xl mx-auto bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl p-6 md:p-8 animate-fade-in">
            <!-- Step indicators -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg md:text-2xl font-bold">How to book tickets</h2>
                <div class="flex items-center space-x-2 text-sm">
                    <span class="text-white/70">Step</span>
                    <span id="currentStepLabel" class="font-bold">1</span>
                    <span class="text-white/70">/ 5</span>
                </div>
            </div>

            <div class="w-full bg-white/10 rounded-full h-2 mb-6">
                <div id="progressBar"
                    class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-300"
                    style="width: 20%;"></div>
            </div>

            <!-- Steps content -->
            <div id="step1" class="step-content animate-slide-up">
                <h3 class="text-xl font-bold mb-3 flex items-center">
                    <span
                        class="w-8 h-8 mr-3 rounded-full bg-blue-500 flex items-center justify-center font-bold">1</span>
                    Sign up or sign in
                </h3>
                <ul class="space-y-2 text-white/80 text-sm md:text-base">
                    <li>• Click on the "Sign In" button on the top navigation bar.</li>
                    <li>• If you are a new user, go to the "Sign Up" tab and create an account using your username,
                        email and password.</li>
                    <li>• If you already have an account, enter your registered email and password and click "Sign In".
                    </li>
                    <li>• After login, your name and email will be used automatically while booking.</li>
                </ul>
            </div>

            <div id="step2" class="step-content hidden animate-slide-up">
                <h3 class="text-xl font-bold mb-3 flex items-center">
                    <span
                        class="w-8 h-8 mr-3 rounded-full bg-purple-500 flex items-center justify-center font-bold">2</span>
                    Choose movie and show time
                </h3>
                <ul class="space-y-2 text-white/80 text-sm md:text-base">
                    <li>• On the home page, browse the list of movies.</li>
                    <li>• Click on a movie card to see details like genre, duration, rating and ticket price.</li>
                    <li>• Click the "Book Now" button for the movie you want to watch.</li>
                    <li>• In the booking popup, choose a show time for today from the available time slots.</li>
                </ul>
            </div>

            <div id="step3" class="step-content hidden animate-slide-up">
                <h3 class="text-xl font-bold mb-3 flex items-center">
                    <span
                        class="w-8 h-8 mr-3 rounded-full bg-green-500 flex items-center justify-center font-bold">3</span>
                    Enter details and number of tickets
                </h3>
                <ul class="space-y-2 text-white/80 text-sm md:text-base">
                    <li>• Your full name and email are filled automatically from your account.</li>
                    <li>• Enter your 10-digit mobile number in the phone field.</li>
                    <li>• Select how many tickets you want using the "Number of Tickets" input.</li>
                    <li>• Click on "Select Seats" button to open the seat selection screen.</li>
                </ul>
            </div>

            <div id="step4" class="step-content hidden animate-slide-up">
                <h3 class="text-xl font-bold mb-3 flex items-center">
                    <span
                        class="w-8 h-8 mr-3 rounded-full bg-orange-500 flex items-center justify-center font-bold">4</span>
                    Select your seats
                </h3>
                <ul class="space-y-2 text-white/80 text-sm md:text-base">
                    <li>• In the seat selection popup, you will see a layout with a screen on top and rows of seats.
                    </li>
                    <li>• Click on available seats (light/gray color) to select them. Selected seats change to a colored
                        style.</li>
                    <li>• You must select the same number of seats as the number of tickets chosen.</li>
                    <li>• After selecting seats, click "Confirm Seat Selection".</li>
                    <li>• Selected seat numbers will be shown in the booking popup before payment.</li>
                </ul>
            </div>

            <div id="step5" class="step-content hidden animate-slide-up">
                <h3 class="text-xl font-bold mb-3 flex items-center">
                    <span
                        class="w-8 h-8 mr-3 rounded-full bg-pink-500 flex items-center justify-center font-bold">5</span>
                    Make payment and check bookings
                </h3>
                <ul class="space-y-2 text-white/80 text-sm md:text-base">
                    <li>• Click on "Proceed to Payment" in the booking popup.</li>
                    <li>• On the payment page, check movie name, show date & time, seats and total amount.</li>
                    <li>• Choose your payment method (Card / UPI / Wallet) and enter the required payment details.</li>
                    <li>• Click the pay button to complete your booking.</li>
                    <li>• After successful payment, your booking status becomes “Confirmed”.</li>
                    <li>• Go to "My Bookings" page anytime to view or cancel your bookings (before showtime).</li>
                </ul>
            </div>

            <!-- Navigation buttons -->
            <div class="mt-8 flex items-center justify-between">
                <button id="prevBtn"
                    class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-sm md:text-base flex items-center space-x-2 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                    disabled>
                    <i class="fas fa-arrow-left"></i>
                    <span>Previous</span>
                </button>
                <button id="nextBtn"
                    class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-sm md:text-base flex items-center space-x-2 transition-all">
                    <span>Next</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-8">
            <a href="index.php"
                class="inline-flex items-center bg-white/10 hover:bg-white/20 text-white font-medium py-3 px-6 rounded-xl text-sm md:text-base transition-all border border-white/20">
                <i class="fas fa-ticket-alt mr-2"></i>
                Start Booking
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-4 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400 text-sm">
                &copy; <?= date('Y') ?> MovieTix - Online Movie Ticket Booking Platform
            </p>
        </div>
    </footer>

    <script>
        const totalSteps = 5;
        let currentStep = 1;

        const stepElements = [];
        for (let i = 1; i <= totalSteps; i++) {
            stepElements[i] = document.getElementById('step' + i);
        }

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const currentStepLabel = document.getElementById('currentStepLabel');
        const progressBar = document.getElementById('progressBar');

        function updateStepView() {
            // Show current step, hide others
            for (let i = 1; i <= totalSteps; i++) {
                if (i === currentStep) {
                    stepElements[i].classList.remove('hidden');
                } else {
                    stepElements[i].classList.add('hidden');
                }
            }

            currentStepLabel.textContent = currentStep;
            const progressPercent = (currentStep / totalSteps) * 100;
            progressBar.style.width = progressPercent + '%';

            // Button states
            prevBtn.disabled = currentStep === 1;
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = '<span>Finish</span><i class="fas fa-check ml-2"></i>';
            } else {
                nextBtn.innerHTML = '<span>Next</span><i class="fas fa-arrow-right"></i>';
            }
        }

        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateStepView();
            }
        });

        nextBtn.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepView();
            } else {
                // On finish, go back to home or bookings page (you can change this)
                window.location.href = 'index.php';
            }
        });

        // Scroll to top on load
        window.scrollTo({ top: 0, behavior: 'smooth' });
    </script>
</body>

</html>