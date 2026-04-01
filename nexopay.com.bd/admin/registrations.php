<?php
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// ?? Fix admin_id from username if missing ????????????????????????????????????
if (!isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
    $s = $db->prepare("SELECT id FROM admin_users WHERE username = :u");
    $s->bindParam(':u', $_SESSION['admin_username']);
    $s->execute();
    $a = $s->fetch(PDO::FETCH_ASSOC);
    if ($a) $_SESSION['admin_id'] = $a['id'];
}

// ?? Handle AJAX live-poll request ????????????????????????????????????????????
if (isset($_GET['poll'])) {
    $last_id = (int)($_GET['last_id'] ?? 0);
    $stmt = $db->prepare("
        SELECT r.id, r.full_name, r.email, r.phone, r.event_id,
               r.payment_method, r.registration_type, r.transaction_id,
               r.ticket_quantity, r.total_amount, r.payment_status,
               r.approval_status, r.registration_date,
               e.title AS event_title
        FROM registrations r
        LEFT JOIN events e ON r.event_id = e.id
        WHERE r.id > :last_id
        ORDER BY r.id DESC
    ");
    $stmt->bindParam(':last_id', $last_id, PDO::PARAM_INT);
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ?? Handle Approve / Reject action ??????????????????????????????????????????
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['registration_id'])) {
    $rid    = (int)$_POST['registration_id'];
    $action = $_POST['action'];
    try {
        if ($action === 'approve') {
            $q = "UPDATE registrations SET approval_status='approved', payment_status='paid', approved_date=NOW() WHERE id=:id";
            $st = $db->prepare($q);
            $st->bindParam(':id', $rid, PDO::PARAM_INT);
            $st->execute();
            $flash = ['type' => 'success', 'msg' => '<i class="fas fa-check-circle"></i> Registration #' . $rid . ' approved!'];
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
            $q = "UPDATE registrations SET approval_status='rejected', rejection_reason=:r, approved_date=NOW() WHERE id=:id";
            $st = $db->prepare($q);
            $st->bindParam(':r', $reason);
            $st->bindParam(':id', $rid, PDO::PARAM_INT);
            $st->execute();
            $flash = ['type' => 'warning', 'msg' => '❌ Registration #' . $rid . ' rejected.'];
        }
    } catch (Exception $e) {
        $flash = ['type' => 'error', 'msg' => 'Error: ' . $e->getMessage()];
    }
    // Redirect to prevent re-submission
    header('Location: registrations.php?flash=' . urlencode($flash['msg']) . '&ftype=' . $flash['type']);
    exit;
}

// ?? Handle Delete ????????????????????????????????????????????????????????????
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM registrations WHERE id=:id")->execute([':id' => $id]);
    header('Location: registrations.php?flash=' . urlencode('🗑️ Registration deleted.') . '&ftype=warning');
    exit;
}

// ?? Flash from redirect ??????????????????????????????????????????????????????
if (!$flash && isset($_GET['flash'])) {
    $flash = ['type' => $_GET['ftype'] ?? 'success', 'msg' => $_GET['flash']];
}

// ?? Filters ??????????????????????????????????????????????????????????????????
$filter_event  = $_GET['event_id']  ?? '';
$filter_status = $_GET['status']    ?? '';
$filter_method = $_GET['method']    ?? '';
$filter_type   = $_GET['type']      ?? '';
$search        = $_GET['search']    ?? '';

$where = ['1=1'];
$params = [];

if ($filter_event)  { $where[] = 'r.event_id = :evid';                          $params[':evid']  = $filter_event; }
if ($filter_status) { $where[] = 'r.approval_status = :status';                 $params[':status'] = $filter_status; }
if ($filter_method) { $where[] = 'r.payment_method = :method';                  $params[':method'] = $filter_method; }
if ($filter_type)   { $where[] = 'r.registration_type = :rtype';                $params[':rtype']  = $filter_type; }
if ($search)        { $where[] = '(r.full_name LIKE :s OR r.email LIKE :s OR r.phone LIKE :s OR r.transaction_id LIKE :s)';
                      $params[':s'] = '%' . $search . '%'; }

$whereStr = implode(' AND ', $where);

$query = "
    SELECT r.*, e.title AS event_title
    FROM registrations r
    LEFT JOIN events e ON r.event_id = e.id
    WHERE $whereStr
    ORDER BY r.registration_date DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch events for filter dropdown
$events = $db->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$stats = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(approval_status='pending')  AS pending,
        SUM(approval_status='approved') AS approved,
        SUM(approval_status='rejected') AS rejected,
        SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END) AS revenue
    FROM registrations
")->fetch(PDO::FETCH_ASSOC);

$max_id = $db->query("SELECT COALESCE(MAX(id),0) AS m FROM registrations")->fetch()['m'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrations – Nexopay Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
/* ??─ LAYOUT ?????????????????????????????????????????????????????? */
.reg-wrap { padding: 28px 24px; }

/* ??─ KPI STRIP ??????????????????????????????????????????????????─ */
.kpi-strip { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:28px; }
.kpi { flex:1; min-width:160px; background:#fff; border-radius:14px;
       padding:20px 22px; box-shadow:0 2px 12px rgba(0,0,0,.06);
       display:flex; align-items:center; gap:15px; transition:transform .2s; }
.kpi:hover { transform:translateY(-3px); }
.kpi-icon { width:46px; height:46px; border-radius:12px; display:flex;
            align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.kpi-icon.blue   { background:#dbeafe; color:#2563eb; }
.kpi-icon.yellow { background:#fef9c3; color:#ca8a04; }
.kpi-icon.green  { background:#dcfce7; color:#16a34a; }
.kpi-icon.red    { background:#fee2e2; color:#dc2626; }
.kpi-icon.purple { background:#ede9fe; color:#7c3aed; }
.kpi-num  { font-size:1.7rem; font-weight:800; color:#111; line-height:1; }
.kpi-label{ font-size:.8rem; color:#6b7280; margin-top:3px; font-weight:500; }

/* ??─ FILTER BAR ?????????????????????????????????????????????????? */
.filter-bar { background:#fff; border-radius:14px; padding:18px 20px;
              box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:22px;
              display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.filter-bar label { font-size:.8rem; font-weight:600; color:#374151; display:block; margin-bottom:4px; }
.filter-bar select,
.filter-bar input[type=text] {
    padding:9px 14px; border:1.5px solid #e5e7eb; border-radius:10px;
    font-size:.9rem; background:#f9fafb; min-width:145px; font-family:inherit; }
.filter-bar select:focus,
.filter-bar input[type=text]:focus { outline:none; border-color:#6366f1; background:#fff; }
.btn-filter { padding:9px 22px; background:#6366f1; color:#fff; border:none;
              border-radius:10px; font-weight:600; font-size:.9rem; cursor:pointer; transition:all .2s; }
.btn-filter:hover { background:#4f46e5; }
.btn-reset  { padding:9px 18px; background:#f3f4f6; color:#374151; border:none;
              border-radius:10px; font-weight:600; font-size:.9rem; cursor:pointer; }

/* ??─ SECTION HEADER ?????????????????????????????????????????????? */
.section-header { display:flex; align-items:center; justify-content:space-between;
                  margin-bottom:16px; flex-wrap:wrap; gap:10px; }
.section-title  { font-size:1.2rem; font-weight:700; color:#111; }
.live-dot { width:10px; height:10px; border-radius:50%; background:#10b981;
            display:inline-block; animation:pulse-dot 1.5s infinite; margin-right:6px; }
@keyframes pulse-dot { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.6} }
.badge-count { background:#ef4444; color:#fff; border-radius:50rem; padding:2px 10px;
               font-size:.78rem; font-weight:700; }

/* ??─ TABLE ??????????????????????????????????????????????????????─ */
.table-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.06);
              overflow:hidden; }
.table-scroll { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:.9rem; }
thead th { background:#f8fafc; padding:13px 14px; font-size:.78rem; font-weight:700;
           text-transform:uppercase; letter-spacing:.5px; color:#64748b;
           border-bottom:2px solid #e2e8f0; white-space:nowrap; }
tbody tr { border-bottom:1px solid #f1f5f9; transition:background .15s; }
tbody tr:hover { background:#f8faff; }
tbody tr.new-row { animation:highlight-in 2s ease forwards; }
@keyframes highlight-in { 0%{background:#d1fae5} 100%{background:transparent} }
tbody td { padding:12px 14px; vertical-align:middle; }
.td-name { font-weight:600; color:#1e293b; }
.td-sub  { font-size:.8rem; color:#94a3b8; margin-top:2px; }

/* ??─ BADGES ?????????????????????????????????????????????????????? */
.badge { display:inline-block; padding:3px 11px; border-radius:50rem;
         font-size:.76rem; font-weight:700; white-space:nowrap; }
.badge-pending  { background:#fef9c3; color:#92400e; }
.badge-approved { background:#dcfce7; color:#166534; }
.badge-rejected { background:#fee2e2; color:#991b1b; }
.badge-paid     { background:#d1fae5; color:#065f46; }
.badge-unpaid, .badge-pending-pay { background:#fce7f3; color:#9d174d; }
.badge-bkash    { background:#f5d0fa; color:#7e22ce; }
.badge-bank     { background:#dbeafe; color:#1d4ed8; }
.badge-general  { background:#e0f2fe; color:#0369a1; }
.badge-ca       { background:#ede9fe; color:#6d28d9; }
.badge-volunteer{ background:#ecfdf5; color:#047857; }

/* ??─ ACTIONS ????????????????????????????????????????????????????─ */
.action-group { display:flex; gap:6px; flex-wrap:wrap; }
.btn-sm { padding:5px 12px; border:none; border-radius:8px; font-size:.8rem;
          font-weight:600; cursor:pointer; transition:all .15s; }
.btn-approve { background:#d1fae5; color:#065f46; }
.btn-approve:hover { background:#a7f3d0; }
.btn-reject  { background:#fee2e2; color:#991b1b; }
.btn-reject:hover  { background:#fca5a5; }
.btn-view    { background:#e0f2fe; color:#0369a1; }
.btn-view:hover    { background:#bae6fd; }
.btn-del     { background:#f3f4f6; color:#6b7280; }
.btn-del:hover     { background:#e5e7eb; color:#ef4444; }

/* ??─ COPY CHIP ??????????????????????????????????????????????????─ */
.copy-chip { cursor:pointer; font-family:monospace; font-size:.82rem;
             background:#f1f5f9; padding:3px 9px; border-radius:6px;
             border:1px solid #e2e8f0; transition:background .15s; user-select:none; }
.copy-chip:hover { background:#e2e8f0; }
.copy-chip:active { background:#cbd5e1; }

/* ??─ FLASH ??????????????????????????????????????????????????????─ */
.flash { padding:14px 18px; border-radius:10px; margin-bottom:20px;
         font-weight:600; display:flex; align-items:center; gap:10px; }
.flash-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.flash-warning { background:#fef9c3; color:#92400e; border:1px solid #fde68a; }
.flash-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

/* ??─ MODAL ??????????????????????????????????????????????????????─ */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55);
                 z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:16px; padding:30px;
             max-width:580px; width:90%; max-height:90vh; overflow-y:auto;
             box-shadow:0 25px 60px rgba(0,0,0,.25); animation:slide-up .25s ease; }
@keyframes slide-up { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
.modal-title { font-size:1.2rem; font-weight:700; margin-bottom:18px; color:#111; }
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:22px; }
.detail-item label { font-size:.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase; margin-bottom:3px; display:block; }
.detail-item span  { font-size:.95rem; color:#1e293b; font-weight:500; }
.modal-footer { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
.btn-close-modal { padding:9px 22px; background:#f3f4f6; border:none; border-radius:10px;
                   font-weight:600; cursor:pointer; }
.reject-form textarea { width:100%; padding:12px; border:1.5px solid #e5e7eb;
                        border-radius:10px; font-family:inherit; font-size:.95rem; resize:vertical; }
.reject-form textarea:focus { outline:none; border-color:#ef4444; }

/* ??─ EMPTY STATE ????????????????????????????????????????????????─ */
.empty-state { text-align:center; padding:60px 20px; }
.empty-state i { font-size:3rem; color:#cbd5e1; margin-bottom:14px; display:block; }
.empty-state p { color:#94a3b8; }

@media(max-width:768px){
    .kpi-strip { gap:10px; }
    .kpi { min-width:calc(50% - 5px); flex:none; }
    .detail-grid { grid-template-columns:1fr; }
    .filter-bar { flex-direction:column; }
    .filter-bar select, .filter-bar input { width:100%; min-width:0; }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content reg-wrap">

    <!-- ?? Page Title ???????????????????????????????????????????????? -->
    <div class="section-header">
        <div>
            <h1 style="font-size:1.6rem;font-weight:800;color:#111;margin:0">
                <i class="fas fa-clipboard-list" style="color:#6366f1;margin-right:8px"></i>
                Event Registrations
            </h1>
            <p style="color:#6b7280;font-size:.9rem;margin:4px 0 0">
                <span class="live-dot"></span>
                Live – auto-refreshes every 30 seconds
            </p>
        </div>
        <a href="../admin/download_registrations_pdf.php" target="_blank"
           style="padding:10px 20px;background:#6366f1;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:.9rem;">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>

    <!-- ?? Flash Message ????????????????????????????????????????????─ -->
    <?php if ($flash): ?>
    <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>" id="flashMsg">
        <?= htmlspecialchars($flash['msg']) ?>
        <button onclick="document.getElementById('flashMsg').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1.1rem;">&times;</button>
    </div>
    <?php endif; ?>

    <!-- ?? KPI Strip ????????????????????????????????????????????????─ -->
    <div class="kpi-strip">
        <div class="kpi">
            <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['total']) ?></div><div class="kpi-label">Total Registrations</div></div>
        </div>
        <div class="kpi">
            <div class="kpi-icon yellow"><i class="fas fa-clock"></i></div>
            <div>
                <div class="kpi-num"><?= number_format($stats['pending']) ?></div>
                <div class="kpi-label">Awaiting Review <?php if ($stats['pending'] > 0): ?><span class="badge-count"><?= $stats['pending'] ?></span><?php endif; ?></div>
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['approved']) ?></div><div class="kpi-label">Approved</div></div>
        </div>
        <div class="kpi">
            <div class="kpi-icon red"><i class="fas fa-times-circle"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['rejected']) ?></div><div class="kpi-label">Rejected</div></div>
        </div>
        <div class="kpi">
            <div class="kpi-icon purple"><i class="fas fa-money-bill-wave"></i></div>
            <div><div class="kpi-num">&#2547;<?= number_format($stats['revenue'], 0) ?></div><div class="kpi-label">Revenue Collected</div></div>
        </div>
    </div>

    <!-- ?? Filter Bar ???????????????????????????????????????????????? -->
    <form method="GET" class="filter-bar">
        <div>
            <label>🔍 Search</label>
            <input type="text" name="search" placeholder="Name, email, phone, Txn ID…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div>
            <label><i class="fas fa-calendar"></i> Event</label>
            <select name="event_id">
                <option value="">All Events</option>
                <?php foreach ($events as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $filter_event == $ev['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><i class="fas fa-clipboard-list"></i> Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="pending"  <?= $filter_status=='pending'  ? 'selected':'' ?>>⏳ Pending</option>
                <option value="approved" <?= $filter_status=='approved' ? 'selected':'' ?>><i class="fas fa-check-circle"></i> Approved</option>
                <option value="rejected" <?= $filter_status=='rejected' ? 'selected':'' ?>>❌ Rejected</option>
            </select>
        </div>
        <div>
            <label><i class="fas fa-credit-card"></i> Payment Method</label>
            <select name="method">
                <option value="">All Methods</option>
                <option value="bkash"         <?= $filter_method=='bkash'         ? 'selected':'' ?>>bKash</option>
                <option value="bank_transfer" <?= $filter_method=='bank_transfer' ? 'selected':'' ?>>Bank Transfer</option>
                <option value="nagad"         <?= $filter_method=='nagad'         ? 'selected':'' ?>>Nagad</option>
                <option value="cash"          <?= $filter_method=='cash'          ? 'selected':'' ?>>Cash</option>
            </select>
        </div>
        <div>
            <label>🏷️ Type</label>
            <select name="type">
                <option value="">All Types</option>
                <option value="general"           <?= $filter_type=='general'           ? 'selected':'' ?>>General</option>
                <option value="campus_ambassador" <?= $filter_type=='campus_ambassador' ? 'selected':'' ?>>Campus Ambassador</option>
                <option value="volunteer"         <?= $filter_type=='volunteer'         ? 'selected':'' ?>>Volunteer</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-self:flex-end">
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="registrations.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- ?? Table ????????????????????????????????????????????????????─ -->
    <div class="section-header">
        <span class="section-title">
            Showing <?= count($registrations) ?> registration<?= count($registrations) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <div class="table-card" id="tableCard">
        <div class="table-scroll">
        <?php if (empty($registrations)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No registrations match your filters.</p>
            </div>
        <?php else: ?>
        <table id="regTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Registrant</th>
                    <th>Event</th>
                    <th>Type</th>
                    <th>Payment</th>
                    <th>Transaction ID</th>
                    <th>Tickets</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="regBody">
            <?php foreach ($registrations as $r): ?>
                <tr id="row-<?= $r['id'] ?>">
                    <td><strong>#<?= $r['id'] ?></strong></td>
                    <td>
                        <div class="td-name"><?= htmlspecialchars($r['full_name']) ?></div>
                        <div class="td-sub"><?= htmlspecialchars($r['email']) ?></div>
                        <div class="td-sub"><?= htmlspecialchars($r['phone'] ?? '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($r['event_title'] ?? '&mdash;') ?></td>
                    <td>
                        <?php
                        $type = $r['registration_type'] ?? 'general';
                        $type_labels = ['general'=>'General','campus_ambassador'=>'Campus Ambassador','volunteer'=>'Volunteer'];
                        $type_cls    = ['general'=>'badge-general','campus_ambassador'=>'badge-ca','volunteer'=>'badge-volunteer'];
                        ?>
                        <span class="badge <?= $type_cls[$type] ?? 'badge-general' ?>">
                            <?= $type_labels[$type] ?? ucfirst($type) ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $method = $r['payment_method'] ?? 'bkash';
                        $method_cls = ['bkash'=>'badge-bkash','bank_transfer'=>'badge-bank','nagad'=>'badge-bkash','cash'=>'badge-general'];
                        $method_label = ['bkash'=>'bKash','bank_transfer'=>'Bank Transfer','nagad'=>'Nagad','cash'=>'Cash'];
                        ?>
                        <span class="badge <?= $method_cls[$method] ?? 'badge-bkash' ?>">
                            <?= $method_label[$method] ?? ucfirst($method) ?>
                        </span>
                        <br>
                        <span class="badge <?= $r['payment_status'] === 'paid' ? 'badge-paid' : 'badge-unpaid' ?>" style="margin-top:4px">
                            <?= ucfirst($r['payment_status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($r['transaction_id'])): ?>
                        <span class="copy-chip" onclick="copyText('<?= htmlspecialchars($r['transaction_id']) ?>', this)" title="Click to copy">
                            <?= htmlspecialchars($r['transaction_id']) ?>
                        </span>
                        <?php else: ?>&mdash;<?php endif; ?>
                    </td>
                    <td style="text-align:center"><?= $r['ticket_quantity'] ?? '&mdash;' ?></td>
                    <td><strong>&#2547;<?= number_format($r['total_amount'] ?? 0, 2) ?></strong></td>
                    <td>
                        <?php $st = $r['approval_status'] ?? 'pending'; ?>
                        <span class="badge badge-<?= $st ?>">
                            <?= ['pending'=>'⏳ Pending','approved'=>'<i class="fas fa-check-circle"></i> Approved','rejected'=>'❌ Rejected'][$st] ?? ucfirst($st) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap">
                        <div class="td-sub"><?= date('d M Y', strtotime($r['registration_date'])) ?></div>
                        <div class="td-sub"><?= date('h:i A', strtotime($r['registration_date'])) ?></div>
                    </td>
                    <td>
                        <div class="action-group">
                            <button class="btn-sm btn-view" onclick="viewDetails(<?= htmlspecialchars(json_encode($r)) ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($st === 'pending'): ?>
                            <button class="btn-sm btn-approve" onclick="openApprove(<?= $r['id'] ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn-sm btn-reject" onclick="openReject(<?= $r['id'] ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <?php endif; ?>
                            <a href="?delete=<?= $r['id'] ?>" class="btn-sm btn-del"
                               onclick="return confirm('Permanently delete registration #<?= $r['id'] ?>?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>
</main>

<!-- ?? View Details Modal ???????????????????????????????????????????????? -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-id-card" style="margin-right:8px;color:#6366f1"></i>Registration Details</div>
        <div class="detail-grid" id="detailGrid"></div>
        <div class="modal-footer">
            <button class="btn-close-modal" onclick="closeModals()">Close</button>
        </div>
    </div>
</div>

<!-- ?? Approve Modal ????????????????????????????????????????????????????─ -->
<div class="modal-overlay" id="approveModal">
    <div class="modal-box" style="max-width:400px">
        <div class="modal-title"><i class="fas fa-check-circle"></i> Confirm Approval</div>
        <p style="color:#374151;margin-bottom:22px">Are you sure you want to <strong>approve</strong> this registration? The applicant will be notified.</p>
        <form method="POST" id="approveForm">
            <input type="hidden" name="registration_id" id="approveId">
            <input type="hidden" name="action" value="approve">
            <div class="modal-footer">
                <button class="btn-close-modal" type="button" onclick="closeModals()">Cancel</button>
                <button type="submit" style="padding:9px 24px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer">
                    <i class="fas fa-check-circle"></i> Yes, Approve
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ?? Reject Modal ?????????????????????????????????????????????????????? -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-title">❌ Reject Registration</div>
        <form method="POST" id="rejectForm" class="reject-form">
            <input type="hidden" name="registration_id" id="rejectId">
            <input type="hidden" name="action" value="reject">
            <div style="margin-bottom:18px">
                <label style="font-weight:600;color:#374151;display:block;margin-bottom:8px">Reason for Rejection *</label>
                <textarea name="rejection_reason" rows="4" required placeholder="Please explain why this registration is being rejected…"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn-close-modal" type="button" onclick="closeModals()">Cancel</button>
                <button type="submit" style="padding:9px 24px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer">
                    ❌ Reject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let lastId = <?= (int)$max_id ?>;

// ?? Copy Transaction ID ????????????????????????????????????????????????????
function copyText(text, el) {
    navigator.clipboard.writeText(text).then(() => {
        const old = el.textContent;
        el.textContent = '✓ Copied!';
        el.style.background = '#d1fae5';
        setTimeout(() => { el.textContent = old; el.style.background = ''; }, 1500);
    });
}

// ?? View Details Modal ????????????????????????????????????????????????????─
function viewDetails(r) {
    const fields = [
        ['Registration #', '#' + r.id],
        ['Full Name', r.full_name],
        ['Email', r.email],
        ['Phone', r.phone || '&mdash;'],
        ['Event', r.event_title || '&mdash;'],
        ['Registration Type', r.registration_type || 'general'],
        ['Tickets', r.ticket_quantity || '&mdash;'],
        ['Total Amount', '&#2547;' + parseFloat(r.total_amount || 0).toFixed(2)],
        ['Payment Method', r.payment_method || '&mdash;'],
        ['Transaction ID', r.transaction_id || '&mdash;'],
        ['Payment Status', r.payment_status || '&mdash;'],
        ['Approval Status', r.approval_status || '&mdash;'],
        ['Date', r.registration_date],
        ['Rejection Reason', r.rejection_reason || '&mdash;'],
    ];
    const grid = document.getElementById('detailGrid');
    grid.innerHTML = fields.map(([label, val]) =>
        `<div class="detail-item"><label>${label}</label><span>${val}</span></div>`
    ).join('');
    document.getElementById('viewModal').classList.add('open');
}

// ?? Approve / Reject Modals ????????????????????????????????????????????????
function openApprove(id) {
    document.getElementById('approveId').value = id;
    document.getElementById('approveModal').classList.add('open');
}
function openReject(id) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectModal').classList.add('open');
}
function closeModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModals(); });
});

// ?? Live Polling ??????????????????????????????????????????????????????????─
function pollNewRegistrations() {
    fetch(`registrations.php?poll=1&last_id=${lastId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.length) return;
            const tbody = document.getElementById('regBody');
            if (!tbody) return;
            data.forEach(r => {
                if (document.getElementById('row-' + r.id)) return;
                lastId = Math.max(lastId, r.id);
                const typeCls   = {general:'badge-general',campus_ambassador:'badge-ca',volunteer:'badge-volunteer'};
                const typeLabel = {general:'General',campus_ambassador:'Campus Ambassador',volunteer:'Volunteer'};
                const methodCls = {bkash:'badge-bkash',bank_transfer:'badge-bank',nagad:'badge-bkash'};
                const methodLabel = {bkash:'bKash',bank_transfer:'Bank Transfer',nagad:'Nagad',cash:'Cash'};
                const st  = r.approval_status;
                const stIcon = {pending:'⏳ Pending',approved:'<i class="fas fa-check-circle"></i> Approved',rejected:'❌ Rejected'};
                const type   = r.registration_type || 'general';
                const method = r.payment_method || 'bkash';
                const row = document.createElement('tr');
                row.id = 'row-' + r.id;
                row.className = 'new-row';
                row.innerHTML = `
                    <td><strong>#${r.id}</strong></td>
                    <td><div class="td-name">${r.full_name}</div><div class="td-sub">${r.email}</div></td>
                    <td>${r.event_title || '&mdash;'}</td>
                    <td><span class="badge ${typeCls[type] || 'badge-general'}">${typeLabel[type] || type}</span></td>
                    <td><span class="badge ${methodCls[method] || 'badge-bkash'}">${methodLabel[method] || method}</span></td>
                    <td><span class="copy-chip" onclick="copyText('${r.transaction_id||''}',this)">${r.transaction_id||'&mdash;'}</span></td>
                    <td style="text-align:center">${r.ticket_quantity||'&mdash;'}</td>
                    <td><strong>&#2547;${parseFloat(r.total_amount||0).toFixed(2)}</strong></td>
                    <td><span class="badge badge-${st}">${stIcon[st]||st}</span></td>
                    <td><div class="td-sub">${r.registration_date}</div></td>
                    <td>
                        <div class="action-group">
                            <button class="btn-sm btn-view" onclick='viewDetails(${JSON.stringify(r)})'><i class="fas fa-eye"></i></button>
                            ${st === 'pending' ? `
                            <button class="btn-sm btn-approve" onclick="openApprove(${r.id})"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn-sm btn-reject"  onclick="openReject(${r.id})"><i class="fas fa-times"></i> Reject</button>` : ''}
                        </div>
                    </td>`;
                tbody.prepend(row);
                // Flash notification
                if (Notification && Notification.permission === 'granted') {
                    new Notification('NEW New Registration', { body: `${r.full_name} just registered for ${r.event_title||'an event'}!` });
                }
            });
        })
        .catch(() => {});
}

// Request browser notification permission
if (Notification && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Start polling every 30 seconds
setInterval(pollNewRegistrations, 30000);

// Auto-hide flash after 6s
setTimeout(() => {
    const f = document.getElementById('flashMsg');
    if (f) { f.style.opacity = '0'; f.style.transition = '.3s'; setTimeout(() => f.remove(), 300); }
}, 6000);
</script>
</body>
</html>
