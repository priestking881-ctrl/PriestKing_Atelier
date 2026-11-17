<?php
require_once __DIR__ . '/includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();
$stats = fetchDashboardStats($pdo);
$customers = dbQueryAll($pdo, 'SELECT * FROM customers ORDER BY created_at DESC LIMIT 5');
$payments = dbQueryAll($pdo, 'SELECT p.*, c.full_name FROM payments p LEFT JOIN customers c ON c.id = p.customer_id ORDER BY p.payment_date DESC LIMIT 5');
$orders = dbQueryAll($pdo, 'SELECT o.*, c.full_name FROM orders o LEFT JOIN customers c ON c.id = o.customer_id ORDER BY o.created_at DESC LIMIT 5');
$settings = getBusinessSettings();

$monthStarts = [];
$monthLabels = [];
for ($offset = 5; $offset >= 0; $offset--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify("-{$offset} months");
    $monthStarts[$month->format('Y-m')] = $month->format('Y-m-01');
    $monthLabels[] = $month->format('M Y');
}

$revenueByMonth = array_fill_keys(array_keys($monthStarts), 0.0);
$revenueStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(payment_date, "%Y-%m") AS month_key, SUM(amount_paid) AS revenue
     FROM payments
     WHERE payment_date >= :start_date AND payment_date < :end_date
     GROUP BY DATE_FORMAT(payment_date, "%Y-%m")'
);
$revenueStmt->execute([
    'start_date' => reset($monthStarts),
    'end_date' => (new DateTimeImmutable('first day of next month'))->format('Y-m-d'),
]);
foreach ($revenueStmt->fetchAll() as $row) {
    $revenueByMonth[$row['month_key']] = (float) $row['revenue'];
}

$orderStatuses = ['Pending', 'Cutting', 'Sewing', 'Fitting', 'Alteration', 'Completed', 'Ready for collection', 'Delivered', 'Cancelled'];
$ordersByStatus = array_fill_keys($orderStatuses, 0);
$statusRows = $pdo->query('SELECT status, COUNT(*) AS order_count FROM orders GROUP BY status')->fetchAll();
foreach ($statusRows as $row) {
    if (isset($ordersByStatus[$row['status']])) {
        $ordersByStatus[$row['status']] = (int) $row['order_count'];
    }
}
$sixMonthRevenue = array_sum($revenueByMonth);

include __DIR__ . '/includes/header.php';
?>
<div class="content-wrap">
    <section class="stats-grid">
        <div class="card stat-card accent-blue">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <span>Customers</span>
                <strong><?= e($stats['customers']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-gold">
            <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <span>Total Orders</span>
                <strong><?= e($stats['orders']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-green">
            <div class="stat-icon"><i class="fa-solid fa-spinner"></i></div>
            <div>
                <span>Pending</span>
                <strong><?= e($stats['pending']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-purple">
            <div class="stat-icon"><i class="fa-solid fa-shirt"></i></div>
            <div>
                <span>In Progress</span>
                <strong><?= e($stats['in_progress']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-red">
            <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
            <div>
                <span>Completed</span>
                <strong><?= e($stats['completed']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-navy">
            <div class="stat-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
                <span>Delivered</span>
                <strong><?= e($stats['delivered']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-gold">
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
                <span>Revenue</span>
                <strong><?= e(formatCurrency((float) $stats['revenue'])) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-red">
            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
            <div>
                <span>Outstanding</span>
                <strong><?= e(formatCurrency((float) $stats['outstanding'])) ?></strong>
            </div>
        </div>
    </section>

    <section class="analytics-grid">
        <div class="card chart-card">
            <div class="section-head chart-heading">
                <div>
                    <span class="section-kicker">REVENUE REVIEW</span>
                    <h3>Revenue performance</h3>
                    <p>Payments received over the last six months</p>
                </div>
                <div class="chart-total">
                    <small>6-month total</small>
                    <strong><?= e(formatCurrency($sixMonthRevenue)) ?></strong>
                </div>
            </div>
            <div class="revenue-chart-wrap">
                <canvas id="revenueChart" aria-label="Revenue received each month over the last six months"></canvas>
            </div>
        </div>
        <div class="card chart-card order-status-card">
            <div class="section-head chart-heading">
                <div>
                    <span class="section-kicker">WORKROOM OVERVIEW</span>
                    <h3>Order status</h3>
                    <p>All active and completed orders</p>
                </div>
            </div>
            <div class="order-chart-wrap">
                <canvas id="orderChart" aria-label="Distribution of orders by status"></canvas>
            </div>
            <div id="orderStatusLegend" class="order-status-legend"></div>
        </div>
    </section>

    <section class="two-col">
        <div class="card">
            <div class="section-head">
                <h3>Recent Registrations</h3>
            </div>
            <div class="list-box">
                <?php foreach ($customers as $customer): ?>
                    <div class="list-row">
                        <div>
                            <strong><?= e($customer['full_name']) ?></strong>
                            <small><?= e($customer['email']) ?></small>
                        </div>
                        <span><?= e(date('d M Y', strtotime($customer['created_at']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <div class="section-head">
                <h3>Recent Transactions</h3>
            </div>
            <div class="list-box">
                <?php foreach ($payments as $payment): ?>
                    <div class="list-row">
                        <div>
                            <strong><?= e($payment['full_name'] ?? 'Customer') ?></strong>
                            <small><?= e($payment['payment_method']) ?></small>
                        </div>
                        <span><?= e(formatCurrency((float) $payment['amount_paid'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-head">
            <h3>Recent Orders</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['full_name'] ?? 'Guest') ?></td>
                        <td><span class="status-badge"><?= e($order['status']) ?></span></td>
                        <td><?= e(formatCurrency((float) $order['total_amount'])) ?></td>
                        <td><?= e(formatCurrency((float) max(0, (float) $order['total_amount'] - (float) $order['total_paid']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
    const revenueCurrency = <?= json_encode($settings['currency'] ?? 'GHS', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const revenueLabels = <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const revenueValues = <?= json_encode(array_values($revenueByMonth), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const orderStatusLabels = <?= json_encode(array_keys($ordersByStatus), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const orderStatusValues = <?= json_encode(array_values($ordersByStatus), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const orderStatusColors = ['#d3ad55', '#30445f', '#51708a', '#8a6a44', '#bd835f', '#4b8275', '#77936f', '#1d675c', '#9d7773'];

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Payments received',
                data: revenueValues,
                borderColor: '#bd9a4a',
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return 'rgba(189,154,74,0.12)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(189,154,74,0.28)');
                    gradient.addColorStop(1, 'rgba(189,154,74,0.015)');
                    return gradient;
                },
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#bd9a4a',
                pointBorderWidth: 2,
                tension: 0.38,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#172235',
                    padding: 12,
                    callbacks: {
                        label: (context) => ` ${revenueCurrency} ${Number(context.parsed.y).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#78818e', font: { family: 'Inter, Arial, sans-serif', size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false, dash: [4, 4] },
                    grid: { color: 'rgba(30,41,59,0.07)' },
                    ticks: {
                        color: '#78818e',
                        padding: 10,
                        callback: (value) => `${revenueCurrency} ${Number(value).toLocaleString()}`
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('orderChart'), {
        type: 'doughnut',
        data: {
            labels: orderStatusLabels,
            datasets: [{
                data: orderStatusValues,
                backgroundColor: orderStatusColors,
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverOffset: 7,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#172235',
                    padding: 12,
                    callbacks: {
                        label: (context) => ` ${context.label}: ${context.parsed} ${context.parsed === 1 ? 'order' : 'orders'}`
                    }
                }
            }
        },
        plugins: [{
            id: 'orderTotalCenter',
            afterDraw(chart) {
                const {ctx, chartArea: {left, right, top, bottom}} = chart;
                const total = orderStatusValues.reduce((sum, value) => sum + value, 0);
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#172235';
                ctx.font = '600 30px Inter, Arial, sans-serif';
                ctx.fillText(total.toLocaleString(), centerX, centerY - 5);
                ctx.fillStyle = '#78818e';
                ctx.font = '12px Inter, Arial, sans-serif';
                ctx.fillText('TOTAL ORDERS', centerX, centerY + 19);
                ctx.restore();
            }
        }]
    });

    document.getElementById('orderStatusLegend').innerHTML = orderStatusLabels.map((label, index) => `
        <div class="status-legend-item">
            <span class="status-legend-name"><i style="--legend-color:${orderStatusColors[index]}"></i>${label}</span>
            <strong>${orderStatusValues[index]}</strong>
        </div>
    `).join('');
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
