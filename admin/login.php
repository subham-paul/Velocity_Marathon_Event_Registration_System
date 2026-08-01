<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired — please try again.';
    } else {
        // Throttle: 5 failed attempts → 10 minute lockout (per session)
        $attempts  = $_SESSION['login_attempts'] ?? 0;
        $lockUntil = $_SESSION['login_lock_until'] ?? 0;

        if ($lockUntil > time()) {
            $error = 'Too many failed attempts. Try again in ' . ceil(($lockUntil - time()) / 60) . ' minute(s).';
        } else {
            $username = clean($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
                db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
                header('Location: dashboard.php');
                exit;
            }

            $_SESSION['login_attempts'] = ++$attempts;
            if ($attempts >= 5) {
                $_SESSION['login_lock_until'] = time() + 600;
                $_SESSION['login_attempts'] = 0;
                $error = 'Too many failed attempts. Locked for 10 minutes.';
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — <?= e(EVENT_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  .login-wrap { min-height: 100vh; display: flex; align-items: center; }
  .login-card { max-width: 420px; width: 100%; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="container d-flex justify-content-center">
    <div class="login-card reg-card">
      <div class="text-center mb-4">
        <div class="navbar-brand fs-3"><i class="bi bi-lightning-charge-fill text-accent"></i> VELOCITY<span class="text-accent">26</span></div>
        <p class="text-secondary small mb-0">Race Control · Admin Panel</p>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="mb-3">
          <label class="form-label" for="username">Username</label>
          <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-accent w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
      </form>
      <p class="text-center small text-secondary mt-4 mb-0"><a href="../index.php" class="text-secondary"><i class="bi bi-arrow-left me-1"></i>Back to website</a></p>
    </div>
  </div>
</div>
</body>
</html>
