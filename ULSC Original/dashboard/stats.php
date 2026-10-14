<?php
/**
 * dashboard statistics component
 */
require_once __DIR__ . '/helpers.php';

$stats = getDashboardStats($pdo);
?>

<div class="row g-3 mb-4 fade-in-up">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Repair Cost</h6>
                    <h3 class="mb-0 animate-number"><?= peso($stats['total_repair_cost']) ?></h3>
                </div>
                <i class="bi bi-cash-stack text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Overall Cost</h6>
                    <h3 class="mb-0 animate-number"><?= peso($stats['total_overall_cost']) ?></h3>
                </div>
                <i class="bi bi-wallet2 text-info"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Vehicles & Equipment</h6>
                    <h3 class="mb-0 animate-number"><?= $stats['total_vehicles_equipment'] ?></h3>
                </div>
                <i class="bi bi-box-seam text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-summary h-100 pulse-subtle">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Overall Breakdown Count</h6>
                    <h3 class="mb-0 animate-number"><?= $stats['total_breakdowns'] ?></h3>
                </div>
                <i class="bi bi-x-octagon text-danger"></i>
            </div>
        </div>
    </div>
</div>
