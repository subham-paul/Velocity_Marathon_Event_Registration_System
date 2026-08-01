<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once BASE_PATH . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

/* ================= JSON responses ================= */

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): never
{
    json_response(array_merge(['success' => false, 'message' => $message], $extra), $status);
}

/* ================= CSRF ================= */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ================= Input helpers ================= */

function clean(?string $value): string
{
    return trim((string)$value);
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ================= OTP ================= */

function generate_otp(): string
{
    $max = (10 ** OTP_LENGTH) - 1;
    return str_pad((string)random_int(0, $max), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/* ================= Registration ID ================= */

function generate_reg_id(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no confusing 0/O/1/I
    do {
        $suffix = '';
        for ($i = 0; $i < 6; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $regId = EVENT_YEAR_TAG . '-' . $suffix;
        $stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE reg_id = ?');
        $stmt->execute([$regId]);
    } while ($stmt->fetch());
    return $regId;
}

/* ================= QR payload + signature =================
 * The QR encodes human-readable participant details plus an HMAC
 * signature so the admin scanner can detect forged/tampered codes.
 */

function qr_signature(string $regId): string
{
    return strtoupper(substr(hash_hmac('sha256', $regId, QR_SECRET), 0, 12));
}

function build_qr_payload(array $r): string
{
    return implode("\n", [
        EVENT_NAME . ' — OFFICIAL ENTRY',
        'Reg ID: '    . $r['reg_id'],
        'Name: '      . $r['first_name'] . ' ' . $r['last_name'],
        'Category: '  . $r['category'],
        'Gender: '    . $r['gender'],
        'T-Shirt: '   . $r['tshirt_size'],
        'Blood Grp: ' . $r['blood_group'],
        'Phone: '     . $r['phone'],
        'Event: '     . EVENT_DATE . ' | ' . EVENT_VENUE,
        'SIG: '       . qr_signature($r['reg_id']),
    ]);
}

function generate_qr_png(array $r): string
{
    if (!is_dir(QR_DIR)) {
        mkdir(QR_DIR, 0775, true);
    }
    $options = new QROptions([
        'outputInterface' => QRGdImagePNG::class,
        'outputBase64'    => false,
        'scale'           => 6,
        'quietzoneSize'   => 3,
    ]);
    $file = QR_DIR . '/' . $r['reg_id'] . '.png';
    (new QRCode($options))->render(build_qr_payload($r), $file);
    return 'uploads/qrcodes/' . $r['reg_id'] . '.png';
}

/* ================= Razorpay =================
 * Dev mode: while RAZORPAY_KEY_ID is empty, no gateway call is made — a
 * simulated order/payment is used so the flow stays testable locally.
 */

function razorpay_dev_mode(): bool
{
    return RAZORPAY_KEY_ID === '' || RAZORPAY_KEY_SECRET === '';
}

function category_fee(string $category): int
{
    $fees = CATEGORY_FEES;
    if (!isset($fees[$category])) {
        throw new RuntimeException("Unknown category: $category");
    }
    return (int)$fees[$category]; // INR
}

/** Create a Razorpay order. Returns ['id' => order_id] (or a DEV order in dev mode). */
function razorpay_create_order(int $amountPaise, string $receipt): array
{
    if (razorpay_dev_mode()) {
        return ['id' => 'DEVORD_' . bin2hex(random_bytes(10)), 'dev_mode' => true];
    }

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'receipt'  => $receipt,
        ]),
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false || $http !== 200) {
        log_line('payment_error.log', "order create failed (HTTP $http): " . ($err ?: (string)$body));
        throw new RuntimeException('Payment gateway is unavailable right now.');
    }
    $order = json_decode($body, true);
    if (empty($order['id'])) {
        log_line('payment_error.log', 'order create: unexpected response ' . $body);
        throw new RuntimeException('Payment gateway returned an invalid response.');
    }
    return $order;
}

/** Verify the checkout signature: HMAC-SHA256(order_id|payment_id, key_secret). */
function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool
{
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expected, $signature);
}

/* ================= Misc ================= */

function log_line(string $file, string $line): void
{
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0775, true);
    }
    file_put_contents(LOG_DIR . '/' . $file, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
}

function age_from_dob(string $dob): int
{
    return (new DateTime($dob))->diff(new DateTime('today'))->y;
}
