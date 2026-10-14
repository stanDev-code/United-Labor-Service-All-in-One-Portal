<?php
/**
 * AJAX: Oil change history for a vehicle
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$vid = (int) ($_GET['vehicle_id'] ?? 0);
if (!$vid) { echo '<p class="text-muted">No vehicle specified</p>'; exit; }

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM vehicle_oil_changes WHERE vehicle_id = ? ORDER BY change_date DESC");
$stmt->execute([$vid]);
$history = $stmt->fetchAll();

if (empty($history)) {
    echo '<p class="text-muted">No oil change history recorded yet.</p>';
    exit;
}
?>
<table class="table table-sm">
    <thead><tr><th>Change Date</th><th>Next Due</th><th>Notes</th></tr></thead>
    <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['change_date']) ?></td>
            <td><?= htmlspecialchars($h['next_due_date']) ?></td>
            <td><?= htmlspecialchars($h['notes'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
