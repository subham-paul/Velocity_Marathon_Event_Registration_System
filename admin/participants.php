<?php
$pageTitle = 'Participants';
$activeNav = 'participants';
require_once __DIR__ . '/includes/layout_top.php';

$pdo = db();
$flash = '';
$flashType = 'success';

/* ---------- Actions (check-in toggle / delete) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $flash = 'Session expired — action not performed.';
        $flashType = 'danger';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM registrations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            $flash = 'Registration not found.';
            $flashType = 'danger';
        } elseif ($action === 'checkin') {
            $pdo->prepare("UPDATE registrations SET status='checked_in', checked_in_at=NOW() WHERE id=?")->execute([$id]);
            $flash = $row['reg_id'] . ' marked as checked in.';
        } elseif ($action === 'undo_checkin') {
            $pdo->prepare("UPDATE registrations SET status='confirmed', checked_in_at=NULL WHERE id=?")->execute([$id]);
            $flash = 'Check-in reverted for ' . $row['reg_id'] . '.';
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM registrations WHERE id=?')->execute([$id]);
            $qrFile = BASE_PATH . '/' . $row['qr_path'];
            if ($row['qr_path'] && is_file($qrFile)) {
                unlink($qrFile);
            }
            $flash = 'Registration ' . $row['reg_id'] . ' deleted.';
        }
    }
}

/* ---------- Filters / search / pagination ---------- */
$search   = clean($_GET['q'] ?? '');
$fCat     = clean($_GET['category'] ?? '');
$fGender  = clean($_GET['gender'] ?? '');
$fStatus  = clean($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(reg_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($fCat, ['5K Fun Run', '10K Challenge', '21K Half Marathon', '42K Full Marathon'], true)) {
    $where[] = 'category = ?';
    $params[] = $fCat;
}
if (in_array($fGender, ['Male', 'Female', 'Other'], true)) {
    $where[] = 'gender = ?';
    $params[] = $fGender;
}
if (in_array($fStatus, ['confirmed', 'checked_in'], true)) {
    $where[] = 'status = ?';
    $params[] = $fStatus;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations $whereSql");
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM registrations $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$csrf = csrf_token();
$qs = fn(array $overrides = []) => http_build_query(array_merge(
    ['q' => $search, 'category' => $fCat, 'gender' => $fGender, 'status' => $fStatus, 'page' => $page],
    $overrides
));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="mb-0">Participants</h4>
    <small class="text-secondary"><?= $totalRows ?> registration<?= $totalRows === 1 ? '' : 's' ?> found</small>
  </div>
  <a class="btn btn-outline-accent btn-sm" href="export.php?<?= e($qs()) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= e($flashType) ?> py-2 small"><i class="bi bi-info-circle me-2"></i><?= e($flash) ?></div>
<?php endif; ?>

<!-- filters -->
<form class="chart-card mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small" for="q">Search</label>
      <input type="text" class="form-control form-control-sm" id="q" name="q" value="<?= e($search) ?>" placeholder="Reg ID, name, email, phone…">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="category">Category</label>
      <select class="form-select form-select-sm" id="category" name="category">
        <option value="">All</option>
        <?php foreach (['5K Fun Run', '10K Challenge', '21K Half Marathon', '42K Full Marathon'] as $c): ?>
        <option <?= $fCat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="gender">Gender</label>
      <select class="form-select form-select-sm" id="gender" name="gender">
        <option value="">All</option>
        <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
        <option <?= $fGender === $g ? 'selected' : '' ?>><?= e($g) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small" for="status">Status</label>
      <select class="form-select form-select-sm" id="status" name="status">
        <option value="">All</option>
        <option value="confirmed" <?= $fStatus === 'confirmed' ? 'selected' : '' ?>>Registered</option>
        <option value="checked_in" <?= $fStatus === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
      </select>
    </div>
    <div class="col-6 col-md-2 d-flex gap-2">
      <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
      <a class="btn btn-outline-secondary btn-sm" href="participants.php" title="Reset"><i class="bi bi-x-lg"></i></a>
    </div>
  </div>
</form>

<!-- table -->
<div class="chart-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Reg ID</th><th>Name</th><th>Category</th><th>Gender</th><th>Phone</th>
          <th>City</th><th>Status</th><th>Registered</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-secondary py-5">No registrations match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="text-accent fw-semibold"><?= e($r['reg_id']) ?></span></td>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?><br><small class="text-secondary"><?= e($r['email']) ?></small></td>
          <td><small><?= e($r['category']) ?></small></td>
          <td><small><?= e($r['gender']) ?></small></td>
          <td><small><?= e($r['phone']) ?></small></td>
          <td><small><?= e($r['city']) ?></small></td>
          <td>
            <?php if ($r['status'] === 'checked_in'): ?>
              <span class="badge text-bg-success">Checked In</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Registered</span>
            <?php endif; ?>
          </td>
          <td><small class="text-secondary"><?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></small></td>
          <td class="text-end">
            <div class="d-inline-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-secondary" title="View details"
                data-bs-toggle="modal" data-bs-target="#viewModal"
                data-participant='<?= e(json_encode([
                    'reg_id' => $r['reg_id'],
                    'name' => $r['first_name'] . ' ' . $r['last_name'],
                    'email' => $r['email'],
                    'phone' => $r['phone'],
                    'gender' => $r['gender'],
                    'dob' => $r['dob'],
                    'age' => age_from_dob($r['dob']),
                    'category' => $r['category'],
                    'tshirt' => $r['tshirt_size'],
                    'blood' => $r['blood_group'],
                    'emg_name' => $r['emergency_name'],
                    'emg_phone' => $r['emergency_phone'],
                    'address' => $r['address'] . ', ' . $r['city'] . ', ' . $r['state'],
                    'qr' => '../' . $r['qr_path'],
                    'status' => $r['status'],
                    'checked_in_at' => $r['checked_in_at'],
                    'created_at' => $r['created_at'],
                ])) ?>'><i class="bi bi-eye"></i></button>
              <?php if ($r['status'] === 'confirmed'): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="action" value="checkin">
                <button class="btn btn-sm btn-outline-success" title="Mark checked in"><i class="bi bi-check2-circle"></i></button>
              </form>
              <?php else: ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="action" value="undo_checkin">
                <button class="btn btn-sm btn-outline-warning" title="Undo check-in"><i class="bi bi-arrow-counterclockwise"></i></button>
              </form>
              <?php endif; ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete registration <?= e($r['reg_id']) ?>? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
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

<!-- view modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title"><i class="bi bi-person-vcard text-accent me-2"></i>Participant Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-md-4 text-center">
            <img id="vQr" src="" alt="QR code" class="img-fluid rounded border border-3 border-light bg-white" style="max-width:200px;">
            <div class="reg-id-box mt-3"><span id="vRegId" class="reg-id fs-4"></span></div>
            <span id="vStatus" class="badge mt-2"></span>
          </div>
          <div class="col-md-8">
            <table class="table table-sm mb-0">
              <tbody id="vBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('viewModal').addEventListener('show.bs.modal', e => {
  const d = JSON.parse(e.relatedTarget.dataset.participant);
  const esc = s => { const el = document.createElement('span'); el.textContent = s ?? '—'; return el.innerHTML; };
  document.getElementById('vQr').src = d.qr;
  document.getElementById('vRegId').textContent = d.reg_id;
  const st = document.getElementById('vStatus');
  st.textContent = d.status === 'checked_in' ? 'Checked In · ' + d.checked_in_at : 'Registered';
  st.className = 'badge mt-2 ' + (d.status === 'checked_in' ? 'text-bg-success' : 'text-bg-secondary');
  const fields = [
    ['Name', d.name], ['Email', d.email], ['Phone', d.phone],
    ['Gender', d.gender], ['Date of Birth', `${d.dob} (${d.age} yrs)`],
    ['Category', d.category], ['T-Shirt', d.tshirt], ['Blood Group', d.blood],
    ['Emergency Contact', `${d.emg_name} — ${d.emg_phone}`],
    ['Address', d.address], ['Registered At', d.created_at],
  ];
  document.getElementById('vBody').innerHTML = fields
    .map(([k, v]) => `<tr><td class="text-secondary" style="width:40%">${k}</td><td>${esc(v)}</td></tr>`)
    .join('');
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
