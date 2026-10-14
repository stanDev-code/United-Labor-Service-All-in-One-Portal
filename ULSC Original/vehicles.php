<?php
/**
 * Vehicles Module - Transport vehicles separate from equipment
 * Tracks oil changes, maintenance & breakdown costs per vehicle
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Vehicles';
$is_admin = isAdmin();

/* Handle delete (admin only) */
if ($is_admin && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: vehicles.php?msg=deleted');
    exit;
}

/* Handle add/edit vehicle (admin only) */
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['oil_change'])) {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? 'Transport';
    $plate_number = trim($_POST['plate_number'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'Operational';
    $facilitated_by = trim($_POST['facilitated_by'] ?? '');
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $last_oil = !empty($_POST['last_oil_change_date']) ? $_POST['last_oil_change_date'] : null;
    $next_oil = !empty($_POST['next_oil_due_date']) ? $_POST['next_oil_due_date'] : null;

    $errors = [];
    if (empty($name)) $errors[] = 'Vehicle name is required';
    if (empty($plate_number)) $errors[] = 'Plate number is required';
    if (!in_array($status, ['Operational', 'Under Repair', 'Out of Service'])) $errors[] = 'Invalid status';
    if (!in_array($vehicle_type, ['Transport', 'Service'])) $errors[] = 'Invalid vehicle type';

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE vehicles SET name=?, vehicle_type=?, plate_number=?, location=?, status=?, facilitated_by=?, purchase_date=?, notes=?, last_oil_change_date=?, next_oil_due_date=? WHERE id=?");
            $stmt->execute([$name, $vehicle_type, $plate_number, $location, $status, $facilitated_by ?: null, $purchase_date ?: null, $notes, $last_oil ?: null, $next_oil ?: null, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehicles (name, vehicle_type, plate_number, location, status, facilitated_by, purchase_date, notes, last_oil_change_date, next_oil_due_date) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $vehicle_type, $plate_number, $location, $status, $facilitated_by ?: null, $purchase_date ?: null, $notes, $last_oil ?: null, $next_oil ?: null]);
        }
        header('Location: vehicles.php?msg=saved');
        exit;
    }
}

/* Handle oil change log (admin only) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['oil_change'])) {
    requireAdmin();
    
    $vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);
    $change_date = $_POST['change_date'] ?? '';
    $notes = trim($_POST['oil_notes'] ?? '');

    if ($vehicle_id && !empty($change_date)) {
        /* Check vehicle status before allowing oil change */
        $status_check = $pdo->prepare("SELECT status FROM vehicles WHERE id = ?");
        $status_check->execute([$vehicle_id]);
        $vehicle = $status_check->fetch();
        
        if ($vehicle && $vehicle['status'] === 'Out of Service') {
            header('Location: vehicles.php?error=oil_out_of_service');
            exit;
        }
        
        $next_due = date('Y-m-d', strtotime($change_date . ' +3 months'));
        $pdo->prepare("INSERT INTO vehicle_oil_changes (vehicle_id, change_date, next_due_date, notes) VALUES (?,?,?,?)")->execute([$vehicle_id, $change_date, $next_due, $notes ?: null]);
        $pdo->prepare("UPDATE vehicles SET last_oil_change_date=?, next_oil_due_date=? WHERE id=?")->execute([$change_date, $next_due, $vehicle_id]);
        header('Location: vehicles.php?msg=oil_saved');
        exit;
    }
}

/* Fetch vehicles with total cost (maintenance + breakdowns) */
$filter_status = $_GET['status'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'desc';

/* Validate sort parameters */
$allowed_sorts = ['name', 'vehicle_type', 'plate_number', 'location', 'last_oil_change_date', 'next_oil_due_date', 'status', 'total_cost'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'name';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'asc';

$sql = "SELECT v.*,
    (SELECT COALESCE(SUM(mr.total_price), 0) FROM maintenance_records mr WHERE mr.vehicle_id = v.id) as maint_cost,
    (SELECT COALESCE(SUM(b.total_repair_cost), 0) FROM breakdowns b WHERE b.vehicle_id = v.id) as breakdown_cost
    FROM vehicles v WHERE 1=1";
$params = [];
if ($filter_status) { $sql .= " AND v.status = ?"; $params[] = $filter_status; }
if ($filter_search) { $sql .= " AND (v.name LIKE ? OR v.plate_number LIKE ?)"; $q = "%$filter_search%"; $params = array_merge($params, [$q, $q]); }

/* Add total cost calculation for sorting */
if ($sort_by === 'total_cost') {
    $sql .= " ORDER BY (maint_cost + breakdown_cost) $sort_order";
} else {
    $sql .= " ORDER BY v.$sort_by $sort_order";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= $_GET['msg'] === 'oil_saved' ? 'Oil change recorded.' : ($_GET['msg'] === 'saved' ? 'Vehicle saved.' : 'Vehicle deleted.') ?></div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'oil_out_of_service'): ?>
    <div class="alert alert-danger">Cannot record oil change: Vehicle is out of service.</div>
<?php endif; ?>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Vehicles</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Search by name or plate number..." value="<?= htmlspecialchars($filter_search) ?>">
            </div>
        </div>
        
        <div class="search-filter-item">
            <label class="search-filter-label">Status</label>
            <select name="status" class="modern-select" style="min-width: 150px;">
                <option value="">All Statuses</option>
                <option value="Operational" <?= $filter_status === 'Operational' ? 'selected' : '' ?>>Operational</option>
                <option value="Under Repair" <?= $filter_status === 'Under Repair' ? 'selected' : '' ?>>Under Repair</option>
                <option value="Out of Service" <?= $filter_status === 'Out of Service' ? 'selected' : '' ?>>Out of Service</option>
            </select>
        </div>
        
        <div class="search-filter-item">
            <button type="submit" class="modern-btn-primary">
                <i class="bi bi-search me-2"></i>Search Vehicles
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <?php if ($is_admin): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="openVehicleModal()">
        <i class="bi bi-plus-lg"></i> Add Vehicle
    </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=name&order=<?= $sort_by === 'name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Vehicle <?= $sort_by === 'name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=vehicle_type&order=<?= $sort_by === 'vehicle_type' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Type <?= $sort_by === 'vehicle_type' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=plate_number&order=<?= $sort_by === 'plate_number' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Plate No. <?= $sort_by === 'plate_number' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=location&order=<?= $sort_by === 'location' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Location <?= $sort_by === 'location' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=last_oil_change_date&order=<?= $sort_by === 'last_oil_change_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Last Oil Change <?= $sort_by === 'last_oil_change_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=next_oil_due_date&order=<?= $sort_by === 'next_oil_due_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Next Oil Due <?= $sort_by === 'next_oil_due_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=status&order=<?= $sort_by === 'status' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Status <?= $sort_by === 'status' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Facilitated By</th>
                <th><a href="?sort=purchase_date&order=<?= $sort_by === 'purchase_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Purchase Date <?= $sort_by === 'purchase_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=total_cost&order=<?= $sort_by === 'total_cost' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Total Cost (&#8369;) <?= $sort_by === 'total_cost' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $v): 
                $maint = (float)($v['maint_cost'] ?? 0);
                $breakdown = (float)($v['breakdown_cost'] ?? 0);
                $totalCost = $maint + $breakdown;
            ?>
            <tr>
                <td><?= htmlspecialchars($v['name']) ?></td>
                <td>
                    <?php 
                    $type_class = $v['vehicle_type'] === 'Service' ? 'badge-info' : 'badge-primary';
                    $type_icon = $v['vehicle_type'] === 'Service' ? 'bi-truck' : 'bi-truck';
                    ?>
                    <span class="badge <?= $type_class ?>">
                        <i class="bi <?= $type_icon ?> me-1"></i><?= htmlspecialchars($v['vehicle_type']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($v['plate_number']) ?></td>
                <td><?= htmlspecialchars($v['location'] ?? '-') ?></td>
                <td><?= htmlspecialchars($v['last_oil_change_date'] ?? '-') ?></td>
                <td>
                    <?php
                    $next = $v['next_oil_due_date'] ?? null;
                    $overdue = $next && strtotime($next) < time();
                    ?>
                    <span class="<?= $overdue ? 'text-danger fw-bold' : '' ?>"><?= htmlspecialchars($next ?? '-') ?></span>
                </td>
                <td>
                    <?php $s = $v['status']; $b = match($s) { 'Operational' => 'badge-operational', 'Under Repair' => 'badge-repair', default => 'badge-out' }; ?>
                    <span class="badge <?= $b ?>"><?= htmlspecialchars($s) ?></span>
                </td>
                <td><?= htmlspecialchars($v['facilitated_by'] ?? '-') ?></td>
                <td><?= htmlspecialchars($v['purchase_date'] ?? '-') ?></td>
                <td><strong><?= number_format($totalCost, 2) ?></strong></td>
                <td>
                <div class="d-flex align-items-center gap-1">
    <!-- Oil Change Button - Success 3D effect with text -->
    <?php if ($v['status'] === 'Out of Service'): ?>
    <button class="btn btn-sm btn-secondary" 
            disabled
            title="Oil change not allowed - Vehicle is out of service"
            style="box-shadow: 0 4px 0 #6c757d; transform: translateY(0); transition: all 0.1s; border: none; opacity: 0.6;">
        <i class="bi bi-droplet me-1"></i> Oil
    </button>
    <?php else: ?>
    <button class="btn btn-sm btn-success" 
            onclick="openOilModal(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['name'])) ?>', '<?= htmlspecialchars($v['plate_number'] ?? '') ?>')" 
            title="Record Oil Change for <?= htmlspecialchars($v['name']) ?>"
            style="box-shadow: 0 4px 0 #1e7e34; transform: translateY(0); transition: all 0.1s; border: none;"
            onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #1e7e34'"
            onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #1e7e34'"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #1e7e34'">
        <i class="bi bi-droplet me-1"></i> Oil
    </button>
    <?php endif; ?>
    
    <?php if ($is_admin): ?>
    <!-- Edit Button - Primary 3D effect with text -->
    <?php if ($v['status'] === 'Out of Service'): ?>
    <button class="btn btn-sm btn-secondary" 
            disabled
            title="Edit not allowed - Vehicle is out of service"
            style="box-shadow: 0 4px 0 #6c757d; transform: translateY(0); transition: all 0.1s; border: none; opacity: 0.6;">
        <i class="bi bi-pencil me-1"></i> Edit
    </button>
    <?php else: ?>
    <button class="btn btn-sm btn-primary" 
            onclick="openVehicleModal(<?= htmlspecialchars(json_encode($v)) ?>)"
            title="Edit <?= htmlspecialchars($v['name']) ?>"
            style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
            onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
            onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
        <i class="bi bi-pencil me-1"></i> Edit
    </button>
    <?php endif; ?>
    
    <!-- Delete Button - Danger 3D effect with text -->
    <button class="btn btn-sm btn-danger" 
            onclick="showDeleteConfirm('<?= htmlspecialchars($v['name']) ?>', 'Plate: <?= htmlspecialchars($v['plate_number'] ?? 'N/A') ?>', 'vehicles.php?delete=<?= $v['id'] ?>')"
            title="Delete <?= htmlspecialchars($v['name']) ?>"
            style="box-shadow: 0 4px 0 #a13d3d; transform: translateY(0); transition: all 0.1s; border: none;"
            onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #a13d3d'"
            onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'"
            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'">
        <i class="bi bi-trash me-1"></i> Delete
    </button>
    <?php endif; ?>
</div>

                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($vehicles)): ?>
            <tr><td colspan="11" class="text-muted">No vehicles yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Vehicle Modal (admin only) -->
<?php if ($is_admin): ?>
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="id" id="vid">
                <div class="modal-header border-0 bg-white">
                    <h5 class="modal-title text-dark fw-semibold">Vehicle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Vehicle Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="vname" required placeholder="e.g. Toyota HiAce">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Vehicle Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="vehicle_type" id="vvehicletype">
                                <option value="Transport">Transport Vehicle</option>
                                <option value="Service">Service Vehicle</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Plate No. <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="plate_number" id="vplate" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-dark fw-medium">Location</label>
                            <input type="text" class="form-control" name="location" id="vlocation" placeholder="e.g. BRGY. LUNA, TUY, BATANGAS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Last Oil Change Date</label>
                            <input type="date" class="form-control" name="last_oil_change_date" id="vlastoil">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Next Oil Due Date</label>
                            <input type="date" class="form-control" name="next_oil_due_date" id="vnextoil">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="vstatus">
                                <option value="Operational">Operational</option>
                                <option value="Under Repair">Under Repair</option>
                                <option value="Out of Service">Out of Service</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Facilitated By</label>
                            <input type="text" class="form-control" name="facilitated_by" id="vfacilitated">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-medium">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" id="vpurchase">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-dark fw-medium">Notes</label>
                            <textarea class="form-control" name="notes" id="vnotes" rows="3" placeholder="Enter any additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Save Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Oil Change Modal (all users) -->
<div class="modal fade" id="oilModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="oil_change" value="1">
                <input type="hidden" name="vehicle_id" id="ovehicle">
                <div class="modal-header border-0 bg-white">
                    <h5 class="modal-title text-dark fw-semibold">Record Oil Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light p-3 mb-3">
                            <i class="bi bi-droplet text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <h6 class="text-dark mb-2">Oil Change Details</h6>
                        <p class="text-muted mb-0">Record oil change for the following vehicle:</p>
                    </div>
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-truck text-primary me-2"></i>
                            <div class="flex-grow-1">
                                <div class="fw-medium text-dark" id="ovehiclename"></div>
                                <div class="small text-muted" id="ovehicleplate"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark fw-medium">Change Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="change_date" id="ochangedate" required value="<?= date('Y-m-d') ?>">
                        <small class="text-muted">Next oil due will be set to 3 months from this date.</small>
                    </div>
                    <div>
                        <label class="form-label text-dark fw-medium">Notes</label>
                        <textarea class="form-control" name="oil_notes" id="ooilnotes" rows="3" placeholder="Enter any notes about the oil change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-droplet me-1"></i>Record Oil Change
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVehicleModal(row) {
    if (row) {
        document.getElementById('vid').value = row.id;
        document.getElementById('vname').value = row.name;
        document.getElementById('vvehicletype').value = row.vehicle_type || 'Transport';
        document.getElementById('vplate').value = row.plate_number;
        document.getElementById('vlocation').value = row.location || '';
        document.getElementById('vlastoil').value = row.last_oil_change_date || '';
        document.getElementById('vnextoil').value = row.next_oil_due_date || '';
        document.getElementById('vstatus').value = row.status;
        document.getElementById('vfacilitated').value = row.facilitated_by || '';
        document.getElementById('vpurchase').value = row.purchase_date || '';
        document.getElementById('vnotes').value = row.notes || '';
    } else {
        document.getElementById('vid').value = '';
        document.getElementById('vname').value = '';
        document.getElementById('vvehicletype').value = 'Transport';
        document.getElementById('vplate').value = '';
        document.getElementById('vlocation').value = '';
        document.getElementById('vlastoil').value = '';
        document.getElementById('vnextoil').value = '';
        document.getElementById('vstatus').value = 'Operational';
        document.getElementById('vfacilitated').value = '';
        document.getElementById('vpurchase').value = '';
        document.getElementById('vnotes').value = '';
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleModal')).show();
}
function openOilModal(vid, vname, vplate) {
    document.getElementById('ovehicle').value = vid;
    document.getElementById('ovehiclename').textContent = vname;
    document.getElementById('ovehicleplate').textContent = '(' + vplate + ')';
    document.getElementById('ochangedate').value = new Date().toISOString().slice(0,10);
    document.getElementById('ooilnotes').value = '';
    new bootstrap.Modal(document.getElementById('oilModal')).show();
}
function openHistoryModal(vid, vname) {
    document.getElementById('hvehiclename').textContent = vname;
    document.getElementById('historyBody').innerHTML = '<p class="text-muted">Loading...</p>';
    const m = new bootstrap.Modal(document.getElementById('historyModal'));
    m.show();
    fetch('vehicle_oil_history.php?vehicle_id=' + vid)
        .then(r => r.text())
        .then(html => { document.getElementById('historyBody').innerHTML = html; })
        .catch(() => { document.getElementById('historyBody').innerHTML = '<p class="text-danger">Failed to load history</p>'; });
}
function applyFilters() {
    const q = new URLSearchParams();
    const s = document.getElementById('searchInput').value;
    const st = document.getElementById('filterStatus').value;
    if (s) q.set('search', s);
    if (st) q.set('status', st);
    location.href = 'vehicles.php?' + q.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    applyFilters();
}
document.getElementById('searchInput')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') applyFilters(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
