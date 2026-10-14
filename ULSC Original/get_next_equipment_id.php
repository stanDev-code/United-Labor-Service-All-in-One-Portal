<?php
/**
 * get next equipment ID for auto-generation
 */
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// get the highest existing equipment ID number
$last_id = $pdo->query("SELECT MAX(CAST(item_id AS UNSIGNED)) as max_id FROM equipment")->fetch()['max_id'] ?? 0;

// generate next ID
$next_id = 'EQ' . str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);

// return the next ID
echo $next_id;
?>
