<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch booking data with all details
$stmt = $pdo->prepare("SELECT b.*, v.model, v.vehicle_number, r.start_point, r.end_point, r.fare, u.username, d.name as driver_name, p.payment_method, p.transaction_id, p.status as p_status 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       JOIN routes r ON b.route_id = r.id 
                       JOIN users u ON b.user_id = u.id 
                       LEFT JOIN drivers d ON b.driver_id = d.id 
                       LEFT JOIN payments p ON b.id = p.booking_id 
                       WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')");
$stmt->execute([$booking_id, $user_id, $_SESSION['role']]);
$t = $stmt->fetch();

if (!$t) {
    die("Access denied or ticket not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journey Ticket - <?php echo $t['id']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: white; color: black; padding: 20px; }
        .ticket-box {
            max-width: 600px;
            margin: 40px auto;
            border: 2px dashed #000;
            padding: 30px;
            border-radius: 15px;
            position: relative;
            background: #fff;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .ticket-header h1 { color: #6366f1; margin: 0; }
        .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-item label { display: block; color: #666; font-size: 0.8rem; text-transform: uppercase; }
        .info-item span { font-weight: 700; font-size: 1.1rem; }
        .barcode { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .btn-print {
            display: block; width: 100%; max-width: 200px; margin: 20px auto;
            background: #6366f1; color: white; padding: 10px; border-radius: 10px;
            text-align: center; text-decoration: none; font-weight: 600;
        }
        @media print {
            .btn-print { display: none; }
            body { padding: 0; }
            .ticket-box { border: 2px solid #000; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="ticket-box">
        <div class="ticket-header">
            <h1>TMS PRO TICKET</h1>
            <p>Official Boarding Pass & Receipt</p>
        </div>

        <div class="grid-info">
            <div class="info-item">
                <label>Passenger</label>
                <span><?php echo strtoupper($t['username']); ?></span>
            </div>
            <div class="info-item">
                <label>Ticket ID</label>
                <span>#TMS-<?php echo str_pad($t['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-item">
                <label>Origin</label>
                <span><?php echo $t['start_point']; ?></span>
            </div>
            <div class="info-item">
                <label>Destination</label>
                <span><?php echo $t['end_point']; ?></span>
            </div>
            <div class="info-item">
                <label>Vehicle</label>
                <span><?php echo $t['model']; ?> (<?php echo $t['vehicle_number']; ?>)</span>
            </div>
            <div class="info-item">
                <label>Driver</label>
                <span><?php echo $t['driver_name'] ?: 'N/A'; ?></span>
            </div>
            <div class="info-item">
                <label>Payment Method</label>
                <span><?php echo $t['payment_method'] ?: 'NOT PAID'; ?></span>
            </div>
            <div class="info-item">
                <label>Fare Amount</label>
                <span style="color: green;">ETB <?php echo number_format($t['fare'], 2); ?></span>
            </div>
        </div>

        <div style="background: #f9f9f9; padding: 10px; text-align: center; border-radius: 8px;">
            <strong>STATUS: <?php echo strtoupper($t['status']); ?></strong>
            <?php if ($t['p_status'] == 'paid'): ?>
                <br><small style="color: blue;">Verified by Admin via <?php echo $t['payment_method']; ?></small>
            <?php endif; ?>
        </div>

        <div class="barcode">
            <p style="font-family: monospace;">* <?php echo $t['transaction_id'] ?: 'TICKET-VALID'; ?> *</p>
            <small>Thank you for traveling with TMS PRO Ethiopia</small>
        </div>
    </div>

    <a href="javascript:window.print()" class="btn-print">Print Ticket</a>
    <a href="user_dashboard.php" class="btn-print" style="background: #eee; color: #333;">Back to Dashboard</a>
</body>
</html>
