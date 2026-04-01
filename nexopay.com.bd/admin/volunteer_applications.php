<?php
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['app_id'])) {
    $id = (int)$_POST['app_id'];
    try {
        if ($_POST['action'] === 'approve') {
            $db->prepare("UPDATE volunteer_applications SET approval_status='approved', approved_date=NOW() WHERE id=:id")->execute([':id'=>$id]);
            $flash = ['type'=>'success','msg'=>'âœ… Volunteer Application #'.$id.' approved!'];
        } elseif ($_POST['action'] === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
            $db->prepare("UPDATE volunteer_applications SET approval_status='rejected', rejection_reason=:r, approved_date=NOW() WHERE id=:id")->execute([':r'=>$reason,':id'=>$id]);
            $flash = ['type'=>'warning','msg'=>'âŒ Volunteer Application #'.$id.' rejected.'];
        }
    } catch (Exception $e) { $flash = ['type'=>'error','msg'=>'Error: '.$e->getMessage()]; }
    header('Location: volunteer_applications.php?flash='.urlencode($flash['msg']).'&ftype='.$flash['type']); exit;
}
if (!$flash && isset($_GET['flash'])) $flash = ['type'=>$_GET['ftype']??'success','msg'=>$_GET['flash']];

$stats = $db->query("SELECT COUNT(*) t, SUM(approval_status='pending') p, SUM(approval_status='approved') a, SUM(approval_status='rejected') r FROM volunteer_applications")->fetch();
$filter_status = $_GET['status'] ?? '';
$filter_event  = $_GET['event_id'] ?? '';
$search = $_GET['search'] ?? '';
$where = ['1=1']; $params = [];
if ($filter_status) { $where[] = 'v.approval_status=:s'; $params[':s'] = $filter_status; }
if ($filter_event)  { $where[] = 'v.event_id=:e'; $params[':e'] = $filter_event; }
if ($search)        { $where[] = '(v.full_name LIKE :q OR v.email LIKE :q OR v.institution LIKE :q OR v.district LIKE :q)'; $params[':q']='%'.$search.'%'; }
$apps = $db->prepare("SELECT v.*, e.title AS event_title FROM volunteer_applications v LEFT JOIN events e ON v.event_id=e.id WHERE ".implode(' AND ',$where)." ORDER BY v.submission_date DESC");
$apps->execute($params); $apps = $apps->fetchAll(PDO::FETCH_ASSOC);
$events = $db->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Volunteer Applications â€“ Nexopay Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
.wrap{padding:28px 24px}
.kpi-strip{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:22px}
.kpi{flex:1;min-width:150px;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,.06);display:flex;align-items:center;gap:12px}
.kpi-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.kpi-num{font-size:1.6rem;font-weight:800;color:#111;line-height:1}.kpi-label{font-size:.78rem;color:#6b7280;font-weight:600;margin-top:2px}
.filter-bar{background:#fff;border-radius:14px;padding:16px 18px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
.filter-bar label{font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:4px}
.filter-bar select,.filter-bar input{padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:.88rem;background:#f9fafb;min-width:135px}
.btn-f{padding:8px 18px;background:#ea580c;color:#fff;border:none;border-radius:10px;font-weight:600;font-size:.88rem;cursor:pointer}
.table-card{background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden}
.table-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.88rem}
thead th{background:#f8fafc;padding:12px 14px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap}
tbody tr{border-bottom:1px solid #f1f5f9;transition:background .15s}
tbody tr:hover{background:#fff7ed}
tbody td{padding:11px 14px;vertical-align:middle}
.badge{display:inline-block;padding:3px 10px;border-radius:50rem;font-size:.75rem;font-weight:700}
.badge-pending{background:#fef9c3;color:#92400e}.badge-approved{background:#dcfce7;color:#166534}.badge-rejected{background:#fee2e2;color:#991b1b}
.action-group{display:flex;gap:5px;flex-wrap:wrap}
.btn-sm{padding:5px 11px;border:none;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s}
.btn-approve{background:#d1fae5;color:#065f46}.btn-approve:hover{background:#a7f3d0}
.btn-reject{background:#fee2e2;color:#991b1b}.btn-reject:hover{background:#fca5a5}
.btn-view{background:#fff7ed;color:#c2410c}.btn-view:hover{background:#fed7aa}
.flash{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-weight:600;display:flex;align-items:center;gap:8px}
.flash-success{background:#dcfce7;color:#166534}.flash-warning{background:#fef9c3;color:#92400e}.flash-error{background:#fee2e2;color:#991b1b}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:28px;max-width:540px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:slide-up .25s ease}
@keyframes slide-up{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px;margin-bottom:18px}
.detail-item label{font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;margin-bottom:2px;display:block}
.detail-item span{font-size:.9rem;color:#1e293b;font-weight:500}
.photo-preview img{max-width:100%;border-radius:10px;border:2px solid #e5e7eb;margin-bottom:14px}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<main class="main-content wrap">
    <div style="margin-bottom:22px">
        <h1 style="font-size:1.5rem;font-weight:800;color:#111;margin:0"><i class="fas fa-hands-helping" style="color:#ea580c;margin-right:8px"></i>Volunteer Applications</h1>
        <p style="color:#6b7280;font-size:.88rem;margin:4px 0 0">Review and manage all volunteer applications</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>" id="flashMsg">
        <?= htmlspecialchars($flash['msg']) ?>
        <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer">âœ•</button>
    </div>
    <?php endif; ?>

    <div class="kpi-strip">
        <div class="kpi"><div class="kpi-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-hands-helping"></i></div><div><div class="kpi-num"><?= $stats['t'] ?></div><div class="kpi-label">Total</div></div></div>
        <div class="kpi"><div class="kpi-icon" style="background:#fef9c3;color:#ca8a04"><i class="fas fa-clock"></i></div><div><div class="kpi-num"><?= $stats['p'] ?></div><div class="kpi-label">Pending</div></div></div>
        <div class="kpi"><div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check"></i></div><div><div class="kpi-num"><?= $stats['a'] ?></div><div class="kpi-label">Approved</div></div></div>
        <div class="kpi"><div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times"></i></div><div><div class="kpi-num"><?= $stats['r'] ?></div><div class="kpi-label">Rejected</div></div></div>
    </div>

    <form method="GET" class="filter-bar">
        <div><label>ðŸ” Search</label><input type="text" name="search" placeholder="Name, email, districtâ€¦" value="<?= htmlspecialchars($search) ?>"></div>
        <div><label>ðŸ“‹ Status</label><select name="status">
            <option value="">All</option>
            <option value="pending"  <?= $filter_status=='pending'  ?'selected':'' ?>>â³ Pending</option>
            <option value="approved" <?= $filter_status=='approved' ?'selected':'' ?>>âœ… Approved</option>
            <option value="rejected" <?= $filter_status=='rejected' ?'selected':'' ?>>âŒ Rejected</option>
        </select></div>
        <div><label>ðŸ“… Event</label><select name="event_id"><option value="">All Events</option>
            <?php foreach ($events as $ev): ?><option value="<?= $ev['id'] ?>" <?= $filter_event==$ev['id']?'selected':'' ?>><?= htmlspecialchars($ev['title']) ?></option><?php endforeach; ?>
        </select></div>
        <div style="align-self:flex-end;display:flex;gap:8px">
            <button type="submit" class="btn-f"><i class="fas fa-filter"></i> Filter</button>
            <a href="volunteer_applications.php" style="padding:8px 14px;background:#f3f4f6;color:#374151;border-radius:10px;text-decoration:none;font-weight:600;font-size:.88rem">Reset</a>
        </div>
    </form>

    <div class="table-card">
        <div class="table-scroll">
        <?php if (empty($apps)): ?>
            <div style="text-align:center;padding:50px;color:#94a3b8"><i class="fas fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:12px"></i>No applications found.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>Applicant</th><th>Institution</th><th>Class / Group</th><th>Location</th><th>Contact</th><th>Event</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($apps as $v): ?>
            <tr>
                <td><strong>#<?= $v['id'] ?></strong></td>
                <td><div style="font-weight:600;color:#1e293b"><?= htmlspecialchars($v['full_name']) ?></div><div style="font-size:.8rem;color:#94a3b8"><?= htmlspecialchars($v['email']) ?></div></td>
                <td><?= htmlspecialchars($v['institution'] ?? 'â€”') ?></td>
                <td><?= htmlspecialchars(($v['class'] ?? 'â€”') . ' / ' . ($v['group'] ?? 'â€”')) ?></td>
                <td><?= htmlspecialchars(($v['district'] ?? 'â€”') . ', ' . ($v['division'] ?? 'â€”')) ?></td>
                <td><div style="font-size:.85rem"><?= htmlspecialchars($v['phone_number']) ?></div></td>
                <td><?= htmlspecialchars($v['event_title'] ?? 'â€”') ?></td>
                <td><span class="badge badge-<?= $v['approval_status'] ?>"><?= ['pending'=>'â³ Pending','approved'=>'âœ… Approved','rejected'=>'âŒ Rejected'][$v['approval_status']] ?></span></td>
                <td style="font-size:.82rem;white-space:nowrap"><?= date('d M Y', strtotime($v['submission_date'])) ?><br><span style="color:#94a3b8"><?= date('h:i A', strtotime($v['submission_date'])) ?></span></td>
                <td>
                    <div class="action-group">
                        <button class="btn-sm btn-view" onclick='viewApp(<?= htmlspecialchars(json_encode($v)) ?>)'><i class="fas fa-eye"></i></button>
                        <?php if ($v['approval_status'] === 'pending'): ?>
                        <button class="btn-sm btn-approve" onclick="openApprove(<?= $v['id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn-sm btn-reject"  onclick="openReject(<?= $v['id'] ?>)"><i class="fas fa-times"></i> Reject</button>
                        <?php endif; ?>
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

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box">
        <div style="font-size:1.1rem;font-weight:700;margin-bottom:16px"><i class="fas fa-hands-helping" style="color:#ea580c;margin-right:8px"></i>Volunteer Application Details</div>
        <div id="appPhoto" class="photo-preview"></div>
        <div class="detail-grid" id="appGrid"></div>
        <div style="text-align:right"><button onclick="closeModals()" style="padding:9px 22px;background:#f3f4f6;border:none;border-radius:10px;font-weight:600;cursor:pointer">Close</button></div>
    </div>
</div>
<!-- Approve Modal -->
<div class="modal-overlay" id="approveModal"><div class="modal-box" style="max-width:380px">
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:12px">âœ… Approve Application</div>
    <form method="POST"><input type="hidden" name="app_id" id="approveId"><input type="hidden" name="action" value="approve">
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="closeModals()" style="padding:8px 18px;background:#f3f4f6;border:none;border-radius:10px;font-weight:600;cursor:pointer">Cancel</button>
            <button type="submit" style="padding:8px 20px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer">âœ… Approve</button>
        </div>
    </form>
</div></div>
<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal"><div class="modal-box" style="max-width:420px">
    <div style="font-size:1.1rem;font-weight:700;margin-bottom:12px">âŒ Reject Application</div>
    <form method="POST"><input type="hidden" name="app_id" id="rejectId"><input type="hidden" name="action" value="reject">
        <div style="margin-bottom:14px"><label style="font-weight:600;display:block;margin-bottom:7px">Reason *</label>
            <textarea name="rejection_reason" rows="3" required style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;font-family:inherit;font-size:.9rem" placeholder="Why is this being rejected?"></textarea></div>
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="closeModals()" style="padding:8px 18px;background:#f3f4f6;border:none;border-radius:10px;font-weight:600;cursor:pointer">Cancel</button>
            <button type="submit" style="padding:8px 20px;background:#dc2626;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer">âŒ Reject</button>
        </div>
    </form>
</div></div>

<script>
function viewApp(v) {
    const fields=[['#','#'+v.id],['Full Name',v.full_name],['Email',v.email],['Institution',v.institution||'â€”'],['Class',v.class||'â€”'],['Group',v.group||'â€”'],['Phone',v.phone_number],['WhatsApp',v.whatsapp_number||'â€”'],['DOB',v.dob||'â€”'],['Age',v.age||'â€”'],['Division',v.division||'â€”'],['District',v.district||'â€”'],['Guardian',v.guardian_name||'â€”'],['Guardian #',v.guardian_number||'â€”'],['NID/BC',v.nid_bc||'â€”'],['Facebook',v.facebook_link||'â€”'],['Experience',v.experience||'â€”'],['Terms',v.terms_agreed==1?'Agreed':'â€”'],['Event',v.event_title||'â€”'],['Status',v.approval_status],['Submitted',v.submission_date]];
    document.getElementById('appGrid').innerHTML=fields.map(([l,v])=>`<div class="detail-item"><label>${l}</label><span>${v}</span></div>`).join('');
    const ph=document.getElementById('appPhoto');
    ph.innerHTML=v.picture_path?`<img src="../${v.picture_path}" alt="Photo">`:'';
    document.getElementById('viewModal').classList.add('open');
}
function openApprove(id){document.getElementById('approveId').value=id;document.getElementById('approveModal').classList.add('open');}
function openReject(id){document.getElementById('rejectId').value=id;document.getElementById('rejectModal').classList.add('open');}
function closeModals(){document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('open'));}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)closeModals();}));
setTimeout(()=>{const f=document.getElementById('flashMsg');if(f){f.style.opacity='0';f.style.transition='.3s';setTimeout(()=>f.remove(),300);}},6000);
</script>
</body>
</html>
