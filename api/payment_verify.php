<?php
/**
 * Step 3 — verify the Razorpay payment signature; on success store the
 * registration, generate the Reg ID + QR code and email the confirmation.
 *
 * Dev mode (no Razorpay keys configured): the signature check is replaced by
 * a simulated payment so the flow can be tested locally end-to-end.
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    json_error('Invalid session token. Please refresh the page and try again.', 403);
}

$token     = clean($_POST['token'] ?? '');
$orderId   = clean($_POST['razorpay_order_id'] ?? '');
$paymentId = clean($_POST['razorpay_payment_id'] ?? '');
$signature = clean($_POST['razorpay_signature'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) json_error('Invalid verification token.', 400);
if ($orderId === '' || strlen($orderId) > 64) json_error('Invalid order reference.', 400);

$pdo = db();

/* ---------- Load verified pending registration ---------- */
$stmt = $pdo->prepare('SELECT * FROM otp_verifications WHERE token = ?');
$stmt->execute([$token]);
$pending = $stmt->fetch();

if (!$pending || !(int)$pending['verified']) {
    json_error('Verification session not found. Please submit the form again.', 404);
}
if (strtotime($pending['expires_at']) < time()) {
    json_error('The payment window has expired. Please submit the form again.', 410);
}

/* ---------- Load the matching payment order ---------- */
$stmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ?');
$stmt->execute([$orderId]);
$payment = $stmt->fetch();

if (!$payment || $payment['token'] !== $token) {
    json_error('Payment order not found for this session.', 404);
}
if ($payment['status'] === 'paid') {
    json_error('This payment was already processed.', 409);
}

/* ---------- Verify the payment ---------- */
if (razorpay_dev_mode()) {
    // Simulated payment — only possible while no live keys are configured.
    if (!str_starts_with($orderId, 'DEVORD_')) {
        json_error('Invalid dev-mode order.', 400);
    }
    $paymentId = 'DEVPAY_' . bin2hex(random_bytes(10));
    $signature = 'dev-mode';
} else {
    if ($paymentId === '' || strlen($paymentId) > 64) json_error('Missing payment ID.', 400);
    if (!razorpay_verify_signature($orderId, $paymentId, $signature)) {
        $pdo->prepare("UPDATE payments SET status='failed', payment_id=? WHERE id=?")
            ->execute([$paymentId, $payment['id']]);
        log_line('payment_error.log', "signature mismatch order=$orderId payment=$paymentId");
        json_error('Payment verification failed. If money was deducted it will be auto-refunded by the gateway. Please contact support.', 400);
    }
}

/* ---------- Payment verified → create the registration ---------- */
$data = json_decode($pending['payload'], true);

$stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE email = ?');
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    json_error('This email was already registered.', 409);
}

try {
    $pdo->beginTransaction();

    $regId = generate_reg_id($pdo);
    $data['reg_id'] = $regId;

    $qrRelPath = generate_qr_png($data);
    $qrAbsPath = BASE_PATH . '/' . $qrRelPath;

    $pdo->prepare('INSERT INTO registrations
        (reg_id, first_name, last_name, email, phone, gender, dob, category, tshirt_size,
         blood_group, emergency_name, emergency_phone, city, state, address, qr_path)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            $regId, $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
            $data['gender'], $data['dob'], $data['category'], $data['tshirt_size'],
            $data['blood_group'], $data['emergency_name'], $data['emergency_phone'],
            $data['city'], $data['state'], $data['address'], $qrRelPath,
        ]);

    $pdo->prepare("UPDATE payments
                   SET status='paid', payment_id=?, signature=?, reg_id=?, paid_at=NOW()
                   WHERE id=?")
        ->execute([$paymentId, $signature, $regId, $payment['id']]);

    $pdo->prepare('DELETE FROM otp_verifications WHERE email = ?')->execute([$data['email']]);
    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    log_line('app_error.log', 'payment_verify: ' . $ex->getMessage());
    json_error('Payment received but saving the registration failed. Please contact support with order ID ' . e($orderId) . '.', 500);
}

$paymentInfo = [
    'payment_id' => $paymentId,
    'order_id'   => $orderId,
    'amount'     => '₹' . number_format($payment['amount'] / 100),
];
$emailSent = send_confirmation_email($data, $qrAbsPath, $paymentInfo);

$qrDataUri = is_file($qrAbsPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($qrAbsPath))
    : '';
if ($qrDataUri === '') {
    log_line('app_error.log', "payment_verify: QR file missing after generation for {$regId} ({$qrAbsPath})");
}

json_response([
    'success'      => true,
    'message'      => 'Payment successful — registration confirmed!',
    'reg_id'       => $regId,
    'name'         => $data['first_name'] . ' ' . $data['last_name'],
    'category'     => $data['category'],
    'amount_paid'  => $paymentInfo['amount'],
    'payment_id'   => $paymentId,
    'qr_url'       => BASE_URL . '/' . $qrRelPath,
    'qr_data'      => $qrDataUri,
    'email_sent'   => $emailSent,
]);
