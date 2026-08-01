<?php
/**
 * Admin QR verification endpoint.
 *  - qr_text:    raw scanned QR content OR a bare Reg ID (manual lookup)
 *  - checkin_id: mark a verified participant as checked in
 * QR payloads carry an HMAC signature (SIG line) which is validated so
 * forged or tampered QR codes are rejected.
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    json_error('Session expired — refresh the page.', 403);
}

$pdo = db();

/* ---------- Check-in action ---------- */
if (isset($_POST['checkin_id'])) {
    $id = (int)$_POST['checkin_id'];
    $stmt = $pdo->prepare('SELECT status FROM registrations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) json_error('Registration not found.', 404);
    if ($row['status'] === 'checked_in') json_error('This runner is already checked in.', 409);
    $pdo->prepare("UPDATE registrations SET status='checked_in', checked_in_at=NOW() WHERE id=?")->execute([$id]);
    json_response(['success' => true, 'message' => 'Checked in.']);
}

/* ---------- Verify scanned QR / manual Reg ID ---------- */
// Multipart form data arrives with CRLF line endings — normalize before matching.
$qrText = trim(str_replace(["\r\n", "\r"], "\n", (string)($_POST['qr_text'] ?? '')));
if ($qrText === '' || strlen($qrText) > 2000) {
    json_error('Empty or invalid QR content.', 400);
}

$regId = null;
$sig = null;

if (preg_match('/^Reg ID:\s*(\S+)$/mi', $qrText, $m)) {
    // Full QR payload
    $regId = strtoupper($m[1]);
    if (preg_match('/^SIG:\s*([A-F0-9]+)$/mi', $qrText, $s)) {
        $sig = strtoupper($s[1]);
    }
    if ($sig === null || !hash_equals(qr_signature($regId), $sig)) {
        json_error('⚠ Signature check FAILED — this QR code is forged or tampered.', 400);
    }
} elseif (preg_match('/^[A-Z0-9]{2,6}-[A-Z0-9]{4,10}$/i', $qrText)) {
    // Manual Reg ID lookup (no signature required — admin typed it)
    $regId = strtoupper($qrText);
} else {
    json_error('This QR code is not a valid ' . EVENT_NAME . ' entry pass.', 400);
}

$stmt = $pdo->prepare('SELECT * FROM registrations WHERE reg_id = ?');
$stmt->execute([$regId]);
$r = $stmt->fetch();

if (!$r) {
    json_error("No registration found for Reg ID {$regId}.", 404);
}

json_response([
    'success' => true,
    'participant' => [
        'id'            => (int)$r['id'],
        'reg_id'        => $r['reg_id'],
        'name'          => $r['first_name'] . ' ' . $r['last_name'],
        'email'         => $r['email'],
        'phone'         => $r['phone'],
        'gender'        => $r['gender'],
        'age'           => age_from_dob($r['dob']),
        'category'      => $r['category'],
        'tshirt_size'   => $r['tshirt_size'],
        'blood_group'   => $r['blood_group'],
        'city'          => $r['city'],
        'status'        => $r['status'],
        'checked_in_at' => $r['checked_in_at'],
        'qr_path'       => $r['qr_path'],
    ],
]);
