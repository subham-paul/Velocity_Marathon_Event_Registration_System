<?php
/**
 * Resend the OTP for an existing pending verification (max OTP_MAX_RESENDS).
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    json_error('Invalid session token. Please refresh the page and try again.', 403);
}

$token = clean($_POST['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) json_error('Invalid verification token.', 400);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM otp_verifications WHERE token = ?');
$stmt->execute([$token]);
$pending = $stmt->fetch();

if (!$pending || strtotime($pending['expires_at']) < time()) {
    json_error('This verification session has expired. Please submit the form again.', 410);
}
if ((int)$pending['resend_count'] >= OTP_MAX_RESENDS) {
    json_error('Resend limit reached. Please submit the form again.', 429);
}

$otp = generate_otp();
$pdo->prepare('UPDATE otp_verifications
               SET otp_hash = ?, attempts = 0, resend_count = resend_count + 1,
                   expires_at = NOW() + INTERVAL ' . (int)OTP_EXPIRY_MINUTES . ' MINUTE
               WHERE id = ?')
    ->execute([password_hash($otp, PASSWORD_DEFAULT), $pending['id']]);

$data = json_decode($pending['payload'], true);
if (!send_otp_email($pending['email'], $data['first_name'] ?? 'Runner', $otp)) {
    json_error('Could not send the email right now. Please try again shortly.', 502);
}

json_response(['success' => true, 'message' => 'A new OTP has been sent to your email.']);
