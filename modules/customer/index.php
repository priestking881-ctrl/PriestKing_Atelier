<?php
require_once __DIR__ . '/../../includes/functions.php';
requireCustomerPortalAccess();

$pdo = getDbConnection();
$user = currentUser();
$customer = dbQueryOne($pdo, 'SELECT * FROM customers WHERE user_id = :user_id LIMIT 1', ['user_id' => $user['id']]);

$orderCount = 0;
$pendingPayment = 0.0;
$recentOrders = [];
$finishedOrders = [];
if ($customer) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = :cid');
    $stmt->execute(['cid' => $customer['id']]);
    $orderCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE customer_id = :cid AND status = 'pending'");
    $stmt->execute(['cid' => $customer['id']]);
    $pendingPayment = (float) $stmt->fetchColumn();
    $recentOrders = dbQueryAll($pdo, 'SELECT o.*, d.name AS design_name FROM orders o LEFT JOIN designs d ON d.id = o.design_id WHERE o.customer_id = :cid ORDER BY o.created_at DESC LIMIT 5', ['cid' => $customer['id']]);
    $finishedOrders = dbQueryAll($pdo, "SELECT order_number, status FROM orders WHERE customer_id = :cid AND status IN ('Completed', 'Ready for collection') ORDER BY updated_at DESC", ['cid' => $customer['id']]);
}

$settings = getBusinessSettings();
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Customer Portal</h2>
        <p class="page-subtitle"><?= e($settings['business_name'] ?? 'PRIEST') ?> &middot; Place orders, choose designs, and pay.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php foreach ($finishedOrders as $finishedOrder): ?>
        <div class="alert alert-success" role="status">
            <strong>Order <?= e($finishedOrder['order_number']) ?> update:</strong>
            <?= $finishedOrder['status'] === 'Completed' ? 'Your order is finished sewing.' : 'Your order is ready for collection.' ?>
        </div>
    <?php endforeach; ?>

    <section class="stats-grid">
        <div class="card stat-card accent-blue">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <span>Customer ID</span>
                <strong><?= $customer ? e($customer['customer_id']) : '—' ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-gold">
            <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <span>My Orders</span>
                <strong><?= e($orderCount) ?></strong>
            </div>
        </div>
        <div class="card stat-card accent-green">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div>
                <span>Pending Payment</span>
                <strong><?= e(formatCurrency($pendingPayment)) ?></strong>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-head">
            <h3>Quick Actions</h3>
        </div>
        <?php if ($customer): ?>
            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <a href="<?= PRIEST_BASE_URL ?>/modules/customer/designs.php" class="btn btn-primary w-100"><i class="fa-solid fa-shirt"></i> Browse Designs</a>
                <a href="<?= PRIEST_BASE_URL ?>/modules/customer/orders.php" class="btn btn-primary w-100"><i class="fa-solid fa-plus"></i> Place a New Order</a>
                <a href="<?= PRIEST_BASE_URL ?>/modules/customer/payments.php" class="btn btn-primary w-100"><i class="fa-solid fa-money-bill-wave"></i> Make a Payment</a>
                <a href="<?= PRIEST_BASE_URL ?>/modules/customer/deliveries.php" class="btn btn-primary w-100"><i class="fa-solid fa-truck-fast"></i> My Deliveries</a>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 24px;">
                <i class="fa-solid fa-user"></i>
                <h3 style="margin: 8px 0 0;">No customer profile linked</h3>
                <p style="margin: 4px 0 0; color: var(--text-3);">You can browse designs, but placing orders and making payments requires a customer profile linked to your account. Contact an administrator.</p>
                <a href="<?= PRIEST_BASE_URL ?>/modules/customer/designs.php" class="btn btn-primary" style="margin-top: 12px;"><i class="fa-solid fa-shirt"></i> Browse Designs</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($customer && $recentOrders): ?>
    <section class="card">
        <div class="section-head">
            <h3>My Recent Orders</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Design</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['design_name'] ?? 'No design selected') ?></td>
                        <td><span class="status-badge"><?= e($order['status']) ?></span></td>
                        <td><?= e(formatCurrency((float) $order['total_amount'])) ?></td>
                        <td><?= e(formatCurrency((float) $order['total_paid'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
