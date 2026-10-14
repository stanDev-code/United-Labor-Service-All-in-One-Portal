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
            const html = document.documentElement;

            if (!toggle || !sidebar || !pageContent) return; /* avoid crash */

            function applyState(isCollapsed) {
                if (window.innerWidth < 768) {
                    // Mobile behavior: sidebar is hidden by default
                    if (isCollapsed) {
                        sidebar.classList.remove('show');
                        pageContent.style.marginLeft = '0';
                        const icon = toggle.querySelector('i');
                        if (icon) icon.className = 'bi bi-list fs-4';
                        if (overlay) overlay.classList.remove('show');
                    } else {
                        sidebar.classList.add('show');
                        pageContent.style.marginLeft = '0';
                        const icon = toggle.querySelector('i');
                        if (icon) icon.className = 'bi bi-x fs-4';
                        if (overlay) overlay.classList.add('show');
                    }
                } else {
                    // Desktop behavior: sidebar collapse/expand
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
                        if (overlay) overlay.classList.remove('show');
                    }
                }
            }

            function saveState(isCollapsed) {
                try {
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '');
                } catch (e) {}
            }

            /* Check if pre-collapsed class exists (from early script) */
            const wasPreCollapsed = html.classList.contains('sidebar-pre-collapsed');
            
            // Initialize mobile state
            if (window.innerWidth < 768) {
                sidebar.classList.remove('show');
                pageContent.style.marginLeft = '0';
                const icon = toggle.querySelector('i');
                if (icon) icon.className = 'bi bi-list fs-4';
            } else if (wasPreCollapsed) {
                /* Apply classes to match the pre-collapsed state */
                sidebar.classList.add('collapsed');
                pageContent.classList.add('expanded');
                const icon = toggle.querySelector('i');
                if (icon) icon.className = 'bi bi-chevron-double-right fs-4';
                
                /* Remove pre-collapsed class to re-enable transitions */
                /* Small delay to ensure browser has rendered initial state */
                setTimeout(function() {
                    html.classList.remove('sidebar-pre-collapsed');
                }, 50);
            } else {
                /* Normal load - check saved state */
                try {
                    const saved = localStorage.getItem(STORAGE_KEY);
                    if (saved === '1') applyState(true);
                } catch (e) {}
            }

            /* toggle click */
            toggle.addEventListener('click', function () {
                if (window.innerWidth < 768) {
                    // Mobile toggle: show/hide sidebar
                    const isShowing = sidebar.classList.toggle('show');
                    const icon = toggle.querySelector('i');
                    if (icon) {
                        icon.className = isShowing ? 'bi bi-x fs-4' : 'bi bi-list fs-4';
                    }
                    if (overlay) {
                        overlay.classList.toggle('show', isShowing);
                    }
                    saveState(false); // Don't save mobile state
                } else {
                    // Desktop toggle: collapse/expand sidebar
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

                    if (overlay) {
                        overlay.classList.remove('show');
                    }

                    saveState(isCollapsed);
                }
            });

            /* overlay click */
            if (overlay) {
                overlay.addEventListener('click', function () {
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('show');
                        const icon = toggle.querySelector('i');
                        if (icon) icon.className = 'bi bi-list fs-4';
                    } else {
                        sidebar.classList.add('collapsed');
                        pageContent.classList.add('expanded');
                        const icon = toggle.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-double-right fs-4';
                    }
                    overlay.classList.remove('show');
                    saveState(true);
                });
            }

            /* resize fix */
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    // Switching to desktop mode
                    if (overlay) overlay.classList.remove('show');
                    sidebar.classList.remove('show');
                    pageContent.style.marginLeft = ''; // Reset inline style
                    
                    // Restore desktop state
                    try {
                        const saved = localStorage.getItem(STORAGE_KEY);
                        if (saved === '1') {
                            sidebar.classList.add('collapsed');
                            pageContent.classList.add('expanded');
                            const icon = toggle.querySelector('i');
                            if (icon) icon.className = 'bi bi-chevron-double-right fs-4';
                        } else {
                            sidebar.classList.remove('collapsed');
                            pageContent.classList.remove('expanded');
                            const icon = toggle.querySelector('i');
                            if (icon) icon.className = 'bi bi-list fs-4';
                        }
                    } catch (e) {}
                } else {
                    // Switching to mobile mode
                    sidebar.classList.remove('show');
                    pageContent.style.marginLeft = '0';
                    const icon = toggle.querySelector('i');
                    if (icon) icon.className = 'bi bi-list fs-4';
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

    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>

</body>
</html>