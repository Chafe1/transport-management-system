<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isAdmin()) {
    redirect('login.php');
}

$msg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Vehicle
    if (isset($_POST['add_vehicle'])) {
        $v_num = cleanInput($_POST['vehicle_number']);
        $model = cleanInput($_POST['model']);
        $capacity = (int)$_POST['capacity'];
        $stmt = $pdo->prepare("INSERT INTO vehicles (vehicle_number, model, capacity) VALUES (?, ?, ?)");
        $stmt->execute([$v_num, $model, $capacity]);
        $msg = "Vehicle added successfully!";
    }
    
    // Add Driver
    if (isset($_POST['add_driver'])) {
        $name = cleanInput($_POST['name']);
        $license = cleanInput($_POST['license']);
        $phone = cleanInput($_POST['phone']);
        $stmt = $pdo->prepare("INSERT INTO drivers (name, license_number, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $license, $phone]);
        $msg = "Driver added successfully!";
    }

    // Add Route
    if (isset($_POST['add_route'])) {
        $start = cleanInput($_POST['start']);
        $end = cleanInput($_POST['end']);
        $dist = (float)$_POST['distance'];
        $fare = (float)$_POST['fare'];
        $stmt = $pdo->prepare("INSERT INTO routes (start_point, end_point, distance, fare) VALUES (?, ?, ?, ?)");
        $stmt->execute([$start, $end, $dist, $fare]);
        $msg = "Route added successfully!";
    }
    
    // Update Booking
    if (isset($_POST['update_booking'])) {
        $bid = (int)$_POST['booking_id'];
        $status = $_POST['status'];
        $did = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
        $stmt = $pdo->prepare("UPDATE bookings SET status = ?, driver_id = ? WHERE id = ?");
        $stmt->execute([$status, $did, $bid]);
        $msg = "Booking updated!";
    }

    // Update User Role/Status
    if (isset($_POST['update_user'])) {
        $uid = (int)$_POST['user_id'];
        $role = $_POST['role'];
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $uid]);
        $msg = "User role updated!";
    }

    // Update Payment Status
    if (isset($_POST['update_payment'])) {
        $pid = (int)$_POST['payment_id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $pid]);
        $msg = "Payment status updated!";
    }

    // Delete Item
    if (isset($_POST['delete_item'])) {
        $id = (int)$_POST['item_id'];
        $table = $_POST['table_name'];
        // Basic protection check for table name
        $allowed_tables = ['vehicles', 'drivers', 'routes', 'bookings', 'users', 'payments'];
        if (in_array($table, $allowed_tables)) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Item deleted!";
        }
    }
}

// Fetch Stats
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'vehicles' => $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
    'drivers' => $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'paid'")->fetchColumn() ?: 0
];

// Fetch Data
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();
$drivers = $pdo->query("SELECT * FROM drivers ORDER BY id DESC")->fetchAll();
$routes = $pdo->query("SELECT * FROM routes ORDER BY id DESC")->fetchAll();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$payments = $pdo->query("SELECT p.*, u.username, b.booking_date FROM payments p JOIN users u ON p.user_id = u.id JOIN bookings b ON p.booking_id = b.id ORDER BY p.id DESC")->fetchAll();
$bookings = $pdo->query("SELECT b.*, u.username, v.vehicle_number, r.start_point, r.end_point, r.fare, d.name as driver_name 
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.id 
                         JOIN vehicles v ON b.vehicle_id = v.id 
                         JOIN routes r ON b.route_id = r.id 
                         LEFT JOIN drivers d ON b.driver_id = d.id
                         ORDER BY b.booking_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TMS PRO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>TMS Admin</h2></div>
            <ul class="nav-links">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-home"></i>&nbsp; Dashboard</a></li>
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-globe"></i>&nbsp; View Site</a></li>
                <li class="nav-item" style="margin-top: auto;"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>&nbsp; Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="card-header">
                <div><h1>Control Panel</h1><p style="color:var(--text-dim)">Total Collected: <span style="color:var(--success)">ETB <?php echo number_format($stats['revenue'], 2); ?></span></p></div>
                <?php if($msg): ?><div style="background:rgba(34,197,94,0.1); padding:10px; border-radius:8px; color:var(--success)"><?php echo $msg; ?></div><?php endif; ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><h3>USERS</h3><div class="value"><?php echo $stats['users']; ?></div></div>
                <div class="stat-card"><h3>VEHICLES</h3><div class="value"><?php echo $stats['vehicles']; ?></div></div>
                <div class="stat-card"><h3>DRIVERS</h3><div class="value"><?php echo $stats['drivers']; ?></div></div>
                <div class="stat-card"><h3>PENDING RIDES</h3><div class="value" style="color:var(--secondary)"><?php echo $stats['bookings']; ?></div></div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" onclick="openTab(event, 'tab-bookings')">Bookings</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-payments')">Payments</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-users')">User Management</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-fleet')">Fleet & Drivers</button>
            </div>

            <!-- Bookings Tab -->
            <div id="tab-bookings" class="tab-content active">
                <section class="card">
                    <div class="card-header"><h2>Active Bookings</h2></div>
                    <table>
                        <thead><tr><th>User</th><th>Details</th><th>Driver</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($bookings as $b): ?>
                            <tr>
                                <td><strong><?php echo $b['username']; ?></strong></td>
                                <td><?php echo $b['start_point']." - ".$b['end_point']; ?><br><small>ETB <?php echo number_format($b['fare'], 2); ?></small></td>
                                <td>
                                    <form method="POST" id="fm-b-<?php echo $b['id']; ?>">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="driver_id" onchange="this.form.submit()">
                                            <option value="">No Driver</option>
                                            <?php foreach($drivers as $d): ?>
                                                <option value="<?php echo $d['id']; ?>" <?php echo $b['driver_id'] == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="update_booking">
                                </td>
                                <td>
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $b['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $b['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="cancelled" <?php echo $b['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="completed" <?php echo $b['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete booking?')">
                                        <input type="hidden" name="item_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="table_name" value="bookings">
                                        <button type="submit" name="delete_item" class="btn btn-sm" style="background:var(--error); padding:5px 10px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Payments Tab -->
            <div id="tab-payments" class="tab-content">
                <section class="card">
                    <div class="card-header"><h2>Ethiopian Payment Records</h2></div>
                    <table>
                        <thead><tr><th>User</th><th>Method</th><th>Transaction ID</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach($payments as $p): ?>
                            <tr>
                                <td><?php echo $p['username']; ?></td>
                                <td><span class="status-badge" style="background:var(--glass); border:1px solid var(--primary)"><?php echo $p['payment_method']; ?></span></td>
                                <td><code><?php echo $p['transaction_id']; ?></code></td>
                                <td>ETB <?php echo number_format($p['amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $p['status'] == 'paid' ? 'confirmed' : ($p['status'] == 'failed' ? 'cancelled' : 'pending'); ?>"><?php echo $p['status']; ?></span></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:5px;">
                                            <option value="pending" <?php echo $p['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="paid" <?php echo $p['status'] == 'paid' ? 'selected' : ''; ?>>Mark Paid</option>
                                            <option value="failed" <?php echo $p['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                        <input type="hidden" name="update_payment">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($payments)): ?><tr><td colspan="6" style="text-align:center">No payments found yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Users Tab -->
            <div id="tab-users" class="tab-content">
                <section class="card">
                    <div class="card-header"><h2>User Catalog</h2></div>
                    <table>
                        <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td><?php echo $u['username']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td>
                                    <form method="POST" id="fm-u-<?php echo $u['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="role" onchange="this.form.submit()">
                                            <option value="user" <?php echo $u['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_user">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete user?')">
                                        <input type="hidden" name="item_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="table_name" value="users">
                                        <button type="submit" name="delete_item" class="btn btn-sm" <?php echo $u['username'] == 'admin' ? 'disabled' : ''; ?> style="background:var(--error); padding:5px 10px;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- Fleet Tab -->
            <div id="tab-fleet" class="tab-content">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <section class="card">
                        <div class="card-header"><h3>Vehicles</h3><button class="btn btn-sm" style="width:auto" onclick="showModal('vehicleModal')">Add</button></div>
                        <table>
                            <tbody>
                                <?php foreach($vehicles as $v): ?>
                                <tr><td><?php echo $v['model']; ?> (<?php echo $v['vehicle_number']; ?>)</td><td><button onclick="confirmDelete(<?php echo $v['id']; ?>, 'vehicles')" class="btn-sm" style="background:none; color:var(--error); border:none;"><i class="fas fa-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                    <section class="card">
                        <div class="card-header"><h3>Drivers</h3><button class="btn btn-sm" style="width:auto" onclick="showModal('driverModal')">Add</button></div>
                        <table>
                            <tbody>
                                <?php foreach($drivers as $d): ?>
                                <tr><td><?php echo $d['name']; ?> (<?php echo $d['phone']; ?>)</td><td><button onclick="confirmDelete(<?php echo $d['id']; ?>, 'drivers')" class="btn-sm" style="background:none; color:var(--error); border:none;"><i class="fas fa-trash"></i></button></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="vehicleModal" class="modal-overlay"><div class="modal-content"><h3>Add Vehicle</h3><form method="POST"><div class="form-group"><label>Number</label><input type="text" name="vehicle_number"></div><div class="form-group"><label>Model</label><input type="text" name="model"></div><div class="form-group"><label>Capacity</label><input type="number" name="capacity"></div><button type="submit" name="add_vehicle" class="btn">Save</button><button type="button" class="btn" style="background:none;" onclick="hideModal('vehicleModal')">Cancel</button></form></div></div>
    <div id="driverModal" class="modal-overlay"><div class="modal-content"><h3>Add Driver</h3><form method="POST"><div class="form-group"><label>Name</label><input type="text" name="name"></div><div class="form-group"><label>License</label><input type="text" name="license"></div><div class="form-group"><label>Phone</label><input type="text" name="phone"></div><button type="submit" name="add_driver" class="btn">Save</button><button type="button" class="btn" style="background:none;" onclick="hideModal('driverModal')">Cancel</button></form></div></div>

    <form id="delete-form" method="POST" style="display:none;">
        <input type="hidden" name="item_id" id="del-id">
        <input type="hidden" name="table_name" id="del-table">
        <input type="hidden" name="delete_item">
    </form>

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
        function showModal(id) { document.getElementById(id).style.display = 'flex'; }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }
        function confirmDelete(id, table) {
            if(confirm('Delete this?')) {
                document.getElementById('del-id').value = id;
                document.getElementById('del-table').value = table;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>
