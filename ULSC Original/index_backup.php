<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$page_title = 'Dashboard';

$stmt = $pdo->query("SELECT COALESCE(SUM(total_repair_cost), 0) as c FROM breakdowns WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL");
$total_repair_cost = (float) $stmt->fetch()['c'];
$stmt = $pdo->query("SELECT COALESCE(SUM(total_repair_cost), 0) as c FROM breakdowns WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL UNION ALL SELECT COALESCE(SUM(total_price), 0) as c FROM maintenance_records WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL");
$cost_results = $stmt->fetchAll();
$total_overall_cost = (float) $cost_results[0]['c'] + (float) ($cost_results[1]['c'] ?? 0);
$stmt = $pdo->query("SELECT COALESCE(SUM(quantity), 0) as c FROM tools");
$total_tools = (int) $stmt->fetch()['c'];
$stmt = $pdo->query("SELECT (SELECT COUNT(*) FROM equipment WHERE status = 'Under Repair') + (SELECT COUNT(*) FROM vehicles WHERE status = 'Under Repair') as c");
$needs_maintenance = (int) $stmt->fetch()['c'];

$stmt = $pdo->query("SELECT DATE_FORMAT(date_reported, '%Y-%m') as month, COALESCE(SUM(total_repair_cost), 0) as cost FROM breakdowns WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL GROUP BY DATE_FORMAT(date_reported, '%Y-%m') ORDER BY month DESC LIMIT 12");
$monthly_repair_costs = $stmt->fetchAll();
$stmt = $pdo->query("SELECT DATE_FORMAT(date_reported, '%Y-%m') as month, COALESCE(SUM(total_repair_cost), 0) as repair_cost FROM breakdowns WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL GROUP BY DATE_FORMAT(date_reported, '%Y-%m') UNION ALL SELECT DATE_FORMAT(date_performed, '%Y-%m') as month, COALESCE(SUM(total_price), 0) as repair_cost FROM maintenance_records WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL GROUP BY DATE_FORMAT(date_performed, '%Y-%m') ORDER BY month DESC LIMIT 12");
$monthly_overall_costs = $stmt->fetchAll();

$stmt = $pdo->query("SELECT mr.*, COALESCE(e.name, v.name) as equipment_name, COALESCE(e.item_id, v.plate_number) as item_id FROM maintenance_records mr LEFT JOIN equipment e ON e.id = mr.equipment_id LEFT JOIN vehicles v ON v.id = mr.vehicle_id WHERE mr.equipment_id IS NOT NULL OR mr.vehicle_id IS NOT NULL ORDER BY mr.date_performed DESC LIMIT 5");
$recent_maintenance = $stmt->fetchAll();
$stmt = $pdo->query("SELECT b.*, COALESCE(e.name, v.name) as equipment_name, COALESCE(e.item_id, v.plate_number) as item_id FROM breakdowns b LEFT JOIN equipment e ON e.id = b.equipment_id LEFT JOIN vehicles v ON v.id = b.vehicle_id WHERE b.equipment_id IS NOT NULL OR b.vehicle_id IS NOT NULL ORDER BY b.date_reported DESC LIMIT 5");
$recent_breakdowns = $stmt->fetchAll();
$stmt = $pdo->query("SELECT t.*, u.full_name as assigned_name FROM tools t LEFT JOIN users u ON u.id = t.assigned_to_id ORDER BY t.tool_name LIMIT 20");
$tools_list = $stmt->fetchAll();

$bar_data = !empty($monthly_repair_costs) ? $monthly_repair_costs : [['month'=>date('Y-m'), 'cost'=>0]];
$pie_data = !empty($monthly_overall_costs) ? $monthly_overall_costs : [['month'=>date('Y-m'), 'repair_cost'=>0]];

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-warning">You do not have permission to access that page.</div>
<?php endif; ?>

<div class="row g-3 mb-4 fade-in-up">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Total Repair Cost</h6><h3 class="mb-0 animate-number">₱<?= number_format($total_repair_cost, 2) ?></h3></div>
                <i class="bi bi-cash-stack text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Total Overall Cost</h6><h3 class="mb-0 animate-number">₱<?= number_format($total_overall_cost, 2) ?></h3></div>
                <i class="bi bi-wallet2 text-info"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Tools Inventory</h6><h3 class="mb-0 animate-number"><?= $total_tools ?></h3></div>
                <i class="bi bi-wrench text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><h6 class="text-muted mb-1">Under Repair</h6><h3 class="mb-0"><?= $needs_maintenance ?></h3></div>
                <i class="bi bi-wrench text-warning"></i>
            </div>
        </div>
    </div>
</div>

<?php
$bar_labels = [];
$bar_values = [];
$bar_total = 0;
foreach ($bar_data ?? [] as $d) {
    $bar_labels[] = date('M Y', strtotime($d['month'] . '-01'));
    $bar_values[] = (float)($d['cost'] ?? 0);
    $bar_total += (float)($d['cost'] ?? 0);
}
$bar_total = $bar_total ?: 1;

// Process monthly overall costs for line chart
$line_labels = [];
$line_values = [];
foreach ($pie_data ?? [] as $d) {
    $line_labels[] = date('M Y', strtotime($d['month'] . '-01'));
    $line_values[] = (float)($d['repair_cost'] ?? 0);
}
?>
<div class="row g-4 mb-4 fade-in-up">
    <div class="col-lg-6">
        <div class="card h-100 hover-lift">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Monthly Repair Costs</h6></div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-bars-modern">
                        <?php 
                        // Get unique months and prepare data for two bars per month
                        $monthly_data = [];
                        foreach ($bar_data as $d) {
                            $month_key = $d['month'];
                            if (!isset($monthly_data[$month_key])) {
                                $monthly_data[$month_key] = ['repair_cost' => 0, 'maintenance_cost' => 0];
                            }
                            $monthly_data[$month_key]['repair_cost'] = (float)$d['cost'];
                        }
                        
                        // Get maintenance costs for the same months
                        $stmt = $pdo->query("SELECT DATE_FORMAT(date_performed, '%Y-%m') as month, COALESCE(SUM(total_price), 0) as cost FROM maintenance_records WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL GROUP BY DATE_FORMAT(date_performed, '%Y-%m') ORDER BY month DESC LIMIT 12");
                        $maintenance_costs = $stmt->fetchAll();
                        foreach ($maintenance_costs as $m) {
                            $month_key = $m['month'];
                            if (!isset($monthly_data[$month_key])) {
                                $monthly_data[$month_key] = ['repair_cost' => 0, 'maintenance_cost' => 0];
                            }
                            $monthly_data[$month_key]['maintenance_cost'] = (float)$m['cost'];
                        }
                        
                        krsort($monthly_data);
                        $max_value = 0;
                        foreach ($monthly_data as $data) {
                            $max_value = max($max_value, $data['repair_cost'], $data['maintenance_cost']);
                        }
                        $max_value = $max_value ?: 1;
                        
                        $months = array_slice(array_keys($monthly_data), 0, 6, true);
                        ?>
                        
                        <div class="chart-grid">
                            <div class="chart-y-axis">
                                <div class="y-tick">20000</div>
                                <div class="y-tick">10000</div>
                                <div class="y-tick">5000</div>
                                <div class="y-tick">1000</div>
                                <div class="y-tick">0</div>
                            </div>
                            <div class="chart-bars-container">
                                <?php foreach ($months as $i => $month): ?>
                                <div class="chart-month-group">
                                    <div class="chart-bars-pair">
                                        <div class="chart-bar blue" style="height: <?= (($monthly_data[$month]['repair_cost'] / $max_value) * 100) ?>%"></div>
                                        <div class="chart-bar green" style="height: <?= (($monthly_data[$month]['maintenance_cost'] / $max_value) * 100) ?>%"></div>
                                    </div>
                                    <div class="chart-x-label"><?= date('M', strtotime($month . '-01')) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 hover-lift">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly Overall Costs</h6></div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-line-modern">
                        <?php
                        // Prepare line chart data with two lines
                        $line_data = [];
                        foreach ($pie_data as $d) {
                            $month_key = $d['month'];
                            if (!isset($line_data[$month_key])) {
                                $line_data[$month_key] = ['repair' => 0, 'maintenance' => 0];
                            }
                            $line_data[$month_key]['repair'] += (float)$d['repair_cost'];
                        }
                        
                        // Get separate maintenance data
                        $stmt = $pdo->query("SELECT DATE_FORMAT(date_performed, '%Y-%m') as month, COALESCE(SUM(total_price), 0) as cost FROM maintenance_records WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL GROUP BY DATE_FORMAT(date_performed, '%Y-%m') ORDER BY month DESC LIMIT 12");
                        $maintenance_line = $stmt->fetchAll();
                        foreach ($maintenance_line as $m) {
                            $month_key = $m['month'];
                            if (!isset($line_data[$month_key])) {
                                $line_data[$month_key] = ['repair' => 0, 'maintenance' => 0];
                            }
                            $line_data[$month_key]['maintenance'] = (float)$m['cost'];
                        }
                        
                        krsort($line_data);
                        $line_months = array_slice(array_keys($line_data), 0, 6, true);
                        $line_max = 0;
                        foreach ($line_data as $data) {
                            $line_max = max($line_max, $data['repair'], $data['maintenance']);
                        }
                        $line_max = $line_max ?: 1;
                        ?>
                        
                        <div class="chart-grid">
                            <div class="chart-y-axis">
                                <div class="y-tick">20000</div>
                                <div class="y-tick">10000</div>
                                <div class="y-tick">5000</div>
                                <div class="y-tick">1000</div>
                                <div class="y-tick">0</div>
                            </div>
                            <div class="chart-line-container">
                                <svg width="100%" height="200" viewBox="140 0 400 200">
                                    <!-- Grid lines -->
                                    <?php for($i = 0; $i <= 4; $i++): ?>
                                    <line x1="0" y1="<?= $i * 40 ?>" x2="700" y2="<?= $i * 40 ?>" stroke="#eee" stroke-dasharray="2,2"/>
                                    <?php endfor; ?>
                                    
                                    <!-- Red line (repair costs) -->
                                    <polyline
                                        fill="none"
                                        stroke="#dc2626"
                                        stroke-width="2"
                                        points="<?php
                                        $points = [];
                                        foreach ($line_months as $i => $month) {
                                            $x = 50 + ($i * 300 / max(1, count($line_months) - 1));
                                            $y = 160 - (($line_data[$month]['repair'] / $line_max) * 160);
                                            $points[] = "$x,$y";
                                        }
                                        echo implode(' ', $points);
                                        ?>"
                                    />
                                    <?php foreach ($line_months as $i => $month): ?>
                                    <circle cx="<?= 50 + ($i * 300 / max(1, count($line_months) - 1)) ?>" cy="<?= 160 - (($line_data[$month]['repair'] / $line_max) * 160) ?>" r="3" fill="#dc2626"/>
                                    <?php endforeach; ?>
                                    
                                    <!-- Green line (maintenance costs) -->
                                    <polyline
                                        fill="none"
                                        stroke="#059669"
                                        stroke-width="2"
                                        points="<?php
                                        $points = [];
                                        foreach ($line_months as $i => $month) {
                                            $x = 50 + ($i * 300 / max(1, count($line_months) - 1));
                                            $y = 160 - (($line_data[$month]['maintenance'] / $line_max) * 160);
                                            $points[] = "$x,$y";
                                        }
                                        echo implode(' ', $points);
                                        ?>"
                                    />
                                    <?php foreach ($line_months as $i => $month): ?>
                                    <circle cx="<?= 50 + ($i * 300 / max(1, count($line_months) - 1)) ?>" cy="<?= 160 - (($line_data[$month]['maintenance'] / $line_max) * 160) ?>" r="3" fill="#059669"/>
                                    <text x="<?= 50 + ($i * 300 / max(1, count($line_months) - 1)) ?>" y="195" text-anchor="middle" font-size="10"><?= date('M', strtotime($month . '-01')) ?></text>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active animate-tab" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button"><i class="bi bi-activity me-2"></i>Recent Maintenance & Breakdowns</button></li>
    <li class="nav-item"><button class="nav-link animate-tab" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button"><i class="bi bi-tools me-1"></i>Tools</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="activity">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card card-no-hover">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Maintenance</h6>
                        <a href="maintenance.php" class="btn btn-primary btn-sm btn-glow">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Item</th><th>Type</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent_maintenance as $m): ?>
                                <tr><td><?= htmlspecialchars($m['equipment_name']) ?></td><td><?= htmlspecialchars($m['maintenance_type']) ?></td><td><?= htmlspecialchars($m['date_performed']) ?></td></tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_maintenance)): ?><tr><td colspan="3" class="text-muted">No records yet</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-no-hover">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Breakdowns</h6>
                        <a href="breakdowns.php" class="btn btn-primary btn-sm btn-glow">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Item</th><th>Category</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent_breakdowns as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($b['category']) ?></td>
                                    <td><span class="badge <?= $b['status'] === 'Resolved' ? 'badge-resolved' : 'badge-ongoing' ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_breakdowns)): ?><tr><td colspan="3" class="text-muted">No records yet</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="tools">
        <div class="card card-no-hover">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Tools Inventory</h6>
                <?php if (isAdmin()): ?><a href="tools.php" class="btn btn-primary btn-sm btn-glow">Manage Tools</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Tool</th><th>Qty</th><th>Condition</th><th>Assigned To</th><th>Last Inspection</th></tr></thead>
                    <tbody>
                        <?php foreach ($tools_list as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['tool_name']) ?></td>
                            <td><?= (int) $t['quantity'] ?></td>
                            <td><span class="badge <?= match($t['condition_status']) { 'Good' => 'badge-good', 'Fair' => 'badge-fair', 'Poor' => 'badge-poor', default => 'badge-replace' } ?>"><?= htmlspecialchars($t['condition_status']) ?></span></td>
                            <td><?= htmlspecialchars($t['assigned_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($t['last_inspection_date'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tools_list)): ?><tr><td colspan="5" class="text-muted">No tools yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
