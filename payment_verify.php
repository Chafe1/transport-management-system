<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/config.php';

if (isset($_GET['tx_ref'])) {
    $tx_ref = $_GET['tx_ref'];

    // Verify transaction with Chapa
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.chapa.co/v1/transaction/verify/" . $tx_ref,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . CHAPA_SECRET_KEY,
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        die("cURL Error #:" . $err);
    } else {
        $res = json_decode($response, true);
        if ($res['status'] == 'success' && $res['data']['status'] == 'success') {
            // Payment Successful - Update Database
            $pdo->beginTransaction();
            try {
                // Update Payments table
                $stmt = $pdo->prepare("UPDATE payments SET status = 'paid' WHERE transaction_id = ?");
                $stmt->execute([$tx_ref]);

                // Update Bookings status to confirmed automatically
                $stmt = $pdo->prepare("SELECT booking_id FROM payments WHERE transaction_id = ?");
                $stmt->execute([$tx_ref]);
                $bid = $stmt->fetchColumn();

                $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$bid]);

                $pdo->commit();
                redirect('user_dashboard.php?msg=success');
            } catch (Exception $e) {
                $pdo->rollBack();
                die("Record update failed: " . $e->getMessage());
            }
        } else {
            redirect('user_dashboard.php?msg=failed');
        }
    }
} else {
    redirect('user_dashboard.php');
}
?>
