<?php
/**
 * Header & Sidebar Navigation
 * Green sidebar + white content theme
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Page title mapping
$page_titles = [
    'index' => 'Dashboard',
    'equipment' => 'Equipment',
    'vehicles' => 'Vehicles',
    'maintenance' => 'Maintenance',
    'breakdowns' => 'Breakdowns & Repairs',
    'tools' => 'Tools Inventory',
    'reports' => 'History & Reports',
    'users' => 'Manage Admins'
];

$current_page_title = $page_titles[$current_page] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Dashboard' ?> | Inventory & Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
    <script>
        // Apply theme early to reduce flash (persists in localStorage) - VISUAL ONLY
        (function() {
            try {
                var key = 'ulsc_theme';
                var t = localStorage.getItem(key);
                if (!t) {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
        
        // Apply sidebar state immediately to prevent flash
        (function() {
            try {
                var sidebarKey = 'ulsc_sidebar_collapsed';
                var isCollapsed = localStorage.getItem(sidebarKey) === '1';
                if (isCollapsed) {
                    document.documentElement.classList.add('sidebar-pre-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar - Green -->
        <div id="sidebar-wrapper">
            <div class="sidebar-brand">
                <h1 class="sidebar-brand-full" style="font-size: 20px; font-weight: bold; color: white !important;">United Labor Service Cooperative</h1>
                <span class="sidebar-brand-collapsed" style="font-size: 24px; font-weight: bold; color: white !important; display: none;">ULSC</span>
                <img class="sidebar-brand-logo" src="United Logo 3.png" alt="United Labor Service Cooperative" style="width: 100px; height: 70x; object-fit: cover; border-radius: 20%;">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="sidebar-nav-item <?= $current_page === 'index' ? 'active' : '' ?>" data-tooltip="Dashboard">
                    <i class="bi bi-speedometer"></i><span>Dashboard</span>
                </a>
                <a href="equipment.php" class="sidebar-nav-item <?= $current_page === 'equipment' ? 'active' : '' ?>" data-tooltip="Equipment">
                    <i class="bi bi-backpack2"></i><span>Equipment</span>
                </a>
                <a href="vehicles.php" class="sidebar-nav-item <?= $current_page === 'vehicles' ? 'active' : '' ?>" data-tooltip="Vehicles">
                    <i class="bi bi-truck"></i><span>Vehicles</span>
                </a>
                <a href="maintenance.php" class="sidebar-nav-item <?= $current_page === 'maintenance' ? 'active' : '' ?>" data-tooltip="Maintenance">
                    <i class="bi bi-gear-wide-connected"></i><span>Maintenance</span>
                </a>
                <a href="breakdowns.php" class="sidebar-nav-item <?= $current_page === 'breakdowns' ? 'active' : '' ?>" data-tooltip="Breakdowns & Repairs">
                    <i class="bi bi-x-octagon"></i><span>Breakdowns & Repairs</span>
                </a>
                <a href="tools.php" class="sidebar-nav-item <?= $current_page === 'tools' ? 'active' : '' ?>" data-tooltip="Tools Inventory">
                    <i class="bi bi-tools"></i><span>Tools Inventory</span>
                </a>
                <a href="reports.php" class="sidebar-nav-item <?= $current_page === 'reports' ? 'active' : '' ?>" data-tooltip="History & Reports">
                    <i class="bi bi-clock-history"></i><span>History & Reports</span>
                </a>
                <?php if (isAdmin()): ?>
                <a href="users.php" class="sidebar-nav-item <?= $current_page === 'users' ? 'active' : '' ?>" data-tooltip="Manage Admins">
                    <i class="bi bi-person-lines-fill"></i><span>Manage Admins</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- User Info Section at Very Bottom -->
            <div class="sidebar-user-info" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px;">
                <div class="text-center mb-3">
                    <div class="text-white small mb-2">
                        <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
                    </div>
                    <div class="badge bg-success mb-3">
                        <?= $_SESSION['role'] ?? '' ?>
                    </div>
                </div>
                
                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-outline-danger btn-sm w-100" style="margin-top: -20px;" data-tooltip="Logout">
                    <i class="bi bi-box-arrow-right"></i><span> Logout</span>
                </a>
            </div>
        </div>
        <!-- Page content - White -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light top-navbar">
                <div class="container-fluid">
                    <button class="btn btn-link text-dark p-2" id="sidebarToggle" type="button" title="Toggle sidebar" style="margin-right: 7px;">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <span class="navbar-brand mb-0 me-auto"><?= htmlspecialchars($current_page_title) ?></span>
                    <img src="United Logo.jpg" alt="United Labor Service Cooperative" style="height: 60px; object-fit: cover;">
                </div>
            </nav>
            <div class="container-fluid p-4">

<!-- Universal Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-3" style="background: rgba(220, 38, 38, 0.1); backdrop-filter: blur(10px);">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="mb-2">Confirm Deletion</h6>
                    <p class="text-muted mb-0">This action cannot be undone. The following item will be permanently deleted:</p>
                </div>
                <div style="background: rgba(0, 0, 0, 0.05); backdrop-filter: blur(10px); border-radius: 8px; padding: 16px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <div class="flex-grow-1">
                            <div class="fw-medium" id="deleteItemName"></div>
                            <div class="small text-muted" id="deleteItemDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i>Delete Item
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteUrl = '';

function showDeleteConfirm(itemName, itemDetails = '', url) {
    document.getElementById('deleteItemName').textContent = itemName;
    document.getElementById('deleteItemDetails').textContent = itemDetails;
    deleteUrl = url;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteUrl) {
        window.location.href = deleteUrl;
    }
});
</script>
