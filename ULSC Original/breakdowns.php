<?php
/**
 * breakdown & repair records module
 * Log breakdown incidents with category, repair action, downtime (minutes), status
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Breakdowns & Repairs';
$is_admin = isAdmin();

$categories = ['Mechanical Failure', 'Electrical Failure', 'Wear and Tear', 'Misuse / Improper Operation', 'Lack of Maintenance', 'Other'];

/* Handle delete (admin only) */
if ($is_admin && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $pdo->prepare("DELETE FROM breakdowns WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: breakdowns.php?msg=deleted');
    exit;
}

/* Handle add/edit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
    $target_type = $_POST['target_type'] ?? 'equipment';
    $equipment_id = $target_type === 'equipment' ? (int) ($_POST['equipment_id'] ?? 0) : null;
    $vehicle_id = $target_type === 'vehicle' ? (int) ($_POST['vehicle_id'] ?? 0) : null;
    $date_reported = $_POST['date_reported'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $category_other = trim($_POST['category_other'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $detailed_findings = trim($_POST['detailed_findings'] ?? '');
    $parts_cost = isset($_POST['parts_cost']) && $_POST['parts_cost'] !== '' ? (float) $_POST['parts_cost'] : null;
    $labor_cost = isset($_POST['labor_cost']) && $_POST['labor_cost'] !== '' ? (float) $_POST['labor_cost'] : null;
    $other_costs = isset($_POST['other_costs']) && $_POST['other_costs'] !== '' ? (float) $_POST['other_costs'] : null;
    $total_repair_cost = isset($_POST['total_repair_cost']) && $_POST['total_repair_cost'] !== '' ? (float) $_POST['total_repair_cost'] : null;
    $replacement_recommendation = trim($_POST['replacement_recommendation'] ?? '') ?: null;
    $replacement_remarks = trim($_POST['replacement_remarks'] ?? '');
    $usage_reminders = trim($_POST['usage_reminders'] ?? '');
    $misuse_recommendations = !empty($_POST['misuse_recommendations']) ? implode(', ', (array) $_POST['misuse_recommendations']) : null;
    $misuse_remarks = trim($_POST['misuse_remarks'] ?? '');
    $repair_action = trim($_POST['repair_action'] ?? '');
    $downtime_minutes = (int) ($_POST['downtime_minutes'] ?? 0);
    $status = $_POST['status'] ?? 'Ongoing';
    $report_by_name = trim($_POST['report_by_name'] ?? '');
    $checked_by = trim($_POST['checked_by'] ?? '');
    $prepared_by = trim($_POST['prepared_by'] ?? '');
    $prepared_position = trim($_POST['prepared_position'] ?? '');
    $approved_by = trim($_POST['approved_by'] ?? '');

    $errors = [];
    if ($target_type === 'equipment' && !$equipment_id) $errors[] = 'Select equipment';
    if ($target_type === 'vehicle' && !$vehicle_id) $errors[] = 'Select vehicle';
    if (empty($date_reported)) $errors[] = 'Date reported is required';
    if (!in_array($category, $categories)) $errors[] = 'Invalid category';
    if (!in_array($status, ['Ongoing', 'Resolved'])) $errors[] = 'Invalid status';

    if (empty($errors)) {
        $uid = getCurrentUserId();
        $cols = "equipment_id=?, vehicle_id=?, date_reported=?, location=?, category=?, category_other=?, description=?, detailed_findings=?, parts_cost=?, labor_cost=?, other_costs=?, total_repair_cost=?, replacement_recommendation=?, replacement_remarks=?, usage_reminders=?, misuse_recommendations=?, misuse_remarks=?, repair_action=?, downtime_minutes=?, status=?, report_by_name=?, checked_by=?, prepared_by=?, prepared_position=?, approved_by=?";
        $vals = [$equipment_id, $vehicle_id, $date_reported, $location, $category, $category_other, $description, $detailed_findings, $parts_cost, $labor_cost, $other_costs, $total_repair_cost, $replacement_recommendation, $replacement_remarks, $usage_reminders, $misuse_recommendations, $misuse_remarks, $repair_action, $downtime_minutes, $status, $report_by_name, $checked_by, $prepared_by, $prepared_position, $approved_by];
        if ($id) {
            $stmt = $pdo->prepare("UPDATE breakdowns SET $cols WHERE id=?");
            $stmt->execute([...$vals, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO breakdowns (equipment_id, vehicle_id, date_reported, location, category, category_other, description, detailed_findings, parts_cost, labor_cost, other_costs, total_repair_cost, replacement_recommendation, replacement_remarks, usage_reminders, misuse_recommendations, misuse_remarks, repair_action, downtime_minutes, status, report_by_name, checked_by, prepared_by, prepared_position, approved_by, reported_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([...$vals, $uid]);
        }
        header('Location: breakdowns.php?msg=saved');
        exit;
    }
}

/* Filters */
$filter_search = trim($_GET['search'] ?? '');
$filter_category = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';
$sort_by = $_GET['sort'] ?? 'date_reported';
$sort_order = $_GET['order'] ?? 'desc';

/* Validate sort parameters */
$allowed_sorts = ['date_reported', 'equipment_name', 'vehicle_name', 'location', 'category', 'total_repair_cost', 'status'];
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'date_reported';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'desc';

$sql = "SELECT b.*, e.name as equipment_name, e.item_id, v.name as vehicle_name, v.plate_number
    FROM breakdowns b
    LEFT JOIN equipment e ON e.id = b.equipment_id
    LEFT JOIN vehicles v ON v.id = b.vehicle_id
    WHERE (b.equipment_id IS NOT NULL OR b.vehicle_id IS NOT NULL)";
$params = [];
if ($filter_category === 'Equipment') { $sql .= " AND b.equipment_id IS NOT NULL"; }
if ($filter_category === 'Vehicle') { $sql .= " AND b.vehicle_id IS NOT NULL"; }
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
if ($filter_status) { $sql .= " AND b.status = ?"; $params[] = $filter_status; }
if ($filter_from) { $sql .= " AND b.date_reported >= ?"; $params[] = $filter_from; }
if ($filter_to) { $sql .= " AND b.date_reported <= ?"; $params[] = $filter_to; }

// handle sorting with COALESCE for equipment/vehicle names
if ($sort_by === 'equipment_name') {
    $sql .= " ORDER BY COALESCE(e.name, '') $sort_order";
} elseif ($sort_by === 'vehicle_name') {
    $sql .= " ORDER BY COALESCE(v.name, '') $sort_order";
} else {
    $sql .= " ORDER BY b.$sort_by $sort_order";
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
            <label class="search-filter-label">Search Breakdowns</label>
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
            <label class="search-filter-label">Status</label>
            <select name="status" class="modern-select" style="min-width: 140px;">
                <option value="">All</option>
                <option value="Ongoing" <?= $filter_status === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
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
                <i class="bi bi-search me-2"></i>Search Breakdowns
            </button>
        </div>
    </form>
</div>

<div class="d-flex justify-content-end mb-3">
    <?php if ($is_admin): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#breakModal" onclick="openBreakModal()">
        <i class="bi bi-plus-lg"></i> Report Breakdown
    </button>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?sort=date_reported&order=<?= $sort_by === 'date_reported' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Date <?= $sort_by === 'date_reported' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=equipment_name&order=<?= $sort_by === 'equipment_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Item (Equipment / Vehicle) <?= $sort_by === 'equipment_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=location&order=<?= $sort_by === 'location' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Location <?= $sort_by === 'location' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=category&order=<?= $sort_by === 'category' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Category <?= $sort_by === 'category' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=total_repair_cost&order=<?= $sort_by === 'total_repair_cost' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Total Cost (&#8369;) <?= $sort_by === 'total_repair_cost' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?sort=status&order=<?= $sort_by === 'status' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Status <?= $sort_by === 'status' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['date_reported']) ?></td>
                <td><?= $r['equipment_id'] ? (htmlspecialchars($r['equipment_name']) . ' <small class="text-muted">(' . htmlspecialchars($r['item_id']) . ')</small>') : (htmlspecialchars($r['vehicle_name'] ?? '-') . ' <small class="text-muted">(' . htmlspecialchars($r['plate_number'] ?? '') . ')</small>') ?></td>
                <td><?= htmlspecialchars($r['location'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['category']) ?></td>
                <td><?= isset($r['total_repair_cost']) && $r['total_repair_cost'] !== null ? number_format((float)$r['total_repair_cost'], 2) : '-' ?></td>
                <td>
                    <span class="badge <?= $r['status'] === 'Resolved' ? 'badge-resolved' : 'badge-ongoing' ?>"><?= htmlspecialchars($r['status']) ?></span>
                </td>
                <td>
<?php if ($is_admin): ?>
<!-- edit button - primary 3d effect with text -->
<button class="btn btn-sm btn-primary" 
        onclick='openBreakModal(<?= json_encode($r) ?>)'
        title="Edit Breakdown Record"
        style="box-shadow: 0 4px 0 #0b5ed7; transform: translateY(0); transition: all 0.1s; border: none;"
        onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #0b5ed7'"
        onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'"
        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #0b5ed7'">
    <i class="bi bi-pencil me-1"></i> Edit
</button>

<!-- delete button - danger 3d effect with text -->
<button class="btn btn-sm btn-danger" 
        onclick="showDeleteConfirm('Breakdown Record', '<?= htmlspecialchars($r['equipment_name'] ?? 'Equipment') ?> - <?= htmlspecialchars($r['breakdown_type'] ?? '') ?>', 'breakdowns.php?delete=<?= $r['id'] ?>')"
        title="Delete Breakdown Record"
        style="box-shadow: 0 4px 0 #a13d3d; transform: translateY(0); transition: all 0.1s; border: none;"
        onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #a13d3d'"
        onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'"
        onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #a13d3d'">
    <i class="bi bi-trash me-1"></i> Delete
</button>
<?php endif; ?>

                  
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
            <tr><td colspan="7" class="text-muted">No records yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- modal - equipment breakdown report form -->
<div class="modal fade" id="breakModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="margin: 20px auto; max-height: calc(100vh - 40px);">
        <div class="modal-content">
            <form method="POST" id="breakForm">
                <input type="hidden" name="id" id="bid">
                <div class="modal-header">
                    <h5 class="modal-title">Equipment Breakdown Report Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: calc(70vh - 120px); overflow-y: auto;">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date_reported" id="bdate" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apply To *</label>
                            <select class="form-select" name="target_type" id="btarget">
                                <option value="equipment">Equipment</option>
                                <option value="vehicle">Vehicle</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="bEquipWrap">
                            <label class="form-label">Equipment *</label>
                            <select class="form-select" name="equipment_id" id="bequip">
                                <option value="">-- Select --</option>
                                <?php foreach ($equipment_list as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?> (<?= htmlspecialchars($e['item_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="bVehicleWrap" style="display:none">
                            <label class="form-label">Vehicle *</label>
                            <select class="form-select" name="vehicle_id" id="bvehicle">
                                <option value="">-- Select --</option>
                                <?php foreach ($vehicle_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['plate_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Equipment No. / Plate No.</label>
                            <input type="text" class="form-control" id="bplate" readonly placeholder="Auto from selection">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="blocation" placeholder="e.g. BRGY. LUNA, TUY, BATANGAS">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Report By</label>
                            <input type="text" class="form-control" name="report_by_name" id="breportby" placeholder="Name of person reporting">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Checked By</label>
                            <input type="text" class="form-control" name="checked_by" id="bcheckedby" placeholder="Name of person checking">
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">1. Actual Cause of Breakdown</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category" id="bcategory">
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="bcategoryOtherWrap" style="display:none">
                            <label class="form-label">Other (specify)</label>
                            <input type="text" class="form-control" name="category_other" id="bcategoryother" placeholder="Specify other cause">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Detailed Findings</label>
                            <textarea class="form-control" name="detailed_findings" id="bfindings" rows="4" placeholder="Comprehensive description of the breakdown"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="bdesc" rows="2"></textarea>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">2. Total Cost of Repair</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Parts/Materials (&#8369;)</label>
                            <input type="number" class="form-control cost-input" name="parts_cost" id="bpartscost" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Labor (&#8369;)</label>
                            <input type="number" class="form-control cost-input" name="labor_cost" id="blaborcost" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Other Costs (&#8369;)</label>
                            <input type="number" class="form-control cost-input" name="other_costs" id="bothercost" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Repair Cost (&#8369;)</label>
                            <input type="number" class="form-control" name="total_repair_cost" id="btotalcost" min="0" step="0.01" readonly placeholder="0.00">
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">3. Recommendation for Replacement</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Recommendation</label>
                            <select class="form-select" name="replacement_recommendation" id="breplacement">
                                <option value="">-- Select --</option>
                                <option value="Not Required">Not Required – Equipment still serviceable</option>
                                <option value="Recommend for Replacement">Recommend for Replacement</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks / Justification</label>
                            <textarea class="form-control" name="replacement_remarks" id="breplacementremarks" rows="2"></textarea>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">4. Usage Reminders / Preventive Tips</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <textarea class="form-control" name="usage_reminders" id="busagereminders" rows="3" placeholder="List reminders or tips to prevent future breakdowns"></textarea>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">5. Recommendation if Misuse is Confirmed</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="misuse_recommendations[]" value="Operator Re-orientation / Training" id="bmisuse1">
                                <label class="form-check-label" for="bmisuse1">Operator Re-orientation / Training</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="misuse_recommendations[]" value="Written warning to concerned personnel" id="bmisuse2">
                                <label class="form-check-label" for="bmisuse2">Written warning to concerned personnel</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="misuse_recommendations[]" value="Strict Monitoring of Usage Logs" id="bmisuse3">
                                <label class="form-check-label" for="bmisuse3">Strict Monitoring of Usage Logs</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="misuse_recommendations[]" value="Others" id="bmisuse4">
                                <label class="form-check-label" for="bmisuse4">Others</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="misuse_remarks" id="bmisuseremarks" rows="2"></textarea>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">Repair Details</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Repair Action Taken</label>
                            <textarea class="form-control" name="repair_action" id="brepair" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Downtime (minutes)</label>
                            <input type="number" class="form-control" name="downtime_minutes" id="bdowntime" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="bstatus">
                                <option value="Ongoing">Ongoing</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="border-bottom pb-1 mb-2">Sign-off</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Prepared By</label>
                            <input type="text" class="form-control" name="prepared_by" id="bpreparedby">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="prepared_position" id="bpreparedpos">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" id="bprepareddate" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reviewed/Approved By</label>
                            <input type="text" class="form-control" name="approved_by" id="bapprovedby">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-outline-primary" id="btnPrintReport" style="display:none"><i class="bi bi-printer"></i> Print Report</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const equipmentList = <?= json_encode($equipment_list) ?>;
const vehicleList = <?= json_encode($vehicle_list) ?>;

function toggleBreakTarget() {
    const t = document.getElementById('btarget').value;
    document.getElementById('bEquipWrap').style.display = t === 'equipment' ? 'block' : 'none';
    document.getElementById('bVehicleWrap').style.display = t === 'vehicle' ? 'block' : 'none';
    document.getElementById('bequip').required = t === 'equipment';
    document.getElementById('bvehicle').required = t === 'vehicle';
    document.getElementById('bequip').value = '';
    document.getElementById('bvehicle').value = '';
    updatePlateDisplay();
}

function updatePlateDisplay() {
    const t = document.getElementById('btarget').value;
    if (t === 'equipment') {
        const sel = document.getElementById('bequip');
        const eq = equipmentList.find(e => e.id == sel.value);
        document.getElementById('bplate').value = eq ? (eq.item_id || '') : '';
    } else {
        const sel = document.getElementById('bvehicle');
        const v = vehicleList.find(x => x.id == sel.value);
        document.getElementById('bplate').value = v ? (v.plate_number || '') : '';
    }
}

function openBreakModal(row) {
    if (row) {
        document.getElementById('bid').value = row.id;
        document.getElementById('btarget').value = row.vehicle_id ? 'vehicle' : 'equipment';
        toggleBreakTarget();
        document.getElementById('bequip').value = row.equipment_id || '';
        document.getElementById('bvehicle').value = row.vehicle_id || '';
        updatePlateDisplay();
        document.getElementById('bdate').value = row.date_reported;
        document.getElementById('blocation').value = row.location || '';
        document.getElementById('bcategory').value = row.category || 'Other';
        document.getElementById('bcategoryother').value = row.category_other || '';
        document.getElementById('bdesc').value = row.description || '';
        document.getElementById('bfindings').value = row.detailed_findings || '';
        document.getElementById('bpartscost').value = row.parts_cost != null ? row.parts_cost : '';
        document.getElementById('blaborcost').value = row.labor_cost != null ? row.labor_cost : '';
        document.getElementById('bothercost').value = row.other_costs != null ? row.other_costs : '';
        document.getElementById('btotalcost').value = row.total_repair_cost != null ? row.total_repair_cost : '';
        document.getElementById('breplacement').value = row.replacement_recommendation || '';
        document.getElementById('breplacementremarks').value = row.replacement_remarks || '';
        document.getElementById('busagereminders').value = row.usage_reminders || '';
        document.getElementById('bmisuseremarks').value = row.misuse_remarks || '';
        document.getElementById('brepair').value = row.repair_action || '';
        document.getElementById('bdowntime').value = row.downtime_minutes || 0;
        document.getElementById('bstatus').value = row.status || 'Ongoing';
        document.getElementById('breportby').value = row.report_by_name || '';
        document.getElementById('bcheckedby').value = row.checked_by || '';
        document.getElementById('bpreparedby').value = row.prepared_by || '';
        document.getElementById('bpreparedpos').value = row.prepared_position || '';
        document.getElementById('bapprovedby').value = row.approved_by || '';
        const misuseStr = row.misuse_recommendations || '';
        ['Operator Re-orientation / Training', 'Written warning to concerned personnel', 'Strict Monitoring of Usage Logs', 'Others'].forEach((v, i) => {
            document.getElementById('bmisuse' + (i+1)).checked = misuseStr.indexOf(v) >= 0;
        });
        document.getElementById('btnPrintReport').style.display = row.id ? 'inline-block' : 'none';
        document.getElementById('btnPrintReport').href = 'breakdown_report.php?id=' + row.id;
    } else {
        document.getElementById('bid').value = '';
        document.querySelector('#breakModal form').reset();
        document.getElementById('bid').value = '';
        document.getElementById('bdate').value = new Date().toISOString().slice(0,10);
        document.getElementById('bdowntime').value = 0;
        document.getElementById('bstatus').value = 'Ongoing';
        document.getElementById('btnPrintReport').style.display = 'none';
        document.getElementById('btarget').value = 'equipment';
        toggleBreakTarget();
    }
    updatePlateDisplay();
    toggleCategoryOther();
    calcTotalCost();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('breakModal')).show();
}

function toggleCategoryOther() {
    const v = document.getElementById('bcategory').value;
    document.getElementById('bcategoryOtherWrap').style.display = v === 'Other' ? 'block' : 'none';
}

function calcTotalCost() {
    const p = parseFloat(document.getElementById('bpartscost').value) || 0;
    const l = parseFloat(document.getElementById('blaborcost').value) || 0;
    const o = parseFloat(document.getElementById('bothercost').value) || 0;
    document.getElementById('btotalcost').value = (p + l + o).toFixed(2);
}

document.getElementById('btarget').addEventListener('change', toggleBreakTarget);
document.getElementById('bequip').addEventListener('change', updatePlateDisplay);
document.getElementById('bvehicle').addEventListener('change', updatePlateDisplay);
document.getElementById('bcategory').addEventListener('change', toggleCategoryOther);
document.querySelectorAll('.cost-input').forEach(el => el.addEventListener('input', calcTotalCost));
document.getElementById('breakModal').addEventListener('show.bs.modal', function() {
    document.getElementById('bprepareddate').value = new Date().toISOString().slice(0,10);
});

function applyFilters() {
    const q = new URLSearchParams();
    const s = document.getElementById('filterSearch').value;
    const c = document.getElementById('filterCategory').value;
    const st = document.getElementById('filterStatus').value;
    const f = document.getElementById('filterFrom').value;
    const t = document.getElementById('filterTo').value;
    if (s) q.set('search', s);
    if (c) q.set('category', c);
    if (st) q.set('status', st);
    if (f) q.set('from', f);
    if (t) q.set('to', t);
    location.href = 'breakdowns.php?' + q.toString();
}
document.getElementById('filterSearch')?.addEventListener('keypress', function(e) { if (e.key === 'Enter') applyFilters(); });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
