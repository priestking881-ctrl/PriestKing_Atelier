<?php
require_once __DIR__ . '/../../includes/functions.php';
requireCustomerPortalAccess();

$pdo = getDbConnection();
$user = currentUser();
$customer = dbQueryOne($pdo, 'SELECT * FROM customers WHERE user_id = :user_id LIMIT 1', ['user_id' => $user['id']]);
$settings = getBusinessSettings();

if (!$customer) {
    setFlash('danger', 'No customer profile is linked to your account. Contact an administrator to enable deliveries.');
    redirect('/modules/customer/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_delivery') {
    try {
        validateCsrf();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new RuntimeException('Select a delivery to approve.');
        }

        $order = dbQueryOne($pdo, 'SELECT * FROM orders WHERE id = :id AND customer_id = :cid LIMIT 1', ['id' => $orderId, 'cid' => $customer['id']]);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if (!in_array($order['status'], ['Ready for collection', 'Completed'], true)) {
            throw new RuntimeException('This order is not ready for delivery approval.');
        }

        $existing = dbQueryOne($pdo, 'SELECT * FROM deliveries WHERE order_id = :id ORDER BY created_at DESC LIMIT 1', ['id' => $orderId]);

        $pdo->beginTransaction();
        if ($existing) {
            $pdo->prepare('UPDATE deliveries SET status = :status, collection_date = :collection_date, collected_by = :collected_by, delivery_notes = :delivery_notes WHERE id = :id')
                ->execute([
                    'status' => 'Delivered',
                    'collection_date' => date('Y-m-d'),
                    'collected_by' => $customer['full_name'],
                    'delivery_notes' => 'Approved by customer on receipt.',
                    'id' => $existing['id'],
                ]);
        } else {
            $pdo->prepare('INSERT INTO deliveries (order_id, status, collection_date, collected_by, delivery_notes, receipt_number) VALUES (:order_id, :status, :collection_date, :collected_by, :delivery_notes, :receipt_number)')
                ->execute([
                    'order_id' => $orderId,
                    'status' => 'Delivered',
                    'collection_date' => date('Y-m-d'),
                    'collected_by' => $customer['full_name'],
                    'delivery_notes' => 'Approved by customer on receipt.',
                    'receipt_number' => null,
                ]);
        }
        $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute(['status' => 'Delivered', 'id' => $orderId]);
        $pdo->commit();

        setFlash('success', 'Delivery approved. Thank you for confirming receipt.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/customer/deliveries.php');
}

$pendingOrders = dbQueryAll($pdo, "SELECT o.*, d.name AS design_name FROM orders o LEFT JOIN designs d ON d.id = o.design_id WHERE o.customer_id = :cid AND o.status IN ('Ready for collection', 'Completed') AND NOT EXISTS (SELECT 1 FROM deliveries dv WHERE dv.order_id = o.id AND dv.status = 'Delivered') ORDER BY o.updated_at DESC", ['cid' => $customer['id']]);
$deliveries = dbQueryAll($pdo, 'SELECT dv.*, o.order_number, d.name AS design_name FROM deliveries dv JOIN orders o ON o.id = dv.order_id LEFT JOIN designs d ON d.id = o.design_id WHERE o.customer_id = :cid ORDER BY dv.created_at DESC', ['cid' => $customer['id']]);

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>My Deliveries</h2>
        <p class="page-subtitle">Confirm receipt of your orders once they are delivered.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 24px;">
        <div class="section-head">
            <h3>Pending Delivery Approval</h3>
        </div>
        <?php if (!$pendingOrders): ?>
            <div class="empty-state" style="padding: 24px;">
                <i class="fa-solid fa-truck-fast"></i>
                <h3 style="margin: 8px 0 0;">No pending deliveries</h3>
                <p style="margin: 4px 0 0; color: var(--text-3);">Orders ready for collection will appear here for your approval.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Design</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $order): ?>
                        <tr>
                            <td><?= e($order['order_number']) ?></td>
                            <td><?= e($order['design_name'] ?? 'No design') ?></td>
                            <td><span class="status-badge"><?= e($order['status']) ?></span></td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="action" value="approve_delivery">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm that you have received this order?')">Approve Receipt</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Delivery History</h3>
        </div>
        <?php if (!$deliveries): ?>
            <div class="empty-state" style="padding: 24px;">
                <i class="fa-solid fa-clipboard-list"></i>
                <h3 style="margin: 8px 0 0;">No deliveries yet</h3>
                <p style="margin: 4px 0 0; color: var(--text-3);">Approved deliveries will appear here.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Design</th>
                        <th>Status</th>
                        <th>Collected By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td><?= e($delivery['order_number']) ?></td>
                            <td><?= e($delivery['design_name'] ?? 'No design') ?></td>
                            <td><span class="status-badge"><?= e($delivery['status']) ?></span></td>
                            <td><?= e($delivery['collected_by'] ?? '-') ?></td>
                            <td><?= e($delivery['collection_date'] ? date('d M Y', strtotime($delivery['collection_date'])) : '--') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>