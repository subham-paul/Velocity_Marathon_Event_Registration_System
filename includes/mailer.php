<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send an HTML email via PHPMailer.
 * Dev mode: when SMTP_PASS is empty the email body is appended to
 * logs/mail.log instead of being sent, so the flow is testable offline.
 *
 * $embeddedImages: ['cid' => 'absolute/path/to/file.png']
 * $attachments:    ['absolute/path/file.png', ...]
 */
function send_mail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    array $embeddedImages = [],
    array $attachments = []
): bool {
    if (SMTP_PASS === '') {
        log_line('mail.log', "DEV-MODE EMAIL\nTo: $toName <$toEmail>\nSubject: $subject\n" . strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)) . "\nAttachments: " . implode(', ', $attachments) . "\n" . str_repeat('-', 60));
        return true;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

        foreach ($embeddedImages as $cid => $path) {
            $mail->addEmbeddedImage($path, $cid);
        }
        foreach ($attachments as $path) {
            $mail->addAttachment($path);
        }

        $mail->send();
        return true;
    } catch (MailException $ex) {
        log_line('mail_error.log', "To: $toEmail | Subject: $subject | Error: " . $mail->ErrorInfo);
        return false;
    }
}

/* ---------- Templates ---------- */

function email_shell(string $inner): string
{
    $event = e(EVENT_NAME);
    return <<<HTML
<div style="margin:0;padding:24px;background:#0d1117;font-family:Segoe UI,Arial,sans-serif;">
  <div style="max-width:560px;margin:0 auto;background:#161b22;border:1px solid #30363d;border-radius:14px;overflow:hidden;">
    <div style="background:linear-gradient(135deg,#f97316,#ea580c);padding:26px 32px;">
      <h1 style="margin:0;color:#fff;font-size:22px;letter-spacing:1px;">🏃 {$event}</h1>
    </div>
    <div style="padding:32px;color:#c9d1d9;font-size:15px;line-height:1.7;">
      {$inner}
    </div>
    <div style="padding:18px 32px;border-top:1px solid #30363d;color:#8b949e;font-size:12px;">
      This is an automated message — please do not reply.<br>{$event} · Organized by Velocity Sports Foundation
    </div>
  </div>
</div>
HTML;
}

function send_otp_email(string $email, string $name, string $otp): bool
{
    $safeName = e($name);
    $minutes  = OTP_EXPIRY_MINUTES;
    $inner = <<<HTML
<p>Hi <strong style="color:#fff;">{$safeName}</strong>,</p>
<p>Use the One-Time Password below to verify your email and complete your marathon registration:</p>
<div style="text-align:center;margin:26px 0;">
  <span style="display:inline-block;background:#0d1117;border:1px dashed #f97316;border-radius:10px;padding:14px 34px;font-size:32px;letter-spacing:12px;color:#f97316;font-weight:700;">{$otp}</span>
</div>
<p>This code expires in <strong style="color:#fff;">{$minutes} minutes</strong>. If you did not request it, you can safely ignore this email.</p>
HTML;
    return send_mail($email, $name, 'Your OTP — ' . EVENT_NAME, email_shell($inner));
}

function send_confirmation_email(array $r, string $qrAbsolutePath, array $payment = []): bool
{
    $safeName = e($r['first_name'] . ' ' . $r['last_name']);
    $regId    = e($r['reg_id']);
    $cat      = e($r['category']);
    $tshirt   = e($r['tshirt_size']);
    $date     = e(EVENT_DATE);
    $venue    = e(EVENT_VENUE);
    $payRows  = '';
    if ($payment) {
        $amount = e($payment['amount'] ?? '');
        $payId  = e($payment['payment_id'] ?? '');
        $payRows = <<<HTML
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">Amount Paid</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#3fb950;font-weight:700;">{$amount}</td></tr>
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">Payment ID</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#fff;">{$payId}</td></tr>
HTML;
    }
    $inner = <<<HTML
<p>Hi <strong style="color:#fff;">{$safeName}</strong>,</p>
<p>🎉 Your registration is <strong style="color:#3fb950;">CONFIRMED</strong>! Here are your details:</p>
<table style="width:100%;border-collapse:collapse;margin:18px 0;">
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">Registration ID</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#f97316;font-weight:700;font-size:17px;">{$regId}</td></tr>
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">Race Category</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#fff;">{$cat}</td></tr>
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">T-Shirt Size</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#fff;">{$tshirt}</td></tr>
{$payRows}
  <tr><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#8b949e;">Event Date</td><td style="padding:9px 0;border-bottom:1px solid #30363d;color:#fff;">{$date}</td></tr>
  <tr><td style="padding:9px 0;color:#8b949e;">Venue</td><td style="padding:9px 0;color:#fff;">{$venue}</td></tr>
</table>
<p style="text-align:center;margin:24px 0 8px;">Show this QR code at the race-kit collection desk:</p>
<div style="text-align:center;margin-bottom:20px;">
  <img src="cid:qrcode" alt="Registration QR Code" style="width:220px;height:220px;border:6px solid #fff;border-radius:10px;">
</div>
<p style="color:#8b949e;font-size:13px;">Your QR code is also attached to this email. Keep it handy on race day — our team will scan it to verify your entry.</p>
HTML;
    return send_mail(
        $r['email'],
        $r['first_name'] . ' ' . $r['last_name'],
        "Registration Confirmed [{$r['reg_id']}] — " . EVENT_NAME,
        email_shell($inner),
        ['qrcode' => $qrAbsolutePath],
        [$qrAbsolutePath]
    );
}
