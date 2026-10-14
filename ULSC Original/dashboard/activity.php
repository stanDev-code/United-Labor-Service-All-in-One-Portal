<?php
/**
 * dashboard activity component
 */
require_once __DIR__ . '/helpers.php';

$activity = getRecentActivity($pdo);
$tools_list = getToolsInventory($pdo);
?>

<ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active animate-tab" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button">
            <i class="bi bi-activity me-2"></i>Recent Maintenance & Breakdowns
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link animate-tab" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button">
            <i class="bi bi-tools me-1"></i>Tools
        </button>
    </li>
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
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity['recent_maintenance'] as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($m['maintenance_type']) ?></td>
                                    <td><?= htmlspecialchars($m['date_performed']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($activity['recent_maintenance'])): ?>
                                <tr>
                                    <td colspan="3" class="text-muted">No records yet</td>
                                </tr>
                                <?php endif; ?>
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
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity['recent_breakdowns'] as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['equipment_name']) ?></td>
                                    <td><?= htmlspecialchars($b['category']) ?></td>
                                    <td>
                                        <span class="badge <?= $b['status'] === 'Resolved' ? 'badge-resolved' : 'badge-ongoing' ?>">
                                            <?= htmlspecialchars($b['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($activity['recent_breakdowns'])): ?>
                                <tr>
                                    <td colspan="3" class="text-muted">No records yet</td>
                                </tr>
                                <?php endif; ?>
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
                <?php if (isAdmin()): ?>
                <a href="tools.php" class="btn btn-primary btn-sm btn-glow">Manage Tools</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tool</th>
                            <th>Qty</th>
                            <th>Condition</th>
                            <th>Assigned To</th>
                            <th>Last Inspection</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tools_list as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['tool_name']) ?></td>
                            <td><?= (int) $t['quantity'] ?></td>
                            <td>
                                <span class="badge <?= match($t['condition_status']) { 
                                    'Good' => 'badge-good', 
                                    'Fair' => 'badge-fair', 
                                    'Poor' => 'badge-poor', 
                                    default => 'badge-replace' 
                                } ?>">
                                    <?= htmlspecialchars($t['condition_status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($t['assigned_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($t['last_inspection_date'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tools_list)): ?>
                        <tr>
                            <td colspan="5" class="text-muted">No tools yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
