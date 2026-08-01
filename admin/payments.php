<?php
$pageTitle = 'Payments';
$activeNav = 'payments';
require_once __DIR__ . '/includes/layout_top.php';

$pdo = db();

/* ---------- Stats ---------- */
$collected = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$paidCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='paid'")->fetchColumn();
$todayAmt  = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND DATE(paid_at)=CURDATE()")->fetchColumn();
$pendCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='created'")->fetchColumn();
$failCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='failed'")->fetchColumn();

$byCat = $pdo->query("SELECT category, COUNT(*) c, SUM(amount) amt FROM payments WHERE status='paid' GROUP BY category ORDER BY amt DESC")->fetchAll();

/* ---------- Filters / pagination ---------- */
$search  = clean($_GET['q'] ?? '');
$fStatus = clean($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(order_id LIKE ? OR payment_id LIKE ? OR email LIKE ? OR name LIKE ? OR reg_id LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($fStatus, ['created', 'paid', 'failed'], true)) {
    $where[] = 'status = ?';
    $params[] = $fStatus;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments $whereSql");
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM payments $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$qs = fn(array $o = []) => http_build_query(array_merge(['q' => $search, 'status' => $fStatus, 'page' => $page], $o));
$inr = fn(int $paise) => '₹' . number_format($paise / 100);

$badge = [
    'paid'    => '<span class="badge text-bg-success">Paid</span>',
    'created' => '<span class="badge text-bg-warning">Initiated</span>',
    'failed'  => '<span class="badge text-bg-danger">Failed</span>',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="mb-0">Payments</h4>
    <small class="text-secondary"><?= $totalRows ?> record<?= $totalRows === 1 ? '' : 's' ?> · Razorpay <?= razorpay_dev_mode() ? '<span class="badge text-bg-warning">DEV MODE — no live keys</span>' : '<span class="badge text-bg-success">LIVE</span>' ?></small>
  </div>
</div>

<!-- stats -->
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="icon" style="background:rgba(63,185,80,.15);color:#3fb950;"><i class="bi bi-currency-rupee"></i></div>
      <div><h3><?= $inr($collected) ?></h3><small class="text-secondary">Total Collected</small></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="icon" style="background:rgba(88,166,255,.15);color:#58a6ff;"><i class="bi bi-receipt"></i></div>
      <div><h3><?= $paidCount ?></h3><small class="text-secondary">Successful Payments</small></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="icon" style="background:rgba(249,115,22,.15);color:#f97316;"><i class="bi bi-calendar-day"></i></div>
      <div><h3><?= $inr($todayAmt) ?></h3><small class="text-secondary">Collected Today</small></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card d-flex align-items-center gap-3">
      <div class="icon" style="background:rgba(210,153,255,.15);color:#d299ff;"><i class="bi bi-hourglass-split"></i></div>
      <div><h3><?= $pendCount ?> / <?= $failCount ?></h3><small class="text-secondary">Initiated / Failed</small></div>
    </div>
  </div>
</div>

<?php if ($byCat): ?>
<div class="chart-card mb-3">
  <h6><i class="bi bi-flag me-1"></i>Revenue by Category</h6>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Category</th><th class="text-end">Paid Registrations</th><th class="text-end">Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($byCat as $c): ?>
        <tr>
          <td><?= e($c['category']) ?></td>
          <td class="text-end"><?= (int)$c['c'] ?></td>
          <td class="text-end fw-semibold text-success"><?= $inr((int)$c['amt']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- filters -->
<form class="chart-card mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label small" for="q">Search</label>
      <input type="text" class="form-control form-control-sm" id="q" name="q" value="<?= e($search) ?>" placeholder="Order ID, Payment ID, Reg ID, name, email…">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small" for="status">Status</label>
      <select class="form-select form-select-sm" id="status" name="status">
        <option value="">All</option>
        <option value="paid" <?= $fStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="created" <?= $fStatus === 'created' ? 'selected' : '' ?>>Initiated</option>
        <option value="failed" <?= $fStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
      </select>
    </div>
    <div class="col-6 col-md-3 d-flex gap-2">
      <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
      <a class="btn btn-outline-secondary btn-sm" href="payments.php" title="Reset"><i class="bi bi-x-lg"></i></a>
    </div>
  </div>
</form>

<!-- table -->
<div class="chart-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Date</th><th>Participant</th><th>Category</th><th>Amount</th>
          <th>Status</th><th>Reg ID</th><th>Order / Payment ID</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-secondary py-5">No payment records match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><small class="text-secondary"><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></small></td>
          <td><?= e($r['name']) ?><br><small class="text-secondary"><?= e($r['email']) ?></small></td>
          <td><small><?= e($r['category']) ?></small></td>
          <td class="fw-semibold <?= $r['status'] === 'paid' ? 'text-success' : '' ?>"><?= $inr((int)$r['amount']) ?></td>
          <td><?= $badge[$r['status']] ?? e($r['status']) ?></td>
          <td>
            <?php if ($r['reg_id']): ?>
              <a class="text-accent fw-semibold text-decoration-none" href="participants.php?q=<?= e(urlencode($r['reg_id'])) ?>"><?= e($r['reg_id']) ?></a>
            <?php else: ?><span class="text-secondary">—</span><?php endif; ?>
          </td>
          <td>
            <small class="text-secondary d-block" style="font-size:.72rem;">O: <?= e($r['order_id']) ?></small>
            <small class="text-secondary d-block" style="font-size:.72rem;">P: <?= e($r['payment_id'] ?: '—') ?></small>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= e($qs(['page' => $page - 1])) ?>">&laquo;</a></li>
      <?php for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs(['page' => $p])) ?>"><?= $p ?></a></li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= e($qs(['page' => $page + 1])) ?>">&raquo;</a></li>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
