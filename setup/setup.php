<?php
/**
 * One-time installer: creates the database, tables and default admin.
 * Run:  php setup/setup.php          (CLI)
 * or visit http://localhost/marathon_live_pr/setup/setup.php once, then delete/keep — it is idempotent.
 *
 * Default admin →  username: admin   password: Admin@123   (change after first login!)
 */
require_once dirname(__DIR__) . '/includes/config.php';

$isCli = PHP_SAPI === 'cli';
$out = fn(string $m) => print($m . ($isCli ? PHP_EOL : '<br>'));

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $out('✔ Database `' . DB_NAME . '` ready');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $out('✔ Table admins');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reg_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(60) NOT NULL,
            last_name VARCHAR(60) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            gender ENUM('Male','Female','Other') NOT NULL,
            dob DATE NOT NULL,
            category ENUM('5K Fun Run','10K Challenge','21K Half Marathon','42K Full Marathon') NOT NULL,
            tshirt_size ENUM('XS','S','M','L','XL','XXL') NOT NULL,
            blood_group VARCHAR(5) NOT NULL,
            emergency_name VARCHAR(120) NOT NULL,
            emergency_phone VARCHAR(20) NOT NULL,
            city VARCHAR(80) NOT NULL,
            state VARCHAR(80) NOT NULL,
            address VARCHAR(255) NOT NULL,
            qr_path VARCHAR(255) NOT NULL DEFAULT '',
            status ENUM('confirmed','checked_in') NOT NULL DEFAULT 'confirmed',
            checked_in_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_gender (gender),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB
    ");
    $out('✔ Table registrations');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otp_verifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            token CHAR(64) NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            payload JSON NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            resend_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB
    ");
    // Upgrade path for installs created before the payment feature.
    $col = $pdo->query("SHOW COLUMNS FROM otp_verifications LIKE 'verified'")->fetch();
    if (!$col) {
        $pdo->exec('ALTER TABLE otp_verifications ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0 AFTER resend_count');
        $out('✔ Column otp_verifications.verified added');
    }
    $out('✔ Table otp_verifications');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(64) NOT NULL UNIQUE,
            payment_id VARCHAR(64) NULL,
            signature VARCHAR(191) NULL,
            token CHAR(64) NOT NULL,
            email VARCHAR(190) NOT NULL,
            name VARCHAR(130) NOT NULL,
            category VARCHAR(40) NOT NULL,
            amount INT UNSIGNED NOT NULL COMMENT 'in paise',
            currency CHAR(3) NOT NULL DEFAULT 'INR',
            status ENUM('created','paid','failed') NOT NULL DEFAULT 'created',
            reg_id VARCHAR(20) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME NULL,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_reg (reg_id)
        ) ENGINE=InnoDB
    ");
    $out('✔ Table payments');

    $stmt = $pdo->prepare('SELECT 1 FROM admins WHERE username = ?');
    $stmt->execute(['admin']);
    if (!$stmt->fetch()) {
        $pdo->prepare('INSERT INTO admins (username, password_hash, full_name) VALUES (?,?,?)')
            ->execute(['admin', password_hash('Admin@123', PASSWORD_DEFAULT), 'Race Director']);
        $out('✔ Default admin created  →  admin / Admin@123  (CHANGE THIS PASSWORD)');
    } else {
        $out('✔ Admin already exists — skipped');
    }

    $out('');
    $out('Setup complete. Open ' . BASE_URL . ' to view the site.');
} catch (PDOException $e) {
    $out('✖ Setup failed: ' . $e->getMessage());
    exit(1);
}
