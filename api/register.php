<?php
/**
 * Step 1 — validate the registration form, store a pending record,
 * and email a one-time password to the participant.
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    json_error('Invalid session token. Please refresh the page and try again.', 403);
}

/* ---------- Collect & validate ---------- */
$fields = [
    'first_name', 'last_name', 'email', 'phone', 'gender', 'dob', 'category',
    'tshirt_size', 'blood_group', 'emergency_name', 'emergency_phone',
    'city', 'state', 'address',
];
$data = [];
foreach ($fields as $f) {
    $data[$f] = clean($_POST[$f] ?? '');
}

$errors = [];

if (!preg_match('/^[\p{L} .\'-]{2,60}$/u', $data['first_name'])) $errors['first_name'] = 'Enter a valid first name (2–60 letters).';
if (!preg_match('/^[\p{L} .\'-]{2,60}$/u', $data['last_name']))  $errors['last_name']  = 'Enter a valid last name (2–60 letters).';

$data['email'] = strtolower($data['email']);
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 190) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!preg_match('/^[6-9]\d{9}$/', $data['phone']))           $errors['phone'] = 'Enter a valid 10-digit mobile number.';
if (!in_array($data['gender'], ['Male', 'Female', 'Other'], true)) $errors['gender'] = 'Select your gender.';

$dobOk = false;
if ($data['dob'] && ($d = DateTime::createFromFormat('Y-m-d', $data['dob'])) && $d->format('Y-m-d') === $data['dob']) {
    $age = age_from_dob($data['dob']);
    if ($age >= 12 && $age <= 90) $dobOk = true;
}
if (!$dobOk) $errors['dob'] = 'Participants must be between 12 and 90 years old.';

$categories = ['5K Fun Run', '10K Challenge', '21K Half Marathon', '42K Full Marathon'];
if (!in_array($data['category'], $categories, true)) $errors['category'] = 'Select a race category.';
if (!in_array($data['tshirt_size'], ['XS', 'S', 'M', 'L', 'XL', 'XXL'], true)) $errors['tshirt_size'] = 'Select a T-shirt size.';
if (!in_array($data['blood_group'], ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) $errors['blood_group'] = 'Select your blood group.';
if (!preg_match('/^[\p{L} .\'-]{2,120}$/u', $data['emergency_name'])) $errors['emergency_name'] = 'Enter the emergency contact name.';
if (!preg_match('/^[6-9]\d{9}$/', $data['emergency_phone'])) $errors['emergency_phone'] = 'Enter a valid emergency contact number.';
if ($data['emergency_phone'] === $data['phone']) $errors['emergency_phone'] = 'Emergency contact must differ from your own number.';
if (mb_strlen($data['city']) < 2 || mb_strlen($data['city']) > 80)   $errors['city'] = 'Enter your city.';
if (mb_strlen($data['state']) < 2 || mb_strlen($data['state']) > 80) $errors['state'] = 'Enter your state.';
if (mb_strlen($data['address']) < 5 || mb_strlen($data['address']) > 255) $errors['address'] = 'Enter your full address (5–255 characters).';
if (empty($_POST['terms'])) $errors['terms'] = 'You must accept the terms & waiver.';

if ($errors) {
    json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
}

$pdo = db();

/* ---------- Duplicate check ---------- */
$stmt = $pdo->prepare('SELECT reg_id FROM registrations WHERE email = ?');
$stmt->execute([$data['email']]);
if ($row = $stmt->fetch()) {
    json_error('This email is already registered (Reg ID: ' . $row['reg_id'] . '). Check your inbox for the confirmation email.', 409);
}

/* ---------- Throttle: max 3 pending OTP requests per email per 15 min ---------- */
$stmt = $pdo->prepare('SELECT COUNT(*) c FROM otp_verifications WHERE email = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
$stmt->execute([$data['email']]);
if ((int)$stmt->fetch()['c'] >= 3) {
    json_error('Too many attempts for this email. Please wait 15 minutes and try again.', 429);
}

/* ---------- Create pending verification ---------- */
$pdo->prepare('DELETE FROM otp_verifications WHERE email = ? OR expires_at < NOW()')->execute([$data['email']]);

$otp   = generate_otp();
$token = bin2hex(random_bytes(32));

$pdo->prepare('INSERT INTO otp_verifications (token, email, otp_hash, payload, expires_at)
               VALUES (?,?,?,?, NOW() + INTERVAL ' . (int)OTP_EXPIRY_MINUTES . ' MINUTE)')
    ->execute([$token, $data['email'], password_hash($otp, PASSWORD_DEFAULT), json_encode($data)]);

if (!send_otp_email($data['email'], $data['first_name'], $otp)) {
    json_error('We could not send the verification email right now. Please try again in a few minutes.', 502);
}

json_response([
    'success' => true,
    'message' => 'An OTP has been sent to ' . e($data['email']) . '. It expires in ' . OTP_EXPIRY_MINUTES . ' minutes.',
    'token'   => $token,
]);
