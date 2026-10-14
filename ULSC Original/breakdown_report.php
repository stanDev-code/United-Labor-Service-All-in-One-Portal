<?php
/*print form of breakdown report*/
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: breakdowns.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT b.*, e.name as equipment_name, e.item_id, v.name as vehicle_name, v.plate_number as vehicle_plate FROM breakdowns b LEFT JOIN equipment e ON e.id = b.equipment_id LEFT JOIN vehicles v ON v.id = b.vehicle_id WHERE b.id = ? AND (b.equipment_id IS NOT NULL OR b.vehicle_id IS NOT NULL)");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    header('Location: breakdowns.php');
    exit;
}
$r['equipment_name'] = $r['equipment_name'] ?? $r['vehicle_name'] ?? '-';
$r['item_id'] = $r['item_id'] ?? $r['vehicle_plate'] ?? '-';

$page_title = 'Breakdown Report #' . $id;
require_once __DIR__ . '/includes/header.php';
?>

<style>
@media print {
    /*hide header and sidebar when printing*/
    .no-print,
    #sidebar-wrapper,
    .sidebar-overlay,
    .top-navbar {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        font-size: 8px !important;
        line-height: 1.1 !important;
    }
    #page-content-wrapper,
    .container-fluid.p-4 {
        margin: 0 !important;
        padding: 5px !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }

    .report-form,
    .report-form * {
        color: #000000 !important;
        background-color: transparent !important;
    }
    .report-form {
        background-color: #ffffff !important;
    }
    
    /*print styles*/
    .report-form { max-width: 100% !important; margin: 0 !important; }
    .report-header { margin-bottom: 12px !important; text-align: center !important; }
    .report-header img { height: 100px !important; width: 267px !important; margin-bottom: -10px !important; margin-top: -10px !important; }
    .report-header p { font-size: 10px !important; margin: 0 !important; text-align: center !important; }
    .report-header h5 { font-size: 12px !important; margin: 4px 0 0 0 !important; text-align: center !important; }
    .report-section { margin-bottom: 10px !important; }
    .report-section h6 { font-size: 11px !important; margin-bottom: 6px !important; padding-bottom: 4px !important; text-align: left !important; }
    .report-field { min-height: 18px !important; padding: 4px 5px !important; font-size: 10px !important; text-align: left !important; vertical-align: top !important; }
    .report-field.full { min-height: 28px !important; }
    .table-report td { font-size: 10px !important; padding: 4px 5px !important; text-align: left !important; vertical-align: top !important; }
}
.report-form { max-width: 900px; margin: 0 auto; }
.report-header { text-align: center; margin-bottom: 1.5rem; }
.report-section { margin-bottom: 1rem; }
.report-section h6 { border-bottom: 1px solid #333; padding-bottom: 4px; margin-bottom: 8px; font-size: 13px; }
.report-field { border-bottom: 1px solid #ccc; min-height: 1.5em; padding: 2px 4px; }
.report-field.full { min-height: 80px; }
.table-report td { vertical-align: top; padding: 4px 8px; }

/*compact screen styles*/
.report-form { max-width: 900px; margin: 0 auto; font-size: 18px; }
.report-header { text-align: center; margin-bottom: 24px; }
.report-header img { height: 180px; width: 480px; margin-bottom: -15px; margin-top: -15px; }
.report-header p { font-size: 16px; margin: 0; text-align: center; }
.report-header h5 { font-size: 18px; margin: 14px 0 0 0; text-align: center; }
.report-section { margin-bottom: 22px; }
.report-section h6 { font-size: 18px; margin-bottom: 18px; padding-bottom: 12px; text-align: left; }
.report-field { min-height: 30px; padding: 9px 11px; font-size: 17px; text-align: left; vertical-align: top; }
.report-field.full { min-height: 60px; }
.table-report td { font-size: 17px; padding: 9px 12px; text-align: left; vertical-align: top; }
</style>

<div class="no-print mb-3">
    <a href="breakdowns.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Report</button>
</div>

<div class="report-form border p-2 bg-white">
    <div class="report-header">
        <img src="United Logo.jpg" alt="United Labor Service Cooperative" style="height: 100px; width: 267px; margin-bottom: -10px; margin-top: -10px;">
        <p class="mb-0" style="font-size: 10px; text-align: center;">TECHNICAL OFFICE 021 BRGY. LUNA, TUY, BATANGAS (043)4037769</p>
        <h5 class="mb-0" style="font-size: 12px; text-align: center; margin-top: 4px;">EQUIPMENT BREAKDOWN REPORT FORM</h5>
    </div>

    <div class="row report-section">
        <div class="col-6">
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Date:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['date_reported']) ?></div>
            </div>
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Equipment No./Plate No.:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['vehicle_plate'] ?? $r['item_id'] ?? '-') ?></div>
            </div>
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Report by:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['report_by_name'] ?? '-') ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Equipment type/model:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['equipment_name']) ?></div>
            </div>
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Location:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['location'] ?? '-') ?></div>
            </div>
            <div class="row mb-1">
                <div class="col-5" style="font-size: 10px; text-align: left;"><strong>Checked by:</strong></div>
                <div class="col-7" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['checked_by'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <h6 style="font-size: 11px; text-align: left;">1. Actual Cause of Breakdown</h6>
    <div class="report-section">
        <p class="mb-1" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['category']) ?><?= $r['category_other'] ? ' – ' . htmlspecialchars($r['category_other']) : '' ?></p>
        <p class="mb-0" style="font-size: 10px; text-align: left;"><strong>Detailed Findings:</strong></p>
        <div class="report-field full" style="font-size: 10px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($r['detailed_findings'] ?? $r['description'] ?? '-')) ?></div>
    </div>

    <h6 style="font-size: 11px; text-align: left;">2. Total Cost of Repair</h6>
    <table class="table table-sm table-report w-100">
        <tr><td width="30%" style="font-size: 10px; text-align: left;"><strong>Parts/Materials:</strong></td><td style="font-size: 10px; text-align: right; padding-right: 5px;">P <?= number_format((float)($r['parts_cost'] ?? 0), 2) ?></td></tr>
        <tr><td style="font-size: 10px; text-align: left;"><strong>Labor:</strong></td><td style="font-size: 10px; text-align: right; padding-right: 5px;">P <?= number_format((float)($r['labor_cost'] ?? 0), 2) ?></td></tr>
        <tr><td style="font-size: 10px; text-align: left;"><strong>Other Costs:</strong></td><td style="font-size: 10px; text-align: right; padding-right: 5px;">P <?= number_format((float)($r['other_costs'] ?? 0), 2) ?></td></tr>
        <tr><td style="font-size: 10px; text-align: left;"><strong>Total Repair Cost:</strong></td><td style="font-size: 10px; text-align: right; padding-right: 5px;"><strong>P <?= number_format((float)($r['total_repair_cost'] ?? 0), 2) ?></strong></td></tr>
    </table>

    <h6 style="font-size: 11px; text-align: left;">3. Recommendation for Replacement</h6>
    <div class="report-section">
        <p class="mb-1" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['replacement_recommendation'] ?? '-') ?></p>
        <p class="mb-0" style="font-size: 10px; text-align: left;"><strong>Remarks/Justification:</strong></p>
        <div class="report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($r['replacement_remarks'] ?? '-')) ?></div>
    </div>

    <h6 style="font-size: 11px; text-align: left;">4. Usage Reminders / Preventive Tips</h6>
    <div class="report-field full mb-2" style="font-size: 10px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($r['usage_reminders'] ?? '-')) ?></div>

    <h6 style="font-size: 11px; text-align: left;">5. Recommendation if Misuse is Confirmed</h6>
    <div class="report-section">
        <p class="mb-1" style="font-size: 10px; text-align: left;"><?= htmlspecialchars($r['misuse_recommendations'] ?? '-') ?></p>
        <p class="mb-0" style="font-size: 10px; text-align: left;"><strong>Remarks:</strong></p>
        <div class="report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($r['misuse_remarks'] ?? '-')) ?></div>
    </div>

    <p class="mb-1" style="font-size: 10px; text-align: left;"><strong>Repair Action Taken:</strong></p>
    <div class="report-field full mb-2" style="font-size: 10px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($r['repair_action'] ?? '-')) ?></div>

    <div class="row report-section">
        <div class="col-3" style="font-size: 10px; text-align: left;"><strong>Prepared by:</strong></div>
        <div class="col-3 report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= htmlspecialchars($r['prepared_by'] ?? '-') ?></div>
        <div class="col-2" style="font-size: 10px; text-align: left;"><strong>Position:</strong></div>
        <div class="col-4 report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= htmlspecialchars($r['prepared_position'] ?? '-') ?></div>
        <div class="col-3" style="font-size: 10px; text-align: left;"><strong>Date:</strong></div>
        <div class="col-3 report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= date('Y-m-d') ?></div>
        <div class="col-2" style="font-size: 10px; text-align: left;"><strong>Reviewed/Approved by:</strong></div>
        <div class="col-4 report-field" style="font-size: 10px; text-align: left; vertical-align: top;"><?= htmlspecialchars($r['approved_by'] ?? '-') ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
