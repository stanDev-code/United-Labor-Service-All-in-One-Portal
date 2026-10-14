<?php
/**
 * dashboard api for ajax requests
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

// get request parameters
$action = $_GET['action'] ?? '';
$year = $_GET['year'] ?? '';
$months = (int) ($_GET['months'] ?? 6);

try {
    switch ($action) {
        case 'stats':
            echo json_encode(getDashboardStats(getDBConnection()));
            break;
            
        case 'charts':
            $chart_data = getMonthlyChartData(getDBConnection(), $months, $year);
            
            echo json_encode($chart_data);
            break;
            
        case 'activity':
            echo json_encode(getRecentActivity(getDBConnection()));
            break;
            
        case 'tools':
            echo json_encode(getToolsInventory(getDBConnection()));
            break;
            
        case 'export':
            // export data to csv
            $stats = getDashboardStats(getDBConnection());
            $chart_data = getMonthlyChartData(getDBConnection(), $months);
            
            $filename = 'dashboard_export_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // csv header
            fputcsv($output, ['Dashboard Export - ' . date('Y-m-d H:i:s')]);
            fputcsv($output, []);
            
            // statistics
            fputcsv($output, ['Statistics']);
            fputcsv($output, ['Total Repair Cost', $stats['total_repair_cost']]);
            fputcsv($output, ['Total Maintenance Cost', $stats['total_maintenance_cost']]);
            fputcsv($output, ['Total Overall Cost', $stats['total_overall_cost']]);
            fputcsv($output, ['Total Tools', $stats['total_tools']]);
            fputcsv($output, ['Under Repair', $stats['needs_maintenance']]);
            fputcsv($output, []);
            
            // monthly data
            fputcsv($output, ['Monthly Data']);
            fputcsv($output, ['Month', 'Repair Cost', 'Maintenance Cost']);
            
            foreach ($chart_data['monthly_data'] as $month => $data) {
                fputcsv($output, [
                    date('F Y', strtotime($month . '-01')),
                    $data['repair_cost'],
                    $data['maintenance_cost']
                ]);
            }
            
            fclose($output);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
