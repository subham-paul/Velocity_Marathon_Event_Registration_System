<?php
/** Shared admin shell. Set $pageTitle and $activeNav before including. */
require_once __DIR__ . '/auth.php';
require_admin();
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — Admin · <?= e(EVENT_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  body { background: var(--bg); }
  .admin-nav { background: var(--card); border-bottom: 1px solid var(--border); }
  .admin-nav .nav-link { color: var(--text-dim); font-weight: 500; border-radius: 8px; padding: 8px 16px; }
  .admin-nav .nav-link:hover { color: #fff; }
  .admin-nav .nav-link.active { background: rgba(249,115,22,.15); color: var(--accent); }
  .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; height: 100%; }
  .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
  .stat-card h3 { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; margin: 0; }
  .chart-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; height: 100%; }
  .chart-card h6 { color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; font-size: .78rem; margin-bottom: 16px; }
  .table { --bs-table-bg: transparent; }
  .table thead th { color: var(--text-dim); font-size: .78rem; text-transform: uppercase; letter-spacing: 1px; border-color: var(--border); }
  .table td { border-color: var(--border); vertical-align: middle; }
</style>
</head>
<body>
<nav class="admin-nav navbar navbar-expand-lg sticky-top py-2">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand fs-5" href="dashboard.php"><i class="bi bi-lightning-charge-fill text-accent"></i> VELOCITY<span class="text-accent">26</span> <span class="text-secondary fw-normal fs-6">· Race Control</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenu" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="adminMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'participants' ? 'active' : '' ?>" href="participants.php"><i class="bi bi-people me-1"></i>Participants</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'payments' ? 'active' : '' ?>" href="payments.php"><i class="bi bi-credit-card me-1"></i>Payments</a></li>
        <li class="nav-item"><a class="nav-link <?= $activeNav === 'scan' ? 'active' : '' ?>" href="scan.php"><i class="bi bi-qr-code-scan me-1"></i>Scan QR</a></li>
        <li class="nav-item ms-lg-3"><span class="text-secondary small"><i class="bi bi-person-circle me-1"></i><?= e($adminName) ?></span></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container-fluid px-3 px-lg-4 py-4">
