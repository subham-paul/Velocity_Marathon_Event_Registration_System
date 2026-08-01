<?php
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/layout_top.php';

$pdo = db();

/* ---------- KPIs ---------- */
$total     = (int)$pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
$today     = (int)$pdo->query('SELECT COUNT(*) FROM registrations WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$checkedIn = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'checked_in'")->fetchColumn();
$week      = (int)$pdo->query('SELECT COUNT(*) FROM registrations WHERE created_at > NOW() - INTERVAL 7 DAY')->fetchColumn();
$avgAge    = $pdo->query('SELECT ROUND(AVG(TIMESTAMPDIFF(YEAR, dob, CURDATE())),1) FROM registrations')->fetchColumn();
$checkinPct = $total ? round($checkedIn / $total * 100, 1) : 0;
$revenue   = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();
$paidCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status='paid'")->fetchColumn();

$pct = fn(int $n) => $total ? round($n / $total * 100, 1) : 0;

/* ---------- Breakdowns ---------- */
// Fixed color per entity (validated dark categorical palette) — never repainted.
$GENDER_COLORS = ['Male' => '#3987e5', 'Female' => '#199e70', 'Other' => '#c98500'];
$CAT_COLORS = [
    '5K Fun Run'        => '#3987e5',
    '10K Challenge'     => '#199e70',
    '21K Half Marathon' => '#c98500',
    '42K Full Marathon' => '#008300',
];

$fetchMap = function (string $sql) use ($pdo): array {
    $out = [];
    foreach ($pdo->query($sql) as $r) $out[$r['label']] = (int)$r['c'];
    return $out;
};

$genderMap = $fetchMap('SELECT gender AS label, COUNT(*) c FROM registrations GROUP BY gender');
$catMap    = $fetchMap('SELECT category AS label, COUNT(*) c FROM registrations GROUP BY category');
$tshirtMap = $fetchMap("SELECT tshirt_size AS label, COUNT(*) c FROM registrations GROUP BY tshirt_size ORDER BY FIELD(tshirt_size,'XS','S','M','L','XL','XXL')");
$bloodMap  = $fetchMap('SELECT blood_group AS label, COUNT(*) c FROM registrations GROUP BY blood_group ORDER BY blood_group');
$ageMap    = $fetchMap("
    SELECT CASE
        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 'Under 18'
        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 25 THEN '18–25'
        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 26 AND 35 THEN '26–35'
        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 36 AND 45 THEN '36–45'
        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 46 AND 60 THEN '46–60'
        ELSE '60+'
    END AS label, COUNT(*) c
    FROM registrations GROUP BY label
    ORDER BY FIELD(label,'Under 18','18–25','26–35','36–45','46–60','60+')");
$cityMap   = $fetchMap('SELECT city AS label, COUNT(*) c FROM registrations GROUP BY city ORDER BY c DESC LIMIT 6');

$trendRows = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%d %b') AS label, COUNT(*) AS c
    FROM registrations
    WHERE created_at > NOW() - INTERVAL 14 DAY
    GROUP BY DATE(created_at), label ORDER BY DATE(created_at)")->fetchAll();

$recent = $pdo->query('SELECT reg_id, first_name, last_name, email, category, gender, status, created_at
                       FROM registrations ORDER BY created_at DESC LIMIT 8')->fetchAll();

$topCategory = $catMap ? array_search(max($catMap), $catMap) : '—';
$topCity     = $cityMap ? array_search(max($cityMap), $cityMap) : '—';

/* Chart payloads: keep entity→color pairing even for missing labels */
$genderChart = ['labels' => array_keys($genderMap), 'data' => array_values($genderMap),
                'colors' => array_map(fn($l) => $GENDER_COLORS[$l] ?? '#8fa3b8', array_keys($genderMap))];
$catChart    = ['labels' => array_keys($catMap), 'data' => array_values($catMap),
                'colors' => array_map(fn($l) => $CAT_COLORS[$l] ?? '#8fa3b8', array_keys($catMap))];
$trendChart  = ['labels' => array_column($trendRows, 'label'), 'data' => array_map('intval', array_column($trendRows, 'c'))];

/* Helper: render an HTML data-bar list (single-hue, magnitude) */
function bar_list(array $map, string $color = '#3987e5'): string
{
    if (!$map) return '<p class="text-secondary small mb-0 py-3 text-center">No data yet.</p>';
    $max = max($map);
    $html = '';
    foreach ($map as $label => $count) {
        $w = $max ? round($count / $max * 100) : 0;
        $html .= '<div class="bar-row">'
               . '<span class="bar-label">' . e((string)$label) . '</span>'
               . '<span class="bar-track"><span class="bar-fill" style="width:' . $w . '%;background:' . $color . ';"></span></span>'
               . '<span class="bar-value">' . $count . '</span>'
               . '</div>';
    }
    return $html;
}
?>

<style>
  .kpi-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 20px 22px; height: 100%; }
  .kpi-card .kpi-label { color: var(--text-dim); font-size: .78rem; text-transform: uppercase; letter-spacing: 1px; }
  .kpi-card .kpi-value { font-size: 2rem; font-weight: 800; line-height: 1.15; color: #fff; }
  .kpi-card .kpi-sub { font-size: .8rem; color: var(--text-dim); }
  .kpi-accent { width: 3px; align-self: stretch; border-radius: 3px; }

  .panel { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 20px 22px; height: 100%; }
  .panel-title { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-dim); margin-bottom: 16px; }
  .panel-title .bi { color: var(--accent); margin-right: 6px; }

  /* data legend beside pies */
  .dlegend { list-style: none; margin: 0; padding: 0; }
  .dlegend li { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border); font-size: .88rem; }
  .dlegend li:last-child { border-bottom: 0; }
  .dlegend .swatch { width: 10px; height: 10px; border-radius: 3px; flex: 0 0 auto; }
  .dlegend .lbl { color: #cfd9e4; flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .dlegend .cnt { color: #fff; font-weight: 700; }
  .dlegend .pc { color: var(--text-dim); font-size: .78rem; min-width: 48px; text-align: right; }

  /* horizontal data bars */
  .bar-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; }
  .bar-label { flex: 0 0 74px; font-size: .82rem; color: #cfd9e4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .bar-track { flex: 1 1 auto; height: 8px; background: rgba(143,163,184,.12); border-radius: 4px; overflow: hidden; }
  .bar-fill { display: block; height: 100%; border-radius: 4px; }
  .bar-value { flex: 0 0 30px; text-align: right; font-size: .84rem; font-weight: 700; color: #fff; }

  /* snapshot facts */
  .fact { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed var(--border); font-size: .88rem; }
  .fact:last-child { border-bottom: 0; }
  .fact .k { color: var(--text-dim); }
  .fact .v { color: #fff; font-weight: 600; text-align: right; }

  .progress-thin { height: 8px; background: rgba(143,163,184,.12); }
  .table-recent td, .table-recent th { padding-top: .65rem; padding-bottom: .65rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="mb-0">Dashboard</h4>
    <small class="text-secondary"><?= e(EVENT_NAME) ?> · as of <?= e(date('d M Y, h:i A')) ?></small>
  </div>
  <div class="d-flex gap-2">
    <a href="export.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
    <a href="participants.php" class="btn btn-outline-accent btn-sm"><i class="bi bi-people me-1"></i>Manage Participants</a>
  </div>
</div>

<!-- ===== KPI row ===== -->
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="kpi-card d-flex gap-3">
      <span class="kpi-accent" style="background:#f97316;"></span>
      <div>
        <div class="kpi-label">Total Registrations</div>
        <div class="kpi-value"><?= number_format($total) ?></div>
        <div class="kpi-sub"><i class="bi bi-graph-up-arrow me-1"></i><?= $week ?> in the last 7 days</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card d-flex gap-3">
      <span class="kpi-accent" style="background:#199e70;"></span>
      <div>
        <div class="kpi-label">Checked In</div>
        <div class="kpi-value"><?= number_format($checkedIn) ?></div>
        <div class="kpi-sub"><?= $checkinPct ?>% of all runners</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card d-flex gap-3">
      <span class="kpi-accent" style="background:#3987e5;"></span>
      <div>
        <div class="kpi-label">Registered Today</div>
        <div class="kpi-value"><?= number_format($today) ?></div>
        <div class="kpi-sub"><?= e(date('l, d M')) ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi-card d-flex gap-3">
      <span class="kpi-accent" style="background:#c98500;"></span>
      <div>
        <div class="kpi-label">Revenue Collected</div>
        <div class="kpi-value">₹<?= number_format($revenue / 100) ?></div>
        <div class="kpi-sub"><a href="payments.php" class="text-decoration-none" style="color:inherit;"><?= $paidCount ?> successful payment<?= $paidCount === 1 ? '' : 's' ?> →</a></div>
      </div>
    </div>
  </div>
</div>

<?php if ($total === 0): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No registrations yet — analytics will appear once participants start registering.</div>
<?php endif; ?>

<!-- ===== Pies with data + snapshot ===== -->
<div class="row g-3 mb-3">
  <div class="col-md-6 col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-gender-ambiguous"></i>Gender Distribution</div>
      <div class="mx-auto" style="max-width:180px;"><canvas id="chGender"></canvas></div>
      <ul class="dlegend mt-3">
        <?php foreach ($genderMap as $label => $count): ?>
        <li>
          <span class="swatch" style="background:<?= e($GENDER_COLORS[$label] ?? '#8fa3b8') ?>;"></span>
          <span class="lbl"><?= e($label) ?></span>
          <span class="cnt"><?= $count ?></span>
          <span class="pc"><?= $pct($count) ?>%</span>
        </li>
        <?php endforeach; ?>
        <?php if (!$genderMap): ?><li class="justify-content-center text-secondary">No data yet.</li><?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="col-md-6 col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-flag"></i>Race Categories</div>
      <div class="mx-auto" style="max-width:180px;"><canvas id="chCategory"></canvas></div>
      <ul class="dlegend mt-3">
        <?php foreach ($CAT_COLORS as $label => $color): $count = $catMap[$label] ?? 0; ?>
        <li>
          <span class="swatch" style="background:<?= e($color) ?>;"></span>
          <span class="lbl"><?= e($label) ?></span>
          <span class="cnt"><?= $count ?></span>
          <span class="pc"><?= $pct($count) ?>%</span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-clipboard-data"></i>Event Snapshot</div>
      <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-secondary">Check-in progress</span>
          <span class="text-white fw-semibold"><?= $checkedIn ?> / <?= $total ?></span>
        </div>
        <div class="progress progress-thin">
          <div class="progress-bar" style="width:<?= $checkinPct ?>%;background:#199e70;" role="progressbar"
               aria-valuenow="<?= $checkinPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
      <div class="fact"><span class="k">Most popular category</span><span class="v"><?= e($topCategory) ?></span></div>
      <div class="fact"><span class="k">Average age</span><span class="v"><?= $avgAge !== null && $avgAge !== false ? e((string)$avgAge) . ' yrs' : '—' ?></span></div>
      <div class="fact"><span class="k">Top city</span><span class="v"><?= e($topCity) ?></span></div>
      <div class="fact"><span class="k">Registered this week</span><span class="v"><?= $week ?></span></div>
      <div class="fact"><span class="k">Awaiting check-in</span><span class="v"><?= $total - $checkedIn ?></span></div>
      <div class="fact"><span class="k">Event date</span><span class="v"><?= e(EVENT_DATE) ?></span></div>
      <div class="fact"><span class="k">Venue</span><span class="v"><?= e(EVENT_VENUE) ?></span></div>
    </div>
  </div>
</div>

<!-- ===== Trend + demographics ===== -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-graph-up"></i>Registrations — Last 14 Days</div>
      <div style="position:relative;height:260px;width:100%;"><canvas id="chTrend"></canvas></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-cake2"></i>Age Groups</div>
      <?= bar_list($ageMap, '#3987e5') ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6 col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-tags"></i>T-Shirt Demand</div>
      <?= bar_list($tshirtMap, '#9085e9') ?>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-droplet"></i>Blood Groups</div>
      <?= bar_list($bloodMap, '#e66767') ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="panel">
      <div class="panel-title"><i class="bi bi-buildings"></i>Top Cities</div>
      <?= bar_list($cityMap, '#199e70') ?>
    </div>
  </div>
</div>

<!-- ===== Recent registrations ===== -->
<div class="panel">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="panel-title mb-0"><i class="bi bi-clock-history"></i>Recent Registrations</div>
    <a href="participants.php" class="btn btn-sm btn-outline-secondary">View all <i class="bi bi-arrow-right ms-1"></i></a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-recent align-middle mb-0">
      <thead>
        <tr><th>Reg ID</th><th>Name</th><th>Category</th><th>Gender</th><th>Status</th><th>Registered</th></tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">No registrations yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><span class="text-accent fw-semibold"><?= e($r['reg_id']) ?></span></td>
          <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?><br><small class="text-secondary"><?= e($r['email']) ?></small></td>
          <td><small><?= e($r['category']) ?></small></td>
          <td><small><?= e($r['gender']) ?></small></td>
          <td>
            <?php if ($r['status'] === 'checked_in'): ?>
              <span class="badge text-bg-success">Checked In</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">Registered</span>
            <?php endif; ?>
          </td>
          <td><small class="text-secondary"><?= e(date('d M, h:i A', strtotime($r['created_at']))) ?></small></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const GENDER = <?= json_encode($genderChart) ?>;
const CATEGORY = <?= json_encode($catChart) ?>;
const TREND = <?= json_encode($trendChart) ?>;
const SURFACE = '#141c25';

Chart.defaults.color = '#8fa3b8';
Chart.defaults.borderColor = 'rgba(34,48,65,.55)';
Chart.defaults.font.family = 'Inter, sans-serif';

// Doughnuts: data legend lives in the HTML beside the chart, so no canvas legend.
const doughnut = (id, d) => new Chart(document.getElementById(id), {
  type: 'doughnut',
  data: {
    labels: d.labels,
    datasets: [{ data: d.data, backgroundColor: d.colors, borderColor: SURFACE, borderWidth: 2 }]
  },
  options: {
    cutout: '64%',
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => {
            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const p = total ? (ctx.parsed / total * 100).toFixed(1) : 0;
            return ` ${ctx.label}: ${ctx.parsed} (${p}%)`;
          }
        }
      }
    }
  }
});

doughnut('chGender', GENDER);
doughnut('chCategory', CATEGORY);

new Chart(document.getElementById('chTrend'), {
  type: 'line',
  data: {
    labels: TREND.labels,
    datasets: [{
      label: 'Registrations',
      data: TREND.data,
      borderColor: '#3987e5',
      borderWidth: 2,
      backgroundColor: 'rgba(57,135,229,.10)',
      fill: true,
      tension: .3,
      pointRadius: 4,
      pointHoverRadius: 6,
      pointBackgroundColor: '#3987e5'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(34,48,65,.45)' } },
      x: { grid: { display: false } }
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
