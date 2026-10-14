<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar - Green -->
        <div id="sidebar-wrapper">
            <div class="sidebar-brand">
                <h1 style="font-size: 20px; font-weight: bold; color: white !important;">United Labor Service Cooperative</h1>
                <img src="United Logo 3.png" alt="United Labor Service Cooperative" style="width: 100px; height: 70x; object-fit: cover; border-radius: 20%;">
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="sidebar-nav-item active" data-tooltip="Dashboard">
                    <i class="bi bi-speedometer"></i><span>Dashboard</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Equipment">
                    <i class="bi bi-backpack2"></i><span>Equipment</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Vehicles">
                    <i class="bi bi-truck"></i><span>Vehicles</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Maintenance">
                    <i class="bi bi-gear-wide-connected"></i><span>Maintenance</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Breakdowns & Repairs">
                    <i class="bi bi-x-octagon"></i><span>Breakdowns & Repairs</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Tools Inventory">
                    <i class="bi bi-tools"></i><span>Tools Inventory</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="History & Reports">
                    <i class="bi bi-clock-history"></i><span>History & Reports</span>
                </a>
                <a href="#" class="sidebar-nav-item" data-tooltip="Manage Staff">
                    <i class="bi bi-person-lines-fill"></i><span>Manage Staff</span>
                </a>
            </nav>
            
            <!-- User Info Section at Very Bottom -->
            <div class="sidebar-user-info" style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px;">
                <div class="text-center mb-3">
                    <div class="text-white small mb-2">
                        Test User
                    </div>
                    <div class="badge bg-success mb-3">
                        Administrator
                    </div>
                </div>
                
                <!-- Logout Button -->
                <a href="#" class="btn btn-outline-danger btn-sm w-100" style="margin-top: -20px;" data-tooltip="Logout">
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
                    <span class="navbar-brand mb-0 me-auto">Dashboard</span>
                    <img src="United Logo.jpg" alt="United Labor Service Cooperative" style="height: 60px; object-fit: cover;">
                </div>
            </nav>
            <div class="container-fluid p-4">
                <div class="row">
                    <div class="col-12">
                        <h1>Sidebar Test Page</h1>
                        <p>Use the hamburger menu to test the sidebar collapse functionality.</p>
                        <div class="alert alert-info">
                            <strong>Features to test:</strong>
                            <ul>
                                <li>Sidebar collapses to show only icons</li>
                                <li>Tooltips appear on hover when collapsed</li>
                                <li>Logout button remains visible and functional</li>
                                <li>Page content adjusts properly</li>
                                <li>Responsive behavior on mobile</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // ==============================
    // safe sidebar toggle (fixed)
    // ==============================
    (function () {

        const STORAGE_KEY = 'ulsc_sidebar_collapsed';

        function initSidebar() {
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar-wrapper');
            const pageContent = document.getElementById('page-content-wrapper');
            const overlay = document.getElementById('sidebarOverlay');

            if (!toggle || !sidebar || !pageContent) return; /* avoid crash */

            function applyState(isCollapsed) {
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    pageContent.classList.add('expanded');
                    const icon = toggle.querySelector('i');
                    if (icon) icon.className = 'bi bi-chevron-double-right fs-4';
                    if (overlay) overlay.classList.remove('show');
                } else {
                    sidebar.classList.remove('collapsed');
                    pageContent.classList.remove('expanded');
                    const icon = toggle.querySelector('i');
                    if (icon) icon.className = 'bi bi-list fs-4';
                    if (window.innerWidth < 768 && overlay) {
                        overlay.classList.add('show');
                    }
                }
            }

            function saveState(isCollapsed) {
                try {
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '');
                } catch (e) {}
            }

            /* load saved state */
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved === '1') applyState(true);
            } catch (e) {}

            /* toggle click */
            toggle.addEventListener('click', function () {
                const isCollapsed = sidebar.classList.toggle('collapsed');
                
                // Toggle expanded class on page content
                if (isCollapsed) {
                    pageContent.classList.add('expanded');
                } else {
                    pageContent.classList.remove('expanded');
                }

                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = isCollapsed
                        ? 'bi bi-chevron-double-right fs-4'
                        : 'bi bi-list fs-4';
                }

                if (overlay && window.innerWidth < 768) {
                    overlay.classList.toggle('show', !isCollapsed);
                }

                saveState(isCollapsed);
            });

            /* overlay click */
            if (overlay) {
                overlay.addEventListener('click', function () {
                    sidebar.classList.add('collapsed');
                    pageContent.classList.add('expanded');
                    overlay.classList.remove('show');

                    const icon = toggle.querySelector('i');
                    if (icon) icon.className = 'bi bi-chevron-double-right fs-4';

                    saveState(true);
                });
            }

            /* resize fix */
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768 && overlay) {
                    overlay.classList.remove('show');
                }
            });
        }

        // important fix:
        // use window load instead of DOMContentLoaded
        // to always run after page scripts
        window.addEventListener('load', function () {
            try {
                initSidebar();
            } catch (e) {
                console.error("Sidebar init error:", e);
            }
        });

    })();
    </script>
</body>
</html>
