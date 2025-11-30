<?php
session_start();
require_once 'db.php';

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : null;
$booking = null;

if ($booking_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, m.title as movie_title, m.image_url, s.show_date, s.show_time 
            FROM bookings b 
            JOIN showtimes s ON b.showtime_id = s.id 
            JOIN movies m ON s.movie_id = m.id 
            WHERE b.id = ? AND b.status = 'Pending'
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Database error. Could not fetch booking details.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - MovieTix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">🎬 MovieTix</h1>
            <p class="text-white/80">Complete your booking</p>
        </div>

        <!-- Payment Container -->
        <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="lg:flex">
                <!-- Order Summary -->
                <div class="lg:w-2/5 bg-gradient-to-br from-gray-50 to-gray-100 p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-receipt mr-2"></i>Order Summary
                    </h2>

                    <!-- Movie Card -->
                    <div class="bg-white rounded-xl p-6 shadow-md mb-6">
                        <div class="flex items-start space-x-4">
                            <img src="<?= htmlspecialchars($booking['image_url']) ?>"
                                alt="<?= htmlspecialchars($booking['movie_title']) ?>"
                                class="w-20 h-28 object-cover rounded-lg shadow-md">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-800 mb-2">
                                    <?= htmlspecialchars($booking['movie_title']) ?>
                                </h3>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <p><i
                                            class="fas fa-calendar mr-2"></i><?= date('D, M j, Y', strtotime($booking['show_date'])) ?>
                                    </p>
                                    <p><i
                                            class="fas fa-clock mr-2"></i><?= date('g:i A', strtotime($booking['show_time'])) ?>
                                    </p>
                                    <p><i
                                            class="fas fa-user mr-2"></i><?= htmlspecialchars($booking['customer_name']) ?>
                                    </p>
                                    <p><i
                                            class="fas fa-envelope mr-2"></i><?= htmlspecialchars($booking['customer_email']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="space-y-4">
                        <div class="bg-white rounded-xl p-4 shadow-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Seats:</span>
                                <span
                                    class="font-medium text-purple-600"><?= htmlspecialchars($booking['seat_numbers']) ?></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-4 shadow-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Tickets:</span>
                                <span class="font-medium"><?= $booking['num_tickets'] ?> ×
                                    ₹<?= number_format($booking['total_amount'] / $booking['num_tickets']) ?></span>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-4 shadow-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Booking Fee:</span>
                                <span class="font-medium text-green-600">FREE</span>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-4 text-white">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium">Total Amount:</span>
                                <span class="text-2xl font-bold">₹<?= number_format($booking['total_amount']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="lg:w-3/5 p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-credit-card mr-2"></i>Payment Details
                    </h2>

                    <!-- Payment Methods -->
                    <div class="mb-8">
                        <label class="block text-gray-700 font-medium mb-4">Select Payment Method:</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button type="button" onclick="selectPaymentMethod('card')"
                                class="payment-method-btn active flex items-center justify-center space-x-2 p-4 border-2 rounded-xl transition-all duration-300">
                                <i class="fas fa-credit-card text-xl"></i>
                                <span class="font-medium">Card</span>
                            </button>
                            <button type="button" onclick="selectPaymentMethod('upi')"
                                class="payment-method-btn flex items-center justify-center space-x-2 p-4 border-2 border-gray-300 rounded-xl hover:border-purple-400 transition-all duration-300">
                                <i class="fas fa-mobile-alt text-xl"></i>
                                <span class="font-medium">UPI</span>
                            </button>
                            <button type="button" onclick="selectPaymentMethod('wallet')"
                                class="payment-method-btn flex items-center justify-center space-x-2 p-4 border-2 border-gray-300 rounded-xl hover:border-purple-400 transition-all duration-300">
                                <i class="fas fa-wallet text-xl"></i>
                                <span class="font-medium">Wallet</span>
                            </button>
                        </div>
                    </div>

                    <!-- Card Payment Form -->
                    <form method="post" action="process_payment.php" id="paymentForm" class="space-y-6">
                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                        <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="card">
                        <input type="hidden" name="wallet_provider" id="selectedWallet" value="">

                        <div id="cardFields">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Card Number</label>
                                    <div class="relative">
                                        <input type="text" id="cardNumber" name="card_number"
                                            placeholder="1234 5678 9012 3456" maxlength="19"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 pl-12"
                                            oninput="formatCardNumber(this)">
                                        <i
                                            class="fas fa-credit-card absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 font-medium mb-2">Expiry Date</label>
                                        <input type="text" id="cardExpiry" name="card_expiry" placeholder="MM/YY"
                                            maxlength="5"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300"
                                            oninput="formatExpiry(this)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 font-medium mb-2">CVV</label>
                                        <input type="text" id="cardCvv" name="card_cvv" placeholder="123" maxlength="4"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-gray-700 font-medium mb-2">Cardholder Name</label>
                                    <input type="text" id="cardHolder" name="card_holder" placeholder="John Doe"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300">
                                </div>
                            </div>
                        </div>

                        <div id="upiFields" class="hidden">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">UPI ID</label>
                                <input type="text" id="upiId" name="upi_id" placeholder="yourname@upi"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300">
                            </div>
                            <div class="text-center py-8">
                                <div class="text-gray-600 mb-4">Or scan QR code to pay</div>
                                <div class="inline-block p-4 bg-gray-100 rounded-lg">
                                    <div
                                        class="w-32 h-32 bg-gradient-to-br from-purple-400 to-pink-400 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-qrcode text-white text-4xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="walletFields" class="hidden">
                            <div class="text-center py-8">
                                <div class="text-gray-600 mb-6">Select your preferred wallet</div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <button type="button" onclick="selectWallet('Paytm', this)"
                                        class="wallet-btn p-4 border-2 border-gray-300 rounded-lg hover:border-purple-400 transition-colors">
                                        <div class="text-2xl mb-2">📱</div>
                                        <div class="text-sm font-medium">Paytm</div>
                                    </button>
                                    <button type="button" onclick="selectWallet('PhonePe', this)"
                                        class="wallet-btn p-4 border-2 border-gray-300 rounded-lg hover:border-purple-400 transition-colors">
                                        <div class="text-2xl mb-2">💰</div>
                                        <div class="text-sm font-medium">PhonePe</div>
                                    </button>
                                    <button type="button" onclick="selectWallet('GPay', this)"
                                        class="wallet-btn p-4 border-2 border-gray-300 rounded-lg hover:border-purple-400 transition-colors">
                                        <div class="text-2xl mb-2">🏦</div>
                                        <div class="text-sm font-medium">GPay</div>
                                    </button>
                                    <button type="button" onclick="selectWallet('Freecharge', this)"
                                        class="wallet-btn p-4 border-2 border-gray-300 rounded-lg hover:border-purple-400 transition-colors">
                                        <div class="text-2xl mb-2">💸</div>
                                        <div class="text-sm font-medium">Freecharge</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Button -->
                        <div class="pt-6">
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold py-4 px-8 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg text-lg">
                                <i class="fas fa-lock mr-2"></i>
                                Pay ₹<?= number_format($booking['total_amount']) ?> Securely
                            </button>
                            <p class="text-center text-gray-500 text-sm mt-4">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Your payment information is secured with 256-bit SSL encryption
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Info -->
        <div class="max-w-6xl mx-auto mt-8 text-center">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                <div class="flex justify-center items-center space-x-8 text-white/80">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Secured</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-lock"></i>
                        <span>256-bit Encryption</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-credit-card"></i>
                        <span>PCI Compliant</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPaymentMethod = 'card';
        let selectedWalletProvider = '';

        function selectPaymentMethod(method) {
            currentPaymentMethod = method;
            document.getElementById('selectedPaymentMethod').value = method;

            // Reset all buttons
            document.querySelectorAll('.payment-method-btn').forEach(btn => {
                btn.classList.remove('active', 'border-purple-500', 'bg-purple-50', 'text-purple-600');
                btn.classList.add('border-gray-300');
            });

            // Style selected button
            event.target.closest('.payment-method-btn').classList.add('active', 'border-purple-500', 'bg-purple-50', 'text-purple-600');
            event.target.closest('.payment-method-btn').classList.remove('border-gray-300');

            // Show/hide relevant fields
            document.getElementById('cardFields').classList.add('hidden');
            document.getElementById('upiFields').classList.add('hidden');
            document.getElementById('walletFields').classList.add('hidden');

            if (method === 'card') {
                document.getElementById('cardFields').classList.remove('hidden');
            } else if (method === 'upi') {
                document.getElementById('upiFields').classList.remove('hidden');
            } else if (method === 'wallet') {
                document.getElementById('walletFields').classList.remove('hidden');
            }

            // Reset wallet selection
            selectedWalletProvider = '';
            document.getElementById('selectedWallet').value = '';
            document.querySelectorAll('.wallet-btn').forEach(btn => {
                btn.classList.remove('border-purple-500', 'bg-purple-50');
                btn.classList.add('border-gray-300');
            });
        }

        function selectWallet(provider, button) {
            selectedWalletProvider = provider;
            document.getElementById('selectedWallet').value = provider;

            // Reset all wallet buttons
            document.querySelectorAll('.wallet-btn').forEach(btn => {
                btn.classList.remove('border-purple-500', 'bg-purple-50');
                btn.classList.add('border-gray-300');
            });

            // Highlight selected wallet
            button.classList.add('border-purple-500', 'bg-purple-50');
            button.classList.remove('border-gray-300');
        }

        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            input.value = value;
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
        }

        // Form validation before submit
        document.getElementById('paymentForm').addEventListener('submit', function (e) {
            e.preventDefault();

            if (currentPaymentMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const cardExpiry = document.getElementById('cardExpiry').value;
                const cardCvv = document.getElementById('cardCvv').value;
                const cardHolder = document.getElementById('cardHolder').value;

                if (!cardNumber || cardNumber.length < 13) {
                    alert('Please enter a valid card number');
                    return;
                }
                if (/^(\d)\1+$/.test(cardNumber)) {
                    alert('Card number cannot be all same digits');
                    return;
                }
                if (!cardExpiry || cardExpiry.length !== 5 || !/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                    alert('Please enter a valid expiry date (MM/YY)');
                    return;
                }

                // Detailed expiry validation
                const [expMonthStr, expYearStr] = cardExpiry.split('/');
                const expMonth = parseInt(expMonthStr, 10);
                const expYear = parseInt(expYearStr, 10); // YY

                // Month must be 1–12, not 00
                if (isNaN(expMonth) || expMonth < 1 || expMonth > 12) {
                    alert('Please enter a valid expiry month (01-12)');
                    return;
                }

                // Year must not be 00 (e.g. 00 means invalid)
                if (isNaN(expYear) || expYear === 0) {
                    alert('Please enter a valid expiry year');
                    return;
                }

                // Compare with current month/year (not in the past)
                const now = new Date();
                const currentMonth = now.getMonth() + 1; // 1-12
                const currentYearYY = now.getFullYear() % 100; // last two digits

                if (expYear < currentYearYY || (expYear === currentYearYY && expMonth < currentMonth)) {
                    alert('Card expiry date cannot be in the past');
                    return;
                }

                if (!cardCvv || cardCvv.length < 3) {
                    alert('Please enter a valid CVV');
                    return;
                }
                if (cardCvv === '0000' || cardCvv === '000') {
                    alert('CVV cannot be all Zero');
                    return;
                }
                if (!cardHolder || cardHolder.trim() === '') {
                    alert('Please enter cardholder name');
                    return;
                }
            } else if (currentPaymentMethod === 'upi') {
                const upiId = document.getElementById('upiId').value;
                if (!upiId || !upiId.includes('@')) {
                    alert('Please enter a valid UPI ID');
                    return;
                }
            } else if (currentPaymentMethod === 'wallet') {
                if (!selectedWalletProvider) {
                    alert('Please select a wallet provider');
                    return;
                }
            }

            // If validation passes, submit the form
            this.submit();
        });

        // Initialize with card method selected
        document.addEventListener('DOMContentLoaded', function () {
            const firstButton = document.querySelector('.payment-method-btn.active');
            if (firstButton) {
                firstButton.classList.add('border-purple-500', 'bg-purple-50', 'text-purple-600');
                firstButton.classList.remove('border-gray-300');
            }
        });
    </script>

    <style>
        .payment-method-btn.active {
            @apply border-purple-500 bg-purple-50 text-purple-600;
        }
    </style>
</body>
</html>
