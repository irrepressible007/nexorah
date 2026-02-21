<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header('Location: events.php');
    exit();
}

$event_id = $_GET['id'];

// Fetch event details
$query = "SELECT * FROM events WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: events.php');
    exit();
}

// Handle registration form submission
if ($_POST && $event['status'] == 'upcoming') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $guardian_number = $_POST['guardian_number'];
    $ticket_quantity = (int)$_POST['ticket_quantity'];
    $total_amount = $event['fee'] * $ticket_quantity;
    $transaction_id = $_POST['transaction_id'] ?? null;
    
    // If this is a payment confirmation (after QR scan)
    if (isset($_POST['confirm_payment']) && !empty($transaction_id)) {
        $insert_query = "INSERT INTO registrations (event_id, full_name, phone, email, guardian_number, ticket_quantity, total_amount, payment_status, approval_status, transaction_id, payment_date) 
                         VALUES (:event_id, :full_name, :phone, :email, :guardian_number, :ticket_quantity, :total_amount, 'paid', 'pending', :transaction_id, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':event_id', $event_id);
        $insert_stmt->bindParam(':full_name', $full_name);
        $insert_stmt->bindParam(':phone', $phone);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':guardian_number', $guardian_number);
        $insert_stmt->bindParam(':ticket_quantity', $ticket_quantity);
        $insert_stmt->bindParam(':total_amount', $total_amount);
        $insert_stmt->bindParam(':transaction_id', $transaction_id);
        
        if ($insert_stmt->execute()) {
            $success_message = "Registration successful! Your registration is pending approval. You will be notified once approved.";
        } else {
            $error_message = "Registration failed. Please try again.";
        }
    } else {
        // Show QR payment interface
        $show_qr_payment = true;
        $registration_data = [
            'full_name' => $full_name,
            'phone' => $phone,
            'email' => $email,
            'guardian_number' => $guardian_number,
            'ticket_quantity' => $ticket_quantity,
            'total_amount' => $total_amount
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Nexopay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2>Nexopay</h2>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php" class="active">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="user-panel.php">My Registrations</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="event-details">
            <div class="event-header">
                <?php 
                $image_path = $event['image'];
                // Ensure the path starts from root
                if (!empty($image_path) && file_exists($image_path)) {
                    $image_src = htmlspecialchars($image_path);
                } else {
                    $image_src = 'assets/images/placeholder.jpg'; // fallback image
                }
                ?>
                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                <div class="event-info">
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    <p class="event-status status-<?php echo $event['status']; ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </p>
                    <p class="event-date">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                    </p>
                    <p class="event-fee">
                        <i class="fas fa-tag"></i> 
                        Registration Fee: ৳<?php echo number_format($event['fee'], 2); ?> BDT
                    </p>
                </div>
            </div>

            <div class="event-description">
                <h3>Event Description</h3>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>

            <?php if ($event['status'] == 'upcoming'): ?>
                <?php if (isset($show_qr_payment) && $show_qr_payment): ?>
                    <!-- QR Payment Interface -->
                    <div class="qr-payment-container">
                        <h3>Complete Your Payment</h3>
                        <div class="payment-summary">
                            <h4>Registration Summary</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($registration_data['full_name']); ?></p>
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['title']); ?></p>
                            <p><strong>Tickets:</strong> <?php echo $registration_data['ticket_quantity']; ?></p>
                            <p><strong>Total Amount:</strong> ৳<?php echo number_format($registration_data['total_amount'], 2); ?> BDT</p>
                        </div>
                        
                        <div class="qr-payment-section">
                            <div class="qr-code-container">
                                <h4>Scan QR Code to Pay</h4>
                                <div id="qrcode"></div>
                                <p class="payment-info">Scan this QR code with your mobile banking app to complete the payment</p>
                            </div>
                            
                            <div class="payment-confirmation">
                                <h4>After Payment Confirmation</h4>
                                <form method="POST" id="confirmationForm">
                                    <!-- Hidden fields to preserve registration data -->
                                    <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($registration_data['full_name']); ?>">
                                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($registration_data['phone']); ?>">
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($registration_data['email']); ?>">
                                    <input type="hidden" name="guardian_number" value="<?php echo htmlspecialchars($registration_data['guardian_number']); ?>">
                                    <input type="hidden" name="ticket_quantity" value="<?php echo $registration_data['ticket_quantity']; ?>">
                                    <input type="hidden" name="confirm_payment" value="1">
                                    
                                    <div class="form-group">
                                        <label for="transaction_id">Transaction ID *</label>
                                        <input type="text" id="transaction_id" name="transaction_id" required placeholder="Enter your transaction ID">
                                        <small>Enter the transaction ID from your payment confirmation</small>
                                    </div>
                                    
                                    <button type="submit" class="btn-primary btn-large">Register Now</button>
                                    <button type="button" class="btn-secondary btn-large" onclick="window.location.reload()">Back to Form</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // Generate QR Code
                    const paymentData = {
                        amount: <?php echo $registration_data['total_amount']; ?>,
                        event: '<?php echo addslashes($event['title']); ?>',
                        reference: 'NEX<?php echo $event_id . time(); ?>'
                    };
                    
                    const qrText = `Payment for ${paymentData.event}\nAmount: ৳${paymentData.amount} BDT\nReference: ${paymentData.reference}`;
                    
                    QRCode.toCanvas(document.getElementById('qrcode'), qrText, {
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff'
                    }, function (error) {
                        if (error) console.error(error);
                    });
                    </script>
                    
                <?php else: ?>
                    <!-- Registration Form -->
                    <div class="registration-form">
                        <h3>Registration Form</h3>
                        
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-error"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" id="registrationForm">
                            <div class="form-group">
                                <label>Registration Fee: ৳<?php echo number_format($event['fee'], 2); ?> BDT</label>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="guardian_number">Guardian Number *</label>
                                <input type="tel" id="guardian_number" name="guardian_number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="ticket_quantity">Tickets Quantity (Max-5) *</label>
                                <input type="number" id="ticket_quantity" name="ticket_quantity" min="1" max="5" value="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Ticket Price</label>
                                <div class="total-price" id="totalPrice">৳<?php echo number_format($event['fee'], 2); ?> BDT</div>
                            </div>
                            
                            <button type="submit" class="btn-primary btn-large">Pay Now</button>
                        </form>
                        
                        <script>
                        // Calculate total price based on quantity
                        const ticketQuantity = document.getElementById('ticket_quantity');
                        const totalPrice = document.getElementById('totalPrice');
                        const eventFee = <?php echo $event['fee']; ?>;
                        
                        ticketQuantity.addEventListener('input', function() {
                            const quantity = parseInt(this.value) || 1;
                            const total = eventFee * quantity;
                            totalPrice.textContent = '৳' + total.toFixed(2) + ' BDT';
                        });
                        </script>
                    </div>
                <?php endif; ?>
            <?php elseif ($event['status'] == 'ongoing'): ?>
                <div class="event-status-message">
                    <h3>Event is Currently Ongoing</h3>
                    <p>This event is currently running. Registration is not available at this time.</p>
                </div>
            <?php else: ?>
                <div class="event-status-message">
                    <h3>Event Completed</h3>
                    <p>This event has already been completed. Registration is no longer available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>