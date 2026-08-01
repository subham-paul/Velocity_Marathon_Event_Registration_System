<?php
/**
 * Step 2 — verify the OTP. On success the email is marked verified and a
 * Razorpay order is created for the selected race category. The registration
 * itself is stored only after the payment is verified (api/payment_verify.php).
 */
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    json_error('Invalid session token. Please refresh the page and try again.', 403);
}

$token = clean($_POST['token'] ?? '');
$otp   = clean($_POST['otp'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) json_error('Invalid verification token.', 400);
if (!preg_match('/^\d{' . OTP_LENGTH . '}$/', $otp)) json_error('Enter the ' . OTP_LENGTH . '-digit OTP.', 400);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM otp_verifications WHERE token = ?');
$stmt->execute([$token]);
$pending = $stmt->fetch();

if (!$pending) {
    json_error('This verification session was not found. Please submit the form again.', 404);
}
if (strtotime($pending['expires_at']) < time()) {
    $pdo->prepare('DELETE FROM otp_verifications WHERE id = ?')->execute([$pending['id']]);
    json_error('This OTP has expired. Please submit the form again.', 410);
}
if ((int)$pending['attempts'] >= OTP_MAX_ATTEMPTS) {
    $pdo->prepare('DELETE FROM otp_verifications WHERE id = ?')->execute([$pending['id']]);
    json_error('Too many wrong attempts. Please submit the form again.', 429);
}

if (!password_verify($otp, $pending['otp_hash'])) {
    $pdo->prepare('UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?')->execute([$pending['id']]);
    $left = OTP_MAX_ATTEMPTS - (int)$pending['attempts'] - 1;
    json_error($left > 0
        ? "Incorrect OTP. {$left} attempt" . ($left === 1 ? '' : 's') . ' remaining.'
        : 'Incorrect OTP. No attempts remaining — please submit the form again.', 401);
}

/* ---------- OTP valid → email verified, move to payment ---------- */
$data = json_decode($pending['payload'], true);

// Re-check duplicates before taking money.
$stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE email = ?');
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM otp_verifications WHERE id = ?')->execute([$pending['id']]);
    json_error('This email was already registered.', 409);
}

try {
    $amountPaise = category_fee($data['category']) * 100;
    $order = razorpay_create_order($amountPaise, 'reg_' . substr($token, 0, 24));
} catch (RuntimeException $ex) {
    json_error($ex->getMessage(), 502);
}

// Email verified — give the runner 30 minutes to finish the payment.
$pdo->prepare('UPDATE otp_verifications
               SET verified = 1, expires_at = NOW() + INTERVAL 30 MINUTE
               WHERE id = ?')->execute([$pending['id']]);

$pdo->prepare('INSERT INTO payments (order_id, token, email, name, category, amount, currency, status)
               VALUES (?,?,?,?,?,?,?,\'created\')')
    ->execute([
        $order['id'], $token, $data['email'],
        $data['first_name'] . ' ' . $data['last_name'],
        $data['category'], $amountPaise, 'INR',
    ]);

json_response([
    'success'          => true,
    'payment_required' => true,
    'dev_mode'         => razorpay_dev_mode(),
    'key_id'           => RAZORPAY_KEY_ID,
    'order_id'         => $order['id'],
    'amount'           => $amountPaise,
    'amount_display'   => '₹' . number_format($amountPaise / 100),
    'currency'         => 'INR',
    'event_name'       => EVENT_NAME,
    'category'         => $data['category'],
    'prefill'          => [
        'name'    => $data['first_name'] . ' ' . $data['last_name'],
        'email'   => $data['email'],
        'contact' => $data['phone'],
    ],
]);
