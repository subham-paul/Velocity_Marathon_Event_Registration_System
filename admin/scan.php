<?php
$pageTitle = 'Scan QR';
$activeNav = 'scan';
require_once __DIR__ . '/includes/layout_top.php';
$csrf = csrf_token();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="mb-0">QR Verification</h4>
    <small class="text-secondary">Scan a participant's QR entry pass with your camera, or enter the Reg ID manually.</small>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="chart-card">
      <h6><i class="bi bi-camera-video me-1"></i>Camera Scanner</h6>
      <div id="reader" class="rounded overflow-hidden mb-3" style="background:#0b0f14;"></div>
      <div class="d-grid gap-2">
        <button class="btn btn-accent" id="startScan"><i class="bi bi-qr-code-scan me-2"></i>Start Camera</button>
        <button class="btn btn-outline-secondary d-none" id="stopScan"><i class="bi bi-stop-circle me-2"></i>Stop Camera</button>
      </div>
      <hr class="border-secondary-subtle">
      <h6><i class="bi bi-keyboard me-1"></i>Manual Lookup</h6>
      <form id="manualForm" class="d-flex gap-2">
        <input type="text" class="form-control" id="manualRegId" placeholder="e.g. <?= e(EVENT_YEAR_TAG) ?>-AB2CD3" maxlength="20" required>
        <button class="btn btn-outline-accent" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="chart-card" id="resultCard">
      <h6><i class="bi bi-person-check me-1"></i>Verification Result</h6>
      <div id="resultEmpty" class="text-center text-secondary py-5">
        <i class="bi bi-qr-code display-3 d-block mb-3 opacity-50"></i>
        Scan a QR code or look up a Reg ID to see the participant here.
      </div>
      <div id="resultBox" class="d-none">
        <div class="alert py-2 small" id="resultAlert"></div>
        <div class="row g-4 align-items-start">
          <div class="col-sm-4 text-center">
            <img id="rQr" src="" alt="QR" class="img-fluid rounded bg-white border border-3 border-light" style="max-width:170px;">
            <div class="reg-id-box mt-3"><span id="rRegId" class="reg-id fs-4"></span></div>
          </div>
          <div class="col-sm-8">
            <table class="table table-sm mb-3"><tbody id="rBody"></tbody></table>
            <button class="btn btn-success w-100 d-none" id="checkinBtn"><i class="bi bi-check2-circle me-2"></i>Check In This Runner</button>
            <div class="alert alert-success py-2 small d-none mb-0 mt-2" id="checkinDone"><i class="bi bi-check-circle me-1"></i>Checked in successfully.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const CSRF = <?= json_encode($csrf) ?>;
let scanner = null;
let currentId = null;
let busy = false;

const $ = id => document.getElementById(id);

async function verify(payload) {
  if (busy) return;
  busy = true;
  try {
    const body = new FormData();
    body.append('csrf_token', CSRF);
    body.append('qr_text', payload);
    const res = await fetch('api/verify_scan.php', { method: 'POST', body });
    const data = await res.json();
    render(data);
  } catch {
    render({ success: false, message: 'Network error — try again.' });
  } finally {
    setTimeout(() => { busy = false; }, 1200); // debounce repeated camera reads
  }
}

function render(data) {
  $('resultEmpty').classList.add('d-none');
  $('resultBox').classList.remove('d-none');
  const alertEl = $('resultAlert');
  $('checkinDone').classList.add('d-none');

  if (!data.success) {
    alertEl.className = 'alert alert-danger py-2 small';
    alertEl.innerHTML = '<i class="bi bi-x-octagon me-2"></i>' + data.message;
    $('rQr').src = '';
    $('rRegId').textContent = '—';
    $('rBody').innerHTML = '';
    $('checkinBtn').classList.add('d-none');
    currentId = null;
    return;
  }

  const p = data.participant;
  currentId = p.id;

  if (p.status === 'checked_in') {
    alertEl.className = 'alert alert-warning py-2 small';
    alertEl.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i><strong>Already checked in</strong> at ' + p.checked_in_at + '. Possible duplicate entry!';
    $('checkinBtn').classList.add('d-none');
  } else {
    alertEl.className = 'alert alert-success py-2 small';
    alertEl.innerHTML = '<i class="bi bi-patch-check me-2"></i><strong>Valid registration</strong> — signature verified. Ready to check in.';
    $('checkinBtn').classList.remove('d-none');
  }

  $('rQr').src = '../' + p.qr_path;
  $('rRegId').textContent = p.reg_id;
  const esc = s => { const el = document.createElement('span'); el.textContent = s ?? '—'; return el.innerHTML; };
  const rows = [
    ['Name', p.name], ['Category', p.category], ['Gender', p.gender],
    ['Age', p.age + ' yrs'], ['T-Shirt', p.tshirt_size], ['Blood Group', p.blood_group],
    ['Phone', p.phone], ['Email', p.email], ['City', p.city],
  ];
  $('rBody').innerHTML = rows.map(([k, v]) =>
    `<tr><td class="text-secondary" style="width:38%">${k}</td><td>${esc(v)}</td></tr>`).join('');
}

$('checkinBtn').addEventListener('click', async () => {
  if (!currentId) return;
  const body = new FormData();
  body.append('csrf_token', CSRF);
  body.append('checkin_id', currentId);
  const res = await fetch('api/verify_scan.php', { method: 'POST', body });
  const data = await res.json();
  if (data.success) {
    $('checkinBtn').classList.add('d-none');
    $('checkinDone').classList.remove('d-none');
    $('resultAlert').className = 'alert alert-success py-2 small';
    $('resultAlert').innerHTML = '<i class="bi bi-check-circle me-2"></i>Runner checked in.';
  } else {
    alert(data.message || 'Check-in failed.');
  }
});

/* ---------- camera ---------- */
$('startScan').addEventListener('click', async () => {
  scanner = new Html5Qrcode('reader');
  try {
    await scanner.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 230, height: 230 } },
      text => verify(text)
    );
    $('startScan').classList.add('d-none');
    $('stopScan').classList.remove('d-none');
  } catch (err) {
    alert('Could not start the camera: ' + err + '\n\nTip: camera access needs HTTPS or localhost.');
  }
});

$('stopScan').addEventListener('click', async () => {
  if (scanner) { await scanner.stop(); scanner.clear(); }
  $('stopScan').classList.add('d-none');
  $('startScan').classList.remove('d-none');
});

/* ---------- manual ---------- */
$('manualForm').addEventListener('submit', e => {
  e.preventDefault();
  verify($('manualRegId').value.trim());
});
</script>

<?php require_once __DIR__ . '/includes/layout_bottom.php'; ?>
