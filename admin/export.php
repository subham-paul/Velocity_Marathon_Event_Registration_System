<?php
/** CSV export of registrations (honours the same filters as participants.php). */
require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();
$search  = clean($_GET['q'] ?? '');
$fCat    = clean($_GET['category'] ?? '');
$fGender = clean($_GET['gender'] ?? '');
$fStatus = clean($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(reg_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($fCat, ['5K Fun Run', '10K Challenge', '21K Half Marathon', '42K Full Marathon'], true)) { $where[] = 'category = ?'; $params[] = $fCat; }
if (in_array($fGender, ['Male', 'Female', 'Other'], true)) { $where[] = 'gender = ?'; $params[] = $fGender; }
if (in_array($fStatus, ['confirmed', 'checked_in'], true)) { $where[] = 'status = ?'; $params[] = $fStatus; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT reg_id, first_name, last_name, email, phone, gender, dob, category,
                              tshirt_size, blood_group, emergency_name, emergency_phone,
                              city, state, address, status, checked_in_at, created_at
                       FROM registrations $whereSql ORDER BY created_at DESC");
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="registrations_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, ['Reg ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Gender', 'DOB', 'Category',
               'T-Shirt', 'Blood Group', 'Emergency Name', 'Emergency Phone',
               'City', 'State', 'Address', 'Status', 'Checked In At', 'Registered At']);
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, $row);
}
fclose($out);
