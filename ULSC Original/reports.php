<?php
/**
 * maintenance & breakdown history reports
 * search by date, item, status
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Maintenance & Breakdown History';

$tab = $_GET['tab'] ?? 'maintenance';
$filter_search = trim($_GET['search'] ?? '');
$filter_category = $_GET['category'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';
$filter_status = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? ($tab === 'maintenance' ? 'date_performed' : 'date_reported');
$sort_order = $_GET['order'] ?? 'desc';

/* Validate sort parameters */
if ($tab === 'maintenance') {
    $allowed_sorts = ['date_performed', 'equipment_name', 'vehicle_name', 'maintenance_type', 'performed_by', 'total_price', 'next_maintenance_date'];
} else {
    $allowed_sorts = ['date_reported', 'equipment_name', 'vehicle_name', 'location', 'category', 'total_repair_cost', 'status'];
}
$allowed_orders = ['asc', 'desc'];
if (!in_array($sort_by, $allowed_sorts)) $sort_by = $tab === 'maintenance' ? 'date_performed' : 'date_reported';
if (!in_array($sort_order, $allowed_orders)) $sort_order = 'desc';

$total_equipment = (int) $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$total_vehicles = (int) $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();

/* Build queries based on tab */
if ($tab === 'maintenance') {
    $sql = "SELECT mr.*, e.name as equipment_name, e.item_id, v.name as vehicle_name, v.plate_number FROM maintenance_records mr LEFT JOIN equipment e ON e.id = mr.equipment_id LEFT JOIN vehicles v ON v.id = mr.vehicle_id WHERE (mr.equipment_id IS NOT NULL OR mr.vehicle_id IS NOT NULL)";
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
    if ($filter_from) { $sql .= " AND mr.date_performed >= ?"; $params[] = $filter_from; }
    if ($filter_to) { $sql .= " AND mr.date_performed <= ?"; $params[] = $filter_to; }
    
    /* Handle sorting with COALESCE for equipment/vehicle names (maintenance) */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    /* Group records by equipment/vehicle to track which items we've shown history button for */
    $shown_history_items = [];
} elseif ($tab === 'breakdowns') {
    $sql = "SELECT b.*, e.name as equipment_name, e.item_id, v.name as vehicle_name, v.plate_number FROM breakdowns b LEFT JOIN equipment e ON e.id = b.equipment_id LEFT JOIN vehicles v ON v.id = b.vehicle_id WHERE (b.equipment_id IS NOT NULL OR b.vehicle_id IS NOT NULL)";
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
    
    /* Handle sorting with COALESCE for equipment/vehicle names (breakdowns) */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} else {
    $tab = 'maintenance';
    $records = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card card-summary">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Total Equipment</h6><h4 class="mb-0"><?= $total_equipment ?></h4></div>
                <i class="bi bi-gear text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-summary">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Total Vehicles</h6><h4 class="mb-0"><?= $total_vehicles ?></h4></div>
                <i class="bi bi-truck text-info"></i>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'maintenance' ? 'active' : '' ?>" href="reports.php?tab=maintenance&<?= http_build_query(array_filter(['search' => $filter_search ?: null, 'category' => $filter_category ?: null, 'from' => $filter_from ?: null, 'to' => $filter_to ?: null])) ?>">Maintenance History</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'breakdowns' ? 'active' : '' ?>" href="reports.php?tab=breakdowns&<?= http_build_query(array_filter(['search' => $filter_search ?: null, 'category' => $filter_category ?: null, 'status' => $filter_status ?: null, 'from' => $filter_from ?: null, 'to' => $filter_to ?: null])) ?>">Breakdown History</a>
    </li>
</ul>

<div class="search-container" style="margin-bottom: 24px;">
    <form method="GET" class="search-filters-row">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        
        <div class="search-filter-item" style="flex: 1; min-width: 280px;">
            <label class="search-filter-label">Search Items</label>
            <div class="search-input-wrapper">
                <div class="search-icon-wrapper">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" name="search" class="modern-search-input" placeholder="Name, ID, or Plate..." value="<?= htmlspecialchars($filter_search) ?>">
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
        
        <?php if ($tab === 'breakdowns'): ?>
        <div class="search-filter-item">
            <label class="search-filter-label">Status</label>
            <select name="status" class="modern-select" style="min-width: 140px;">
                <option value="">All</option>
                <option value="Ongoing" <?= $filter_status === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>
        <?php endif; ?>
        
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

<div class="table-responsive">
    <?php if ($tab === 'maintenance'): ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?tab=maintenance&sort=date_performed&order=<?= $sort_by === 'date_performed' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Date <?= $sort_by === 'date_performed' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=maintenance&sort=equipment_name&order=<?= $sort_by === 'equipment_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Equipment <?= $sort_by === 'equipment_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=maintenance&sort=maintenance_type&order=<?= $sort_by === 'maintenance_type' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Type <?= $sort_by === 'maintenance_type' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Details</th>
                <th><a href="?tab=maintenance&sort=performed_by&order=<?= $sort_by === 'performed_by' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Performed By <?= $sort_by === 'performed_by' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=maintenance&sort=total_price&order=<?= $sort_by === 'total_price' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Total Price <?= $sort_by === 'total_price' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Price Details</th>
                <th><a href="?tab=maintenance&sort=next_maintenance_date&order=<?= $sort_by === 'next_maintenance_date' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Next Maintenance Date <?= $sort_by === 'next_maintenance_date' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): 
                /* Create unique key for equipment/vehicle */
                $item_key = ($r['equipment_id'] ? 'equipment_' . $r['equipment_id'] : 'vehicle_' . $r['vehicle_id']);
                $show_history_button = !in_array($item_key, $shown_history_items);
                if ($show_history_button) {
                    $shown_history_items[] = $item_key;
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($r['date_performed']) ?></td>
                <td><?= ($r['equipment_id'] ? htmlspecialchars($r['equipment_name']) : htmlspecialchars($r['vehicle_name'] ?? '-')) ?> (<?= htmlspecialchars($r['item_id'] ?? $r['plate_number'] ?? '-') ?>)</td>
                <td><?= htmlspecialchars($r['maintenance_type']) ?></td>
                <td><?= htmlspecialchars($r['description'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['performed_by']) ?></td>
                <td><?= isset($r['total_price']) && $r['total_price'] !== null ? number_format((float)$r['total_price'], 2) : '-' ?></td>
                <td><?= !empty($r['price_breakdown']) ? nl2br(htmlspecialchars($r['price_breakdown'])) : '-' ?></td>
                <td><?= htmlspecialchars($r['next_maintenance_date'] ?? '-') ?></td>
                <td>
                    <?php if ($show_history_button): ?>
                    <!-- Printable Report Button - Only show for first occurrence -->
                    <button class="btn btn-sm btn-secondary" 
                            onclick="window.open('maintenance_history_print.php?type=<?= $r['equipment_id'] ? 'equipment' : 'vehicle' ?>&id=<?= $r['equipment_id'] ?: $r['vehicle_id'] ?>&from=<?= urlencode($filter_from) ?>&to=<?= urlencode($filter_to) ?>', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes')"
                            title="Print Maintenance Report"
                            style="box-shadow: 0 4px 0 #5a6268; transform: translateY(0); transition: all 0.1s; border: none;"
                            onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #5a6268'"
                            onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #5a6268'"
                            onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #5a6268'">
                        <i class="bi bi-file-text me-1"></i> Report
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
            <tr><td colspan="9" class="text-muted">No maintenance records found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php else: ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><a href="?tab=breakdowns&sort=date_reported&order=<?= $sort_by === 'date_reported' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Date <?= $sort_by === 'date_reported' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=breakdowns&sort=equipment_name&order=<?= $sort_by === 'equipment_name' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Equipment <?= $sort_by === 'equipment_name' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=breakdowns&sort=location&order=<?= $sort_by === 'location' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Location <?= $sort_by === 'location' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th><a href="?tab=breakdowns&sort=category&order=<?= $sort_by === 'category' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Category <?= $sort_by === 'category' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Breakdown Details</th>
                <th>Parts Cost (&#8369;)</th>
                <th>Labor Cost (&#8369;)</th>
                <th>Other Costs (&#8369;)</th>
                <th><a href="?tab=breakdowns&sort=total_repair_cost&order=<?= $sort_by === 'total_repair_cost' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Total Repair Cost (&#8369;) <?= $sort_by === 'total_repair_cost' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Replacement</th>
                <th><a href="?tab=breakdowns&sort=status&order=<?= $sort_by === 'status' && $sort_order === 'desc' ? 'asc' : 'desc' ?>" class="text-white text-decoration-none">Status <?= $sort_by === 'status' ? ($sort_order === 'asc' ? '↑' : '↓') : '' ?></a></th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): 
                $findings = $r['detailed_findings'] ?? $r['description'] ?? '-';
            ?>
            <tr>
                <td><?= htmlspecialchars($r['date_reported']) ?></td>
                <td><?= ($r['equipment_id'] ? htmlspecialchars($r['equipment_name']) : htmlspecialchars($r['vehicle_name'] ?? '-')) ?> (<?= htmlspecialchars($r['item_id'] ?? $r['plate_number'] ?? '-') ?>)</td>
                <td><?= htmlspecialchars($r['location'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['category']) ?><?= !empty($r['category_other']) ? ' – ' . htmlspecialchars($r['category_other']) : '' ?></td>
                <td><small><?= htmlspecialchars(mb_substr($findings, 0, 80)) ?><?= mb_strlen($findings) > 80 ? '...' : '' ?></small></td>
                <td><?= isset($r['parts_cost']) && $r['parts_cost'] !== null ? number_format((float)$r['parts_cost'], 2) : '-' ?></td>
                <td><?= isset($r['labor_cost']) && $r['labor_cost'] !== null ? number_format((float)$r['labor_cost'], 2) : '-' ?></td>
                <td><?= isset($r['other_costs']) && $r['other_costs'] !== null ? number_format((float)$r['other_costs'], 2) : '-' ?></td>
                <td><strong><?= isset($r['total_repair_cost']) && $r['total_repair_cost'] !== null ? number_format((float)$r['total_repair_cost'], 2) : '-' ?></strong></td>
                <td><?= htmlspecialchars($r['replacement_recommendation'] ?? '-') ?></td>
                <td>
                    <span class="badge <?= $r['status'] === 'Resolved' ? 'badge-resolved' : 'badge-ongoing' ?>"><?= htmlspecialchars($r['status']) ?></span>
                </td>
                <td>
                    <!-- View Report Button - Secondary 3D effect with text -->
                    <a href="breakdown_report.php?id=<?= $r['id'] ?>" 
                       class="btn btn-sm btn-secondary" 
                       target="_blank" 
                       title="View Breakdown Report"
                       style="box-shadow: 0 4px 0 #5a6268; transform: translateY(0); transition: all 0.1s; border: none; text-decoration: none; display: inline-block;"
                       onmousedown="this.style.transform='translateY(2px)'; this.style.boxShadow='0 2px 0 #5a6268'"
                       onmouseup="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #5a6268'"
                       onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 0 #5a6268'">
                        <i class="bi bi-file-text me-1"></i> Report
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?>
            <tr><td colspan="12" class="text-muted">No breakdown records found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
