<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

$summary = [
    'revenue' => (float) $pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM payments')->fetchColumn(),
    'expenses' => (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM expenses')->fetchColumn(),
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'delivered' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn(),
];

$payments = dbQueryAll($pdo, 'SELECT p.payment_date, p.amount_paid, c.full_name FROM payments p LEFT JOIN customers c ON c.id = p.customer_id ORDER BY p.payment_date DESC LIMIT 12');
$expenses = dbQueryAll($pdo, 'SELECT expense_date, amount, description FROM expenses ORDER BY expense_date DESC LIMIT 12');

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Reports and Analytics</h2>
    </div>

    <section class="stats-grid">
        <div class="card stat-card accent-gold">
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
                <span>Total Income</span>
                <strong><?= e(formatCurrency((float) $summary['revenue'])) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-red">
            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
            <div>
                <span>Total Expenses</span>
                <strong><?= e(formatCurrency((float) $summary['expenses'])) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-blue">
            <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <span>Total Orders</span>
                <strong><?= e($summary['orders']) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-green">
            <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
            <div>
                <span>Delivered</span>
                <strong><?= e($summary['delivered']) ?></strong>
            </div>
        </div>
    </section>

    <div class="two-col">
        <div class="card">
            <div class="section-head">
                <h3>Recent Payments</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e($payment['full_name'] ?? 'Customer') ?></td>
                            <td><?= e(formatCurrency((float) $payment['amount_paid'])) ?></td>
                            <td><?= e(date('d M Y', strtotime($payment['payment_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="section-head">
                <h3>Expense Report</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Expense</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= e($expense['description']) ?></td>
                            <td><?= e(formatCurrency((float) $expense['amount'])) ?></td>
                            <td><?= e(date('d M Y', strtotime($expense['expense_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
