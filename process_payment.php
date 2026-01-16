<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch booking details
    $stmt = $pdo->prepare("SELECT b.*, r.fare, u.email, u.username FROM bookings b 
                           JOIN routes r ON b.route_id = r.id 
                           JOIN users u ON b.user_id = u.id 
                           WHERE b.id = ? AND b.user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Booking not found.");
    }

    $tx_ref = "TMS-" . $booking_id . "-" . time();
    $amount = $booking['fare'];

    // Chapa API Request Data
    $data = [
        'amount' => $amount,
        'currency' => 'ETB',
        'email' => $booking['email'],
        'first_name' => $booking['username'],
        'last_name' => 'Customer',
        'tx_ref' => $tx_ref,
        'callback_url' => BASE_URL . "payment_verify.php?tx_ref=" . $tx_ref,
        'return_url' => BASE_URL . "user_dashboard.php?msg=verify",
        'customization' => [
            'title' => "Transport Booking #" . $booking_id,
            'description' => "Payment for journey from TMS PRO"
        ]
    ];

    // Initialize Chapa Payment
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.chapa.co/v1/transaction/initialize",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . CHAPA_SECRET_KEY,
            "Content-Type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        die("cURL Error #:" . $err);
    } else {
        $res = json_decode($response, true);
        if ($res['status'] == 'success') {
            // Log payment attempt
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, 'CHAPA', ?, 'pending')");
            $stmt->execute([$booking_id, $user_id, $amount, $tx_ref]);
            
            // Redirect to Chapa Checkout
            header("Location: " . $res['data']['checkout_url']);
            exit();
        } else {
            echo "Chapa Error: " . $res['message'];
        }
    }
} else {
    redirect('user_dashboard.php');
}
?>
