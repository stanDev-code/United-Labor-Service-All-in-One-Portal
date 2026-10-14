<?php
/**
 * Optimized Dashboard - MVC Structure
 * Security: All inputs validated, prepared statements used
 * Performance: Single queries, optimized data fetching
 * Structure: Clean separation of concerns
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/dashboard/helpers.php';

$page_title = 'Dashboard';
$pdo = getDBConnection();

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    require_once __DIR__ . '/dashboard/api.php';
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-warning">You do not have permission to access that page.</div>
<?php endif; ?>

<!-- Dashboard Components -->
<?php require_once __DIR__ . '/dashboard/stats.php'; ?>
<?php require_once __DIR__ . '/dashboard/charts.php'; ?>
<?php require_once __DIR__ . '/dashboard/activity.php'; ?>

<!-- Dashboard JavaScript -->
<script>
// Dashboard state management
const dashboard = {
    autoRefreshInterval: null,
    refreshRate: 60000, // 60 seconds
    
    init() {
        // Simplified dashboard - no auto-refresh needed
    },
    
    setupEventListeners() {
        // No filtering controls - simplified dashboard
    },
    
    startAutoRefresh() {
        this.stopAutoRefresh(); // Clear existing interval
        this.autoRefreshInterval = setInterval(() => {
            this.refreshAll();
        }, this.refreshRate);
    },
    
    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
    },
    
    async refreshAll() {
        try {
            // Show loading state
            this.showLoading(true);
            
            // Refresh stats
            await this.refreshStats();
            
            // Refresh charts
            await this.refreshCharts();
            
            // Refresh activity
            await this.refreshActivity();
            
        } catch (error) {
            console.error('Dashboard refresh error:', error);
            this.showError('Failed to refresh dashboard data');
        } finally {
            this.showLoading(false);
        }
    },
    
    async refreshStats() {
        const response = await fetch('?ajax=true&action=stats');
        const stats = await response.json();
        
        // Update stat cards with animation
        this.animateNumber('.animate-number', stats);
    },
    
    async refreshCharts() {
        const year = document.getElementById('yearFilter')?.value || '';
        const months = document.getElementById('monthRange')?.value || '6';
        
        const response = await fetch(`?ajax=true&action=charts&year=${year}&months=${months}`);
        const chartData = await response.json();
        
        // Update Chart.js charts
        this.updateCharts(chartData);
    },
    
    async refreshActivity() {
        const response = await fetch('?ajax=true&action=activity');
        const activity = await response.json();
        
        // Update activity tables (would need more sophisticated DOM manipulation)
        console.log('Activity updated:', activity);
    },
    
    updateCharts(chartData) {
        const data = Object.values(chartData.monthly_data);
        const months = data.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short' });
        });
        
        const repairCosts = data.map(item => item.repair_cost);
        const maintenanceCosts = data.map(item => item.maintenance_cost);
        
        // Update bar chart
        if (window.barChart) {
            window.barChart.data.labels = months;
            window.barChart.data.datasets[0].data = repairCosts;
            window.barChart.data.datasets[1].data = maintenanceCosts;
            window.barChart.update('active');
        }
        
        // Update line chart
        if (window.lineChart) {
            window.lineChart.data.labels = months;
            window.lineChart.data.datasets[0].data = repairCosts;
            window.lineChart.data.datasets[1].data = maintenanceCosts;
            window.lineChart.update('active');
        }
    },
    
    animateNumber(selector, targetValue) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            const startValue = 0;
            const duration = 1000;
            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const currentValue = startValue + (targetValue - startValue) * progress;
                
                // Format based on the element type
                if (element.textContent.includes('₱')) {
                    element.textContent = '₱' + currentValue.toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    element.textContent = Math.floor(currentValue).toLocaleString();
                }
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        });
    },
    
    showLoading(show) {
        const button = document.getElementById('refreshData');
        if (button) {
            if (show) {
                button.innerHTML = '<i class="bi bi-arrow-clockwise me-1 spin"></i> Refreshing...';
                button.disabled = true;
            } else {
                button.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Refresh Now';
                button.disabled = false;
            }
        }
    },
    
    showError(message) {
        // Create or update error notification
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },
    
    exportData() {
        const year = document.getElementById('yearFilter')?.value || '';
        const months = document.getElementById('monthRange')?.value || '6';
        
        window.open(`?ajax=true&action=export&year=${year}&months=${months}`, '_blank');
    }
};

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    dashboard.init();
});

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
