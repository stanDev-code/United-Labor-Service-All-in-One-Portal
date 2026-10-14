<?php
/**
 * maintenance records module
 * Add maintenance records per equipment item. Display history.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Maintenance Records';
$is_admin = isAdmin();

/* staff can add; admin can add/edit/delete */
$can_edit = true; /* same as staff */

/* handle delete (admin only) */
if ($is_admin && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $pdo->prepare("DELETE FROM maintenance_records WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: maintenance.php?msg=deleted');
    exit;
}

/* handle add/edit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $target_type = $_POST['target_type'] ?? 'equipment';
    $equipment_id = $target_type === 'equipment' ? (int) ($_POST['equipment_id'] ?? 0) : null;
    $vehicle_id = $target_type === 'vehicle' ? (int) ($_POST['vehicle_id'] ?? 0) : null;
    $date_performed = $_POST['date_performed'] ?? '';
    $maintenance_type = trim($_POST['maintenance_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $performed_by = trim($_POST['performed_by'] ?? '');
    $total_price = isset($_POST['total_price']) && $_POST['total_price'] !== '' ? (float) $_POST['total_price'] : null;
    $price_breakdown = trim($_POST['price_breakdown'] ?? '');
    $next_maintenance_date = !empty($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : null;
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if ($target_type === 'equipment' && !$equipment_id) $errors[] = 'Select equipment';
    if ($target_type === 'vehicle' && !$vehicle_id) $errors[] = 'Select vehicle';
    if (empty($date_performed)) $errors[] = 'Date performed is required';
    if (empty($maintenance_type)) $errors[] = 'Maintenance type is required';
    if (empty($performed_by)) $errors[] = 'Performed by is required';

    if (empty($errors)) {
        $uid = getCurrentUserId();
        if ($id) {
            $stmt = $pdo->prepare("UPDATE maintenance_records SET equipment_id=?, vehicle_id=?, date_performed=?, maintenance_type=?, description=?, performed_by=?, total_price=?, price_breakdown=?, next_maintenance_date=?, notes=? WHERE id=?");
            $stmt->execute([$equipment_id, $vehicle_id, $date_performed, $maintenance_type, $description, $performed_by, $total_price, $price_breakdown ?: null, $next_maintenance_date ?: null, $notes, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO maintenance_records (equipment_id, vehicle_id, date_performed, maintenance_type, description, performed_by, total_price, price_breakdown, next_maintenance_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$equipment_id, $vehicle_id, $date_performed, $maintenance_type, $description, $performed_by, $total_price, $price_breakdown ?: null, $next_maintenance_date ?: null, $notes, $uid]);
        }
        header('Location: maintenance.php?msg=saved');
        exit;
    }
}

/* filters */
$filter_search = trim($_GET['search'] ?? '');
$filter_category = $_GET['category'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';
$sort_by = $_GET['sort'] ?? 'date_performed';
$sort_order = $_GET['order'] ?? 'desc';

/* validate sort parameters */
$allowed_sorts = ['date_performed', 'equipment_name', 'vehicle_name', 'maintenance_type', 'performed_by', 'total_price', 'next_maintenance_date'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'date_performed';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'desc';

$sql = "SELECT mr.*, e.name as equipment_name, e.item_id, v.name as vehicle_name, v.plate_number
    FROM maintenance_records mr
    LEFT JOIN equipment e ON e.id = mr.equipment_id
    LEFT JOIN vehicles v ON v.id = mr.vehicle_id
    WHERE (mr.equipment_id IS NOT NULL OR mr.vehicle_id IS NOT NULL)";
$params = [];
if ($filter_category === 'Equipment') { $sql .= " AND mr.equipment_id IS NOT NULL"; }
if ($filter_category === 'Vehicle') { $sql .= " AND mr.vehicle_id IS NOT NULL"; }
if ($filter_search) {
    $term = "%$filter_search%";
    if ($filter_category === 'Equipment') {
        $sql .= " AND (e.name LIKE ? OR e.item_id LIKE ?)";
        $params = array_merge($params, [$term, $term]);
    } elseif ($filter_category === 'Vehicle') {
        $sql .= " AND (v.name LIKE ? OR v.plate_number LIKE ?)";
        $params = array_merge($params, [$term, $term]);
    } else {
        $sql .= " AND ((e.name LIKE ? OR e.item_id LIKE ?) OR (v.name LIKE ? OR v.plate_number LIKE ?))";
        $params = array_merge($params, [$term, $term, $term, $term]);
    }
}
if ($filter_from) {
    $sql .= " AND mr.date_performed >= ?";
    $params[] = $filter_from;
}
if ($filter_to) {
    $sql .= " AND mr.date_performed <= ?";
    $params[] = $filter_to;
}

// Handle sorting with COALESCE for equipment/vehicle names
if ($sort_by === 'equipment_name') {
    $sql .= " ORDER BY COALESCE(e.name, '') $sort_order";
} elseif ($sort_by === 'vehicle_name') {
    $sql .= " ORDER BY COALESCE(v.name, '') $sort_order";
} else {
    $sql .= " ORDER BY mr.$sort_by $sort_order";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$equipment_list = $pdo->query("SELECT id, item_id, name FROM equipment ORDER BY name")->fetchAll();
$vehicle_list = $pdo->query("SELECT id, name, plate_number FROM vehicles ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= $_GET['msg'] === 'saved' ? 'Record saved.' : 'Record deleted.' ?></div>
<?php endif; ?>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Maintenance</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Search by name, ID, or plate..." value="<?= htmlspecialchars($filter_search) ?>">
            </div>
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">Category</label>
            <select name="category" class="modern-select" style="min-width: 140px;">
                <option value="">All</option>
                <option value="Equipment" <?= $filter_category === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                <option value="Vehicle" <?= $filter_category === 'Vehicle' ? 'selected' : '' ?>>Vehicle</option>
            </select>
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">From Date</label>
            <input type="date" name="from" class="modern-date-input" value="<?= htmlspecialchars($filter_from) ?>" style="min-width: 150px;">
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">To Date</label>
            <input type="date" name="to" class="modern-date-input" value="<?= htmlspecialchars($filter_to) ?>" style="min-width: 150px;">
        </div>
        
        <div class="search-filter-item">
            <button type="submit" class="modern-btn-primary">
                <i class="bi bi-search me-2"></i>Search Records
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <?php if ($is_admin): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintModal" onclick="openMaintenanceModal()">
        <i class="bi bi-plus-lg"></i> Add Maintenance
    </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=date_performed&order=<?= $sort_by === 'date_performed' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Date <?= $sort_by === 'date_performed' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=equipment_name&order=<?= $sort_by === 'equipment_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Item (Equipment / Vehicle) <?= $sort_by === 'equipment_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=maintenance_type&order=<?= $sort_by === 'maintenance_type' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Type <?= $sort_by === 'maintenance_type' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Description</th>
                <th><a href="?sort=performed_by&order=<?= $sort_by === 'performed_by' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Performed By <?= $sort_by === 'performed_by' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=total_price&order=<?= $sort_by === 'total_price' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Price <?= $sort_by === 'total_price' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Price Breakdown</th>
                <th><a href="?sort=next_maintenance_date&order=<?= $sort_by === 'next_maintenance_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Next Schedule <?= $sort_by === 'next_maintenance_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['date_performed']) ?></td>
                <td><?= $r['equipment_id'] ? (htmlspecialchars($r['equipment_name']) . ' <small class="text-muted">(' . htmlspecialchars($r['item_id']) . ')</small>') : (htmlspecialchars($r['vehicle_name'] ?? '-') . ' <small class="text-muted">(' . htmlspecialchars($r['plate_number'] ?? '') . ')</small>') ?></td>
                <td><?= htmlspecialchars($r['maintenance_type']) ?></td>
                <td><?= htmlspecialchars($r['description'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['performed_by']) ?></td>
                <td><?= $r['total_price'] !== null ? number_format((float)$r['total_price'], 2) : '-' ?></td>
                <td><?= $r['price_breakdown'] ? nl2br(htmlspecialchars($r['price_breakdown'])) : '-' ?></td>
                <td><?= htmlspecialchars($r['next_maintenance_date'] ?? '-') ?></td>
                <?php if ($is_admin): ?>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" 
                                onclick='openMaintenanceModal(<?= json_encode($r) ?>)'
                                title="Edit Maintenance Record"
                                style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
                                onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
                                onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
                                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>

                        <button class="btn btn-sm btn-danger" 
                                onclick="showDeleteConfirm('Maintenance Record', '<?= htmlspecialchars($r['description'] ?? '') ?>', 'maintenance.php?delete=<?= $r['id'] ?>')"
                                title="Delete Maintenance Record"
                                style="box-shadow: 0 4px 0 #a13d3d; transform: translateY(0); transition: all 0.1s; border: none;"
                                onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #a13d3d'"
                                onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'"
                                onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
            <tr><td colspan="<?= $is_admin ? 9 : 8 ?>" class="text-muted">No records yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="maintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="id" id="mid">
                <div class="modal-header border-0 bg-white">
                    <h5 class="modal-title text-dark fw-semibold">Maintenance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Apply To <span class="text-danger">*</span></label>
                            <select class="form-select" name="target_type" id="mtarget">
                                <option value="equipment">Equipment</option>
                                <option value="vehicle">Vehicle</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="mEquipWrap">
                            <label class="form-label text-dark fw-medium">Equipment <span class="text-danger">*</span></label>
                            <select class="form-select" name="equipment_id" id="mequip">
                                <option value="">-- Select --</option>
                                <?php foreach ($equipment_list as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?> (<?= htmlspecialchars($e['item_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="mVehicleWrap" style="display:none">
                            <label class="form-label text-dark fw-medium">Vehicle <span class="text-danger">*</span></label>
                            <select class="form-select" name="vehicle_id" id="mvehicle">
                                <option value="">-- Select --</option>
                                <?php foreach ($vehicle_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['plate_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Date Performed <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_performed" id="mdate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Type *</label>
                            <input type="text" class="form-control" name="maintenance_type" id="mtype" placeholder="e.g. Change Oil" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Performed By *</label>
                            <input type="text" class="form-control" name="performed_by" id="mperformed" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Price</label>
                            <input type="number" class="form-control" name="total_price" id="mprice" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Next Maintenance Date</label>
                            <input type="date" class="form-control" name="next_maintenance_date" id="mnext">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Price Breakdown</label>
                            <textarea class="form-control" name="price_breakdown" id="mbreakdown" rows="2" placeholder="e.g. Parts: 500, Labor: 300, Oil: 50"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="mdesc" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="mnotes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMaintenanceTarget() {
    const t = document.getElementById('mtarget').value;
    document.getElementById('mEquipWrap').style.display = t === 'equipment' ? 'block' : 'none';
    document.getElementById('mVehicleWrap').style.display = t === 'vehicle' ? 'block' : 'none';
    document.getElementById('mequip').required = t === 'equipment';
    document.getElementById('mvehicle').required = t === 'vehicle';
    document.getElementById('mequip').value = t === 'equipment' ? document.getElementById('mequip').value : '';
    document.getElementById('mvehicle').value = t === 'vehicle' ? document.getElementById('mvehicle').value : '';
}
function openMaintenanceModal(row) {
    if (row) {
        document.getElementById('mid').value = row.id;
        document.getElementById('mtarget').value = row.vehicle_id ? 'vehicle' : 'equipment';
        toggleMaintenanceTarget();
        document.getElementById('mequip').value = row.equipment_id || '';
        document.getElementById('mvehicle').value = row.vehicle_id || '';
        document.getElementById('mdate').value = row.date_performed;
        document.getElementById('mtype').value = row.maintenance_type || '';
        document.getElementById('mdesc').value = row.description || '';
        document.getElementById('mperformed').value = row.performed_by || '';
        document.getElementById('mprice').value = row.total_price ?? '';
        document.getElementById('mbreakdown').value = row.price_breakdown || '';
        document.getElementById('mnext').value = row.next_maintenance_date || '';
        document.getElementById('mnotes').value = row.notes || '';
    } else {
        document.getElementById('mid').value = '';
        document.querySelector('#maintModal form').reset();
        document.getElementById('mid').value = '';
        document.getElementById('mtarget').value = 'equipment';
        toggleMaintenanceTarget();
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('maintModal')).show();
}
document.getElementById('mtarget')?.addEventListener('change', toggleMaintenanceTarget);
function applyFilters() {
    const q = new URLSearchParams();
    const s = document.getElementById('filterSearch').value;
    const c = document.getElementById('filterCategory').value;
    const f = document.getElementById('filterFrom').value;
    const t = document.getElementById('filterTo').value;
    if (s) q.set('search', s);
    if (c) q.set('category', c);
    if (f) q.set('from', f);
    if (t) q.set('to', t);
    location.href = 'maintenance.php?' + q.toString();
}

function clearSearch() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterFrom').value = '';
    document.getElementById('filterTo').value = '';
    applyFilters();
}
document.getElementById('filterSearch')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') applyFilters(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
