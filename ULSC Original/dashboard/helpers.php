<?php
/**
 * dashboard helper functions
 */

/**
 * format amount as philippine peso
 */
function peso($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * get dashboard statistics with optimized queries
 */
function getDashboardStats($pdo) {
    $stats = [];
    
    // total repair cost
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_repair_cost), 0) as total 
        FROM breakdowns 
        WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL
    ");
    $stmt->execute();
    $stats['total_repair_cost'] = (float) $stmt->fetch()['total'];
    
    // total maintenance cost
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_price), 0) as total 
        FROM maintenance_records 
        WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL
    ");
    $stmt->execute();
    $stats['total_maintenance_cost'] = (float) $stmt->fetch()['total'];
    
    // total overall cost (calculated in php for safety)
    $stats['total_overall_cost'] = $stats['total_repair_cost'] + $stats['total_maintenance_cost'];
    
    // total vehicles and equipment
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM vehicles) as vehicles,
            (SELECT COUNT(*) FROM equipment) as equipment
    ");
    $stmt->execute();
    $counts = $stmt->fetch();
    $stats['total_vehicles_equipment'] = (int) $counts['vehicles'] + (int) $counts['equipment'];
    $stats['vehicles_count'] = (int) $counts['vehicles'];
    $stats['equipment_count'] = (int) $counts['equipment'];
    
    // overall breakdown count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM breakdowns 
        WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL
    ");
    $stmt->execute();
    $stats['total_breakdowns'] = (int) $stmt->fetch()['total'];
    
    return $stats;
}

/**
 * get monthly data for charts (optimized single query with year filtering)
 */
function getMonthlyChartData($pdo, $months = 12, $year = '') {
    $data = [];
    
    // Build year filter condition
    $year_filter_repair = '';
    $year_filter_maintenance = '';
    if ($year) {
        $year_filter_repair = "AND YEAR(date_reported) = ?";
        $year_filter_maintenance = "AND YEAR(date_performed) = ?";
    }
    
    // get repair costs by month
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(date_reported, '%Y-%m') as month, 
               COALESCE(SUM(total_repair_cost), 0) as repair_cost
        FROM breakdowns 
        WHERE (equipment_id IS NOT NULL OR vehicle_id IS NOT NULL)
        $year_filter_repair
        GROUP BY DATE_FORMAT(date_reported, '%Y-%m')
        ORDER BY month DESC 
        LIMIT ?
    ");
    
    if ($year) {
        $stmt->execute([$year, $months]);
    } else {
        $stmt->execute([$months]);
    }
    $repair_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // get maintenance costs by month (single query)
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(date_performed, '%Y-%m') as month, 
               COALESCE(SUM(total_price), 0) as maintenance_cost
        FROM maintenance_records 
        WHERE (equipment_id IS NOT NULL OR vehicle_id IS NOT NULL)
        $year_filter_maintenance
        GROUP BY DATE_FORMAT(date_performed, '%Y-%m')
        ORDER BY month DESC 
        LIMIT ?
    ");
    
    if ($year) {
        $stmt->execute([$year, $months]);
    } else {
        $stmt->execute([$months]);
    }
    $maintenance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // merge data by month
    $monthly_data = [];
    
    // process repair data
    foreach ($repair_data as $row) {
        $monthly_data[$row['month']] = [
            'month' => $row['month'],
            'repair_cost' => (float) $row['repair_cost'],
            'maintenance_cost' => 0
        ];
    }
    
    // process maintenance data
    foreach ($maintenance_data as $row) {
        if (!isset($monthly_data[$row['month']])) {
            $monthly_data[$row['month']] = [
                'month' => $row['month'],
                'repair_cost' => 0,
                'maintenance_cost' => (float) $row['maintenance_cost']
            ];
        } else {
            $monthly_data[$row['month']]['maintenance_cost'] = (float) $row['maintenance_cost'];
        }
    }
    
    // sort by month in ascending order and limit
    ksort($monthly_data);
    $data['monthly_data'] = array_slice($monthly_data, -$months, $months, true);
    
    // calculate max values for dynamic y-axis
    $max_repair = 0;
    $max_maintenance = 0;
    foreach ($data['monthly_data'] as $month_data) {
        $max_repair = max($max_repair, $month_data['repair_cost']);
        $max_maintenance = max($max_maintenance, $month_data['maintenance_cost']);
    }
    
    $data['max_repair'] = $max_repair ?: 1;
    $data['max_maintenance'] = $max_maintenance ?: 1;
    $data['max_overall'] = max($max_repair, $max_maintenance) ?: 1;
    
    return $data;
}

/**
 * get recent activity
 */
function getRecentActivity($pdo, $limit = 5) {
    $activity = [];
    
    // recent maintenance
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               COALESCE(e.name, v.name) as equipment_name, 
               COALESCE(e.item_id, v.plate_number) as item_id
        FROM maintenance_records mr 
        LEFT JOIN equipment e ON e.id = mr.equipment_id 
        LEFT JOIN vehicles v ON v.id = mr.vehicle_id 
        WHERE mr.equipment_id IS NOT NULL OR mr.vehicle_id IS NOT NULL 
        ORDER BY mr.date_performed DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $activity['recent_maintenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // recent breakdowns
    $stmt = $pdo->prepare("
        SELECT b.*, 
               COALESCE(e.name, v.name) as equipment_name, 
               COALESCE(e.item_id, v.plate_number) as item_id
        FROM breakdowns b 
        LEFT JOIN equipment e ON e.id = b.equipment_id 
        LEFT JOIN vehicles v ON v.id = b.vehicle_id 
        WHERE b.equipment_id IS NOT NULL OR b.vehicle_id IS NOT NULL 
        ORDER BY b.date_reported DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $activity['recent_breakdowns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $activity;
}

/**
 * get tools inventory
 */
function getToolsInventory($pdo, $limit = 20) {
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM tools t
        ORDER BY t.tool_name
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * generate dynamic y-axis labels
 */
function generateYAxisLabels($max_value, $steps = 4) {
    $labels = [];
    for ($i = $steps; $i >= 0; $i--) {
        $value = round(($max_value / $steps) * $i);
        $labels[] = number_format($value);
    }
    return $labels;
}

/**
 * get available years for filtering
 */
function getAvailableYears($pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT YEAR(date_reported) as year 
        FROM breakdowns 
        WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL
        UNION
        SELECT DISTINCT YEAR(date_performed) as year 
        FROM maintenance_records 
        WHERE equipment_id IS NOT NULL OR vehicle_id IS NOT NULL
        ORDER BY year DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
