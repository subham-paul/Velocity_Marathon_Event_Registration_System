# 🏃 Velocity Marathon 2026 — Event Registration System

A complete marathon registration platform: dark-themed long-scrolling landing page,
email-OTP verified registration, QR-coded entry passes, PHPMailer confirmation emails,
and an admin panel with live analytics charts and a camera QR scanner.

**Stack:** PHP 8 · MySQL · Bootstrap 5 · Chart.js · PHPMailer · chillerlan/php-qrcode · html5-qrcode

---

## Quick Start (Laragon)

1. **Install dependencies** (already done if `vendor/` exists):
   ```
   composer install
   ```
2. **Create the database** (idempotent — safe to re-run):
   ```
   php setup/setup.php
   ```
3. **Configure email** — open `includes/config.php` and set `SMTP_PASS` to a
   [Gmail App Password](https://myaccount.google.com/apppasswords) (requires 2FA on the account).
   > 💡 **Dev mode:** while `SMTP_PASS` is empty, emails are **not** sent — they are written to
   > `logs/mail.log` (including the OTP) so you can test the whole flow without SMTP.
4. **Configure Razorpay** — in `includes/config.php` set `RAZORPAY_KEY_ID` and
   `RAZORPAY_KEY_SECRET` (Dashboard → Settings → API Keys; use `rzp_test_…` keys for testing).
   Category fees are defined in `CATEGORY_FEES` (server-side source of truth).
   > 💡 **Dev mode:** while the keys are empty, the gateway is skipped and a **simulated**
   > payment is recorded (`DEVORD_…` / `DEVPAY_…`) so the flow stays testable offline.
5. Open **http://localhost/marathon_live_pr** (or `http://marathon_live_pr.test` with Laragon pretty URLs).

## Admin Panel

- URL: `http://localhost/marathon_live_pr/admin/login.php`
- Default login: **admin / Admin@123** — ⚠ change it immediately:
  ```sql
  UPDATE admins SET password_hash = '<hash>' WHERE username = 'admin';
  ```
  Generate a hash with: `php -r "echo password_hash('NewPassword', PASSWORD_DEFAULT);"`

### Features
| Page | What it does |
|---|---|
| Dashboard | KPI cards (incl. revenue), gender & category doughnuts with data legends, event snapshot, 14-day trend, age/T-shirt/blood/city data bars, recent registrations |
| Participants | Search, filter, paginate, view full details + QR, check-in / undo, delete, CSV export |
| Payments | Revenue stats, revenue by category, every payment record (initiated/paid/failed) with order & payment IDs, search + filters |
| Scan QR | Live camera scanner (html5-qrcode) + manual Reg ID lookup; validates the HMAC signature embedded in each QR and flags duplicates already checked in |

## Registration Flow

1. Runner fills the form (category dropdown shows the fee) → server validates →
   OTP emailed (10 min expiry, 5 attempts, 3 resends).
2. OTP verified → a **Razorpay order** is created for the selected category's fee
   (amount always comes from `CATEGORY_FEES` on the server, never the client) and
   Razorpay Checkout opens. The runner gets a 30-minute payment window.
3. Payment captured → the **checkout signature is verified server-side**
   (HMAC-SHA256 of `order_id|payment_id` with the key secret) → only then is the
   registration saved to MySQL → unique Reg ID (`VM26-XXXXXX`) → QR PNG rendered →
   confirmation email sent with the QR + payment receipt (amount & payment ID).
4. The QR encodes human-readable participant details **plus an HMAC-SHA256 signature**,
   so any standard scanner shows the runner's details, while the admin scanner also
   detects forged/tampered codes.
5. Every payment attempt is recorded in the `payments` table
   (initiated / paid / failed) and shown in the admin **Payments** page.

## Security

- PDO prepared statements everywhere (no string-built SQL)
- CSRF tokens on every form and AJAX endpoint
- OTPs and passwords stored as bcrypt hashes; OTP throttling + resend limits
- Admin login lockout (5 fails → 10 min), `session_regenerate_id` on login, httponly cookies
- All output escaped with `htmlspecialchars`; server-side re-validation of every field
- `includes/`, `logs/`, `vendor/` blocked from the web; `uploads/` serves images only
- QR payloads HMAC-signed (`QR_SECRET` in config — change it for production)

## Project Structure

```
├── index.php               Landing page + registration form
├── api/                    register / verify_otp / resend_otp (JSON)
├── admin/
│   ├── login.php           Admin login (throttled)
│   ├── dashboard.php       Charts & stats (Chart.js)
│   ├── participants.php    Manage registrations
│   ├── scan.php            Camera QR scanner
│   ├── export.php          CSV export
│   └── api/verify_scan.php QR/RegID verification + check-in
├── includes/               config, db, functions, mailer
├── setup/setup.php         DB installer + default admin
├── uploads/qrcodes/        Generated QR PNGs
└── logs/                   mail.log (dev mode), error logs
```
"# Velocity_Marathon_Event_Registration_System" 
