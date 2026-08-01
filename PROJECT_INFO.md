# Project Information & Requirements

## Project Overview
Project Name: Marathon Live Registration Platform

This project is a PHP-based event registration system for a marathon event. It supports public registration, email OTP verification, Razorpay-based payments, QR code generation for entry passes, and an admin dashboard for managing participants and payments.

## Main Features
- Landing page and registration form for participants
- OTP-based email verification with resend and attempt limits
- Secure payment flow using Razorpay
- QR code generation for participant entry passes
- Admin login and dashboard with analytics and charts
- Participant management, payment tracking, QR scan validation, and CSV export

## Tech Stack
- PHP 8.x
- MySQL / MariaDB
- Composer
- Bootstrap 5
- Chart.js
- PHPMailer
- chillerlan/php-qrcode

## Project Structure
- index.php — public landing page and registration interface
- api/ — registration and OTP-related API endpoints
- admin/ — admin dashboard, participant management, payment views, QR scanner
- includes/ — shared config, database, helper, and mailer files
- setup/setup.php — database initialization and default admin setup
- uploads/qrcodes/ — generated QR code images
- logs/ — runtime logs and mail logs

## Functional Requirements
- Collect participant details through a registration form
- Validate input data on the server side
- Send OTP emails for identity verification
- Create and verify Razorpay payment orders
- Store payment attempts and completed payments safely
- Generate unique registration IDs and QR codes
- Allow admin users to view, search, filter, and manage participants and payments
- Support QR scan verification for check-in events

## System Requirements
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Composer installed
- Web server such as Apache or Nginx (Laragon/XAMPP recommended)
- Access to SMTP credentials for email sending
- Access to Razorpay API keys for payment processing
- Write permissions for uploads/ and logs/

## Installation & Setup
1. Install Composer dependencies:
   composer install
2. Create and initialize the database:
   php setup/setup.php
3. Configure database, SMTP, and Razorpay values in includes/config.php
4. Open the project in your local web server and access the public or admin pages

## Security Notes
- Prepared SQL statements are used for database access
- CSRF protection is implemented on forms and API endpoints
- Passwords and OTPs are stored as hashed values
- Sessions are hardened with secure cookie settings
- QR payloads are signed with a secret key for tamper detection

## Notes for Development
- If SMTP credentials are not configured, mail can be logged locally to logs/mail.log
- If Razorpay credentials are empty, payments can run in local/dev simulation mode
- Generated QR images should not be committed unless explicitly required
