<?php
/**
 * printable maintenance history
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// Get parameters
$target_type = $_GET['type'] ?? 'equipment'; // 'equipment' or 'vehicle'
$target_id = (int) ($_GET['id'] ?? 0);
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';

if (!$target_id) {
    die('Invalid target ID');
}

// Validate target type
if (!in_array($target_type, ['equipment', 'vehicle'])) {
    die('Invalid target type');
}

// Get target information
if ($target_type === 'equipment') {
    $target_sql = "SELECT id, name, item_id, purchase_date, status FROM equipment WHERE id = ?";
    $target = $pdo->prepare($target_sql);
    $target->execute([$target_id]);
    $item = $target->fetch();
    
    if (!$item) {
        die('Equipment not found');
    }
    
    $item_name = $item['name'];
    $item_id = $item['item_id'];
    $item_subtitle = "Equipment ID: {$item['item_id']} | Status: {$item['status']} | Purchase Date: " . ($item['purchase_date'] ?? 'N/A');
} else {
    $target_sql = "SELECT id, name, plate_number, vehicle_type, status, purchase_date FROM vehicles WHERE id = ?";
    $target = $pdo->prepare($target_sql);
    $target->execute([$target_id]);
    $item = $target->fetch();
    
    if (!$item) {
        die('Vehicle not found');
    }
    
    $item_name = $item['name'];
    $item_id = $item['plate_number'];
    $item_subtitle = "Plate Number: {$item['plate_number']} | Type: {$item['vehicle_type']} | Status: {$item['status']} | Purchase Date: " . ($item['purchase_date'] ?? 'N/A');
}

// Get maintenance records
$maintenance_sql = "SELECT mr.*, 
                    e.name as equipment_name, e.item_id,
                    v.name as vehicle_name, v.plate_number
                    FROM maintenance_records mr
                    LEFT JOIN equipment e ON e.id = mr.equipment_id
                    LEFT JOIN vehicles v ON v.id = mr.vehicle_id
                    WHERE mr.{$target_type}_id = ?";
$params = [$target_id];

if ($from_date) {
    $maintenance_sql .= " AND mr.date_performed >= ?";
    $params[] = $from_date;
}
if ($to_date) {
    $maintenance_sql .= " AND mr.date_performed <= ?";
    $params[] = $to_date;
}

$maintenance_sql .= " ORDER BY mr.date_performed DESC";

$maintenance_stmt = $pdo->prepare($maintenance_sql);
$maintenance_stmt->execute($params);
$records = $maintenance_stmt->fetchAll();

// Calculate totals
$total_cost = 0;
$total_records = count($records);
foreach ($records as $record) {
    if ($record['total_price'] !== null) {
        $total_cost += (float) $record['total_price'];
    }
}

// Get current user info for report generation
$pdo = getDBConnection();
$user_id = getCurrentUserId();
$current_user = ['full_name' => 'Unknown User'];

if ($user_id) {
    $user_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch();
    if ($user_data) {
        $current_user['full_name'] = $user_data['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance History - <?= htmlspecialchars($item_name) ?></title>
    <style>
        @media print {
            body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { margin-bottom: 20px; }
            .summary { margin-bottom: 20px; padding: 15px; border: 1px solid #000; }
            .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            .text-muted { color: #666; }
        }
        
        @media screen {
            body { font-family: Arial, sans-serif; margin: 20px; }
            .print-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; }
            .print-btn:hover { background: #0056b3; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { margin-bottom: 20px; }
            .summary { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f8f9fa; }
            .summary-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .fw-bold { font-weight: bold; }
            .text-muted { color: #666; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button class="print-btn" onclick="window.close()" style="background: #6c757d;">
            <i class="bi bi-x-circle"></i> Close
        </button>
    </div>

    <div class="header">
        <div class="logo">
            <img src="United Logo.jpg" alt="United Labor Service Cooperative" style="height: 100px; width: 267px;">
        </div>
        <h1>Maintenance History Report</h1>
        <h2><?= htmlspecialchars($item_name) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($item_subtitle) ?></p>
        <p><strong>Report Generated:</strong> <?= date('F j, Y, g:i A') ?></p>
        <p><strong>Generated By:</strong> <?= htmlspecialchars($current_user['full_name']) ?></p>
        <?php if ($from_date || $to_date): ?>
            <p><strong>Date Range:</strong> 
                <?= $from_date ? date('F j, Y', strtotime($from_date)) : 'Beginning' ?> 
                to 
                <?= $to_date ? date('F j, Y', strtotime($to_date)) : 'Present' ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row">
            <span><strong>Total Maintenance Records:</strong></span>
            <span><?= $total_records ?></span>
        </div>
        <div class="summary-row">
            <span><strong>Total Maintenance Cost:</strong></span>
            <span class="fw-bold">₱<?= number_format($total_cost, 2) ?></span>
        </div>
        <div class="summary-row">
            <span><strong>Average Cost per Record:</strong></span>
            <span>₱<?= $total_records > 0 ? number_format($total_cost / $total_records, 2) : '0.00' ?></span>
        </div>
    </div>

    <h3>Maintenance Records</h3>
    <?php if (empty($records)): ?>
        <p>No maintenance records found for the specified criteria.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Performed By</th>
                    <th>Total Cost</th>
                    <th>Next Maintenance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['date_performed']) ?></td>
                        <td><?= htmlspecialchars($record['maintenance_type']) ?></td>
                        <td><?= htmlspecialchars($record['description'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($record['performed_by']) ?></td>
                        <td class="text-right">
                            <?= $record['total_price'] !== null ? '₱' . number_format((float)$record['total_price'], 2) : '-' ?>
                        </td>
                        <td><?= htmlspecialchars($record['next_maintenance_date'] ?? '-') ?></td>
                    </tr>
                    <?php if ($record['price_breakdown']): ?>
                        <tr>
                            <td colspan="6" style="background-color: #f9f9f9; font-size: 11px;">
                                <strong>Price Breakdown:</strong><br>
                                <?= nl2br(htmlspecialchars($record['price_breakdown'])) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($record['notes']): ?>
                        <tr>
                            <td colspan="6" style="background-color: #f9f9f9; font-size: 11px;">
                                <strong>Notes:</strong><br>
                                <?= nl2br(htmlspecialchars($record['notes'])) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">Total Cost:</th>
                    <th class="text-right fw-bold">₱<?= number_format($total_cost, 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <p class="text-muted">End of Report</p>
    </div>
</body>
</html>
