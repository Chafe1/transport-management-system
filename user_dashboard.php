<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Book Ride
    if (isset($_POST['book_ride'])) {
        $vehicle_id = (int)$_POST['vehicle_id'];
        $route_id = (int)$_POST['route_id'];
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, route_id) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $vehicle_id, $route_id]);
        $msg = "Ride requested successfully! Please proceed to payment.";
    }

    // Process Payment
    if (isset($_POST['pay_booking'])) {
        $bid = (int)$_POST['booking_id'];
        $amount = (float)$_POST['amount'];
        $method = $_POST['payment_method'];
        $tx_id = cleanInput($_POST['transaction_id']);
        
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$bid, $user_id, $amount, $method, $tx_id]);
        $msg = "Payment submitted! Admin will verify your transaction shortly.";
    }

    // Cancel Booking
    if (isset($_POST['cancel_booking'])) {
        $bid = (int)$_POST['booking_id'];
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$bid, $user_id]);
        $msg = "Booking has been cancelled.";
    }

    // Update Profile
    if (isset($_POST['update_profile'])) {
        $email = cleanInput($_POST['email']);
        $new_pass = $_POST['new_password'];
        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt->execute([$email, $hashed, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);
        }
        $msg = "Profile updated successfully!";
    }
}

// Fetch Data
$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available'")->fetchAll();
$routes = $pdo->query("SELECT * FROM routes")->fetchAll();
$user_data = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_data->execute([$user_id]);
$user = $user_data->fetch();

$stmt = $pdo->prepare("SELECT b.*, v.vehicle_number, v.model, r.start_point, r.end_point, r.fare, d.name as driver_name, d.phone as driver_phone, p.status as payment_status 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       JOIN routes r ON b.route_id = r.id 
                       LEFT JOIN drivers d ON b.driver_id = d.id
                       LEFT JOIN payments p ON b.id = p.booking_id
                       WHERE b.user_id = ? 
                       ORDER BY b.booking_date DESC");
$stmt->execute([$user_id]);
$my_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - TMS PRO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>TMS User</h2></div>
            <ul class="nav-links">
                <li class="nav-item"><a href="#" class="nav-link active"><i class="fas fa-th-large"></i>&nbsp; Dashboard</a></li>
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i>&nbsp; Home Page</a></li>
                <li class="nav-item" style="margin-top:auto;"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>&nbsp; Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="card-header">
                <div><h1>Welcome back, <?php echo $user['username']; ?></h1><p style="color:var(--text-dim)">Manage your transport needs and payments</p></div>
                <?php if($msg): ?><div style="background:rgba(99,102,241,0.1); padding:10px; border-radius:8px; color:var(--primary)"><?php echo $msg; ?></div><?php endif; ?>
            </header>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'tab-book')">New Ride</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-history')">Ride History</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-profile')">Settings</button>
            </div>

            <!-- New Ride Tab -->
            <div id="tab-book" class="tab-content active">
                <section class="card">
                    <div class="card-header"><h2>Reserve a Vehicle</h2></div>
                    <form method="POST">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                            <div class="form-group">
                                <label>Select Vehicle Type</label>
                                <select name="vehicle_id" required>
                                    <?php foreach($vehicles as $v): ?>
                                        <option value="<?php echo $v['id']; ?>"><?php echo $v['model']; ?> (Max <?php echo $v['capacity']; ?> Pers.)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Where are you going?</label>
                                <select name="route_id" required>
                                    <?php foreach($routes as $r): ?>
                                        <option value="<?php echo $r['id']; ?>"><?php echo $r['start_point']; ?> to <?php echo $r['end_point']; ?> - ETB <?php echo number_format($r['fare'], 2); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="book_ride" class="btn" style="width:200px;">Confirm Request</button>
                    </form>
                </section>
            </div>

            <!-- History Tab -->
            <div id="tab-history" class="tab-content">
                <section class="card">
                    <div class="card-header"><h2>Your Booking Record</h2></div>
                    <table>
                        <thead><tr><th>Journey Details</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($my_bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $b['start_point']; ?> <i class="fas fa-arrow-right" style="font-size:0.8rem;"></i> <?php echo $b['end_point']; ?></strong><br>
                                    <small><?php echo $b['model']; ?> | <?php echo date('M d, H:i', strtotime($b['booking_date'])); ?></small>
                                    <?php if($b['driver_name']): ?>
                                        <div class="booking-detail"><i class="fas fa-user-circle"></i> <?php echo $b['driver_name']; ?> (<?php echo $b['driver_phone']; ?>)</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                                <td>
                                    <?php if(!$b['payment_status']): ?>
                                        <form action="process_payment.php" method="POST">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" class="btn btn-sm" style="background:var(--primary); padding:8px 15px;">Pay with Chapa</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-badge status-<?php echo $b['payment_status'] == 'paid' ? 'confirmed' : 'pending'; ?>"><?php echo strtoupper($b['payment_status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($b['status'] == 'confirmed' || $b['status'] == 'completed'): ?>
                                        <a href="ticket.php?id=<?php echo $b['id']; ?>" class="btn-sm" style="background:#6366f1; color:white; text-decoration:none; padding:5px 10px; border-radius:5px;"><i class="fas fa-ticket-alt"></i> Ticket</a>
                                    <?php elseif($b['status'] == 'pending'): ?>
                                        <form method="POST"><input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>"><button type="submit" name="cancel_booking" class="btn-sm" style="background:none; border:none; color:var(--error); cursor:pointer;">Cancel</button></form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Settings Tab -->
            <div id="tab-profile" class="tab-content">
                <section class="card" style="max-width:500px;">
                    <div class="card-header"><h2>Update Information</h2></div>
                    <form method="POST">
                        <div class="form-group"><label>Username</label><input type="text" value="<?php echo $user['username']; ?>" disabled style="opacity:0.5"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo $user['email']; ?>"></div>
                        <div class="form-group"><label>New Password</label><input type="password" name="new_password" placeholder="Leave blank to keep current"></div>
                        <button type="submit" name="update_profile" class="btn">Save & Update</button>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <!-- Payment feedback messages -->
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('msg') === 'success') {
            alert('Payment Successful! Your ride is confirmed.');
        } else if(urlParams.get('msg') === 'failed') {
            alert('Payment was not completed. Please try again.');
        }
    </script>

    <script>
        function openTab(evt, tabName) {
            let i, content, btn;
            content = document.getElementsByClassName("tab-content");
            for (i = 0; i < content.length; i++) content[i].classList.remove("active");
            btn = document.getElementsByClassName("tab-btn");
            for (i = 0; i < btn.length; i++) btn[i].classList.remove("active");
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>
