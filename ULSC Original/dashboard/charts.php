<?php
/**
 * dashboard charts component
 */
require_once __DIR__ . '/helpers.php';

// Get available years for filtering
$available_years = getAvailableYears($pdo);
$current_year = date('Y');
$selected_year = $_GET['year'] ?? $current_year;

$chart_data = getMonthlyChartData($pdo, 6);
$monthly_data = $chart_data['monthly_data'];
$y_axis_labels = generateYAxisLabels($chart_data['max_overall']);
?>

<!-- Chart Filters -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <div class="search-filters-row align-items-center justify-content-center">
            <div class="search-filter-item">
                <label class="search-filter-label">Filter by Year</label>
                <select id="yearFilter" class="modern-select" style="min-width: 150px;">
                    <option value="">All Years</option>
                    <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="search-filter-item">
                <label class="search-filter-label">Months to Display</label>
                <select id="monthRange" class="modern-select" style="min-width: 150px;">
                    <option value="3">Last 3 Months</option>
                    <option value="6" selected>Last 6 Months</option>
                    <option value="12">Last 12 Months</option>
                    <option value="24">Last 24 Months</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 fade-in-up">
    <!-- bar chart -->
    <div class="col-lg-6">
        <div class="card h-100 hover-lift">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                    Monthly Repair Costs
                    <span id="barChartYear" class="badge bg-secondary ms-2"><?= $selected_year ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="barChart" width="400" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- line chart -->
    <div class="col-lg-6">
        <div class="card h-100 hover-lift">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up me-2 text-primary"></i>
                    Monthly Overall Costs
                    <span id="lineChartYear" class="badge bg-secondary ms-2"><?= $selected_year ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="lineChart" width="400" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- chart.js script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart instances
let barChart, lineChart;

// prepare chart data
const chartData = <?= json_encode(array_values($monthly_data)) ?>;
const months = chartData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
});

const repairCosts = chartData.map(item => item.repair_cost);
const maintenanceCosts = chartData.map(item => item.maintenance_cost);

// initialize charts
function initCharts() {
    // bar chart configuration
    const barCtx = document.getElementById('barChart').getContext('2d');
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Repair Costs',
                    data: repairCosts,
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                },
                {
                    label: 'Maintenance Costs',
                    data: maintenanceCosts,
                    backgroundColor: '#059669',
                    borderColor: '#047857',
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [5, 5],
                        color: '#e0e0e0'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // line chart configuration
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    lineChart = new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Repair Costs',
                    data: repairCosts,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#dc2626',
                    tension: 0.1
                },
                {
                    label: 'Maintenance Costs',
                    data: maintenanceCosts,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#059669',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [5, 5],
                        color: '#e0e0e0'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// update charts with new data
function updateCharts(chartData) {
    const months = chartData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
    });
    
    const repairCosts = chartData.map(item => item.repair_cost);
    const maintenanceCosts = chartData.map(item => item.maintenance_cost);
    
    // update bar chart
    barChart.data.labels = months;
    barChart.data.datasets[0].data = repairCosts;
    barChart.data.datasets[1].data = maintenanceCosts;
    barChart.update();
    
    // update line chart
    lineChart.data.labels = months;
    lineChart.data.datasets[0].data = repairCosts;
    lineChart.data.datasets[1].data = maintenanceCosts;
    lineChart.update();
    
    // update year badges
    const year = document.getElementById('yearFilter').value || 'All Years';
    document.getElementById('barChartYear').textContent = year;
    document.getElementById('lineChartYear').textContent = year;
}

// filter charts
async function filterCharts() {
    const year = document.getElementById('yearFilter').value;
    const months = document.getElementById('monthRange').value;
    
    try {
        const response = await fetch(`?ajax=true&action=charts&year=${year}&months=${months}`);
        const chartData = await response.json();
        
        updateCharts(Object.values(chartData.monthly_data));
    } catch (error) {
        console.error('Error filtering charts:', error);
    }
}

// event listeners
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    // auto-filter on change
    document.getElementById('yearFilter').addEventListener('change', filterCharts);
    document.getElementById('monthRange').addEventListener('change', filterCharts);
});
</script>
