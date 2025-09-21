<?php
// The header file includes the 'check_auth.php' and session_start()
include 'includes/header.php';
?>

<h1 class="mt-4">Dashboard</h1>
<p>Welcome to the admin panel. This is the main dashboard.</p>

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3"><i class="fas fa-film"></i></div>
                        <div>Total Movies</div>
                    </div>
                    <div class="fs-2 fw-bold" id="total-movies">0</div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="manage_entries.php?type=movies">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3"><i class="fas fa-tv"></i></div>
                        <div>Total TV Series</div>
                    </div>
                    <div class="fs-2 fw-bold" id="total-series">0</div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="manage_entries.php?type=series">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3"><i class="fas fa-broadcast-tower"></i></div>
                        <div>Total Live TV</div>
                    </div>
                    <div class="fs-2 fw-bold" id="total-livetv">0</div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="manage_entries.php?type=livetv">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-3"><i class="fas fa-database"></i></div>
                        <div>Total Entries</div>
                    </div>
                    <div class="fs-2 fw-bold" id="total-entries">0</div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="manage_entries.php">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-area me-1"></i>
                Content Statistics
            </div>
            <div class="card-body"><canvas id="myAreaChart" width="100%" height="30"></canvas></div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    // Fetch stats from the API
    fetch('../api/stats.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                document.getElementById('total-movies').textContent = data.total_movies;
                document.getElementById('total-series').textContent = data.total_series;
                document.getElementById('total-livetv').textContent = data.total_livetv;
                document.getElementById('total-entries').textContent = data.total_entries;

                // Render the chart
                renderChart(data.chart.labels, data.chart.data);
            } else {
                console.error('Failed to fetch stats:', result.message);
            }
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });

    // Function to render the chart
    function renderChart(labels, data) {
        const ctx = document.getElementById('myAreaChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Entries Added',
                    data: data,
                    backgroundColor: 'rgba(2,117,216,0.2)',
                    borderColor: 'rgba(2,117,216,1)',
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(2,117,216,1)',
                    pointBorderColor: 'rgba(255,255,255,0.8)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(2,117,216,1)',
                    pointHitRadius: 50,
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>

<?php
// The footer file includes the closing tags for the body and html
include 'includes/footer.php';
?>
