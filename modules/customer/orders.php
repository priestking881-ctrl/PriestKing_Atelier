<?php
require_once __DIR__ . '/../../includes/functions.php';
requireCustomerPortalAccess();

$pdo = getDbConnection();
$user = currentUser();
$customer = dbQueryOne($pdo, 'SELECT * FROM customers WHERE user_id = :user_id LIMIT 1', ['user_id' => $user['id']]);
$settings = getBusinessSettings();
$designs = dbQueryAll($pdo, 'SELECT * FROM designs ORDER BY name');
$preselect = (int) ($_GET['design'] ?? 0);

if (!$customer) {
    setFlash('danger', 'No customer profile is linked to your account. Contact an administrator to enable ordering.');
    redirect('/modules/customer/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    try {
        validateCsrf();
        $designId = (int) ($_POST['design_id'] ?? 0);
        $fabricInfo = trim($_POST['fabric_info'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $expectedDate = trim($_POST['expected_completion_date'] ?? '');
        $specialInstructions = trim($_POST['special_instructions'] ?? '');

        if ($designId <= 0) {
            throw new RuntimeException('Please select a design.');
        }
        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        $design = dbQueryOne($pdo, 'SELECT estimated_price FROM designs WHERE id = :id LIMIT 1', ['id' => $designId]);
        if (!$design) {
            throw new RuntimeException('Selected design not found.');
        }

        $totalAmount = (float) $design['estimated_price'] * $quantity;
        $orderCountStmt = $pdo->query('SELECT COUNT(*) FROM orders');
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad((int) $orderCountStmt->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare('INSERT INTO orders (order_number, customer_id, design_id, fabric_info, quantity, order_date, expected_completion_date, status, special_instructions, total_amount, total_paid) VALUES (:order_number, :customer_id, :design_id, :fabric_info, :quantity, :order_date, :expected_completion_date, :status, :special_instructions, :total_amount, :total_paid)');
        $stmt->execute([
            'order_number' => $orderNumber,
            'customer_id' => $customer['id'],
            'design_id' => $designId,
            'fabric_info' => $fabricInfo,
            'quantity' => $quantity,
            'order_date' => date('Y-m-d'),
            'expected_completion_date' => $expectedDate ?: null,
            'status' => 'Pending',
            'special_instructions' => $specialInstructions,
            'total_amount' => $totalAmount,
            'total_paid' => 0,
        ]);

        setFlash('success', 'Order ' . $orderNumber . ' placed successfully. It is pending approval.');
        redirect('/modules/customer/orders.php');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
}

$myOrders = dbQueryAll($pdo, 'SELECT o.*, d.name AS design_name FROM orders o LEFT JOIN designs d ON d.id = o.design_id WHERE o.customer_id = :cid ORDER BY o.created_at DESC', ['cid' => $customer['id']]);
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Place an Order</h2>
        <p class="page-subtitle">Select a design and submit your order.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="card">
            <div class="section-head">
                <h3>New Order</h3>
            </div>
            <form method="post" class="card-form">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="form-grid">
                    <label>
                        <span>Design</span>
                        <select name="design_id" required>
                            <option value="">Select a design</option>
                            <?php foreach ($designs as $design): ?>
                                <option value="<?= (int) $design['id'] ?>" <?= ($preselect === (int) $design['id']) ? 'selected' : '' ?>><?= e($design['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Fabric Info</span>
                        <input type="text" name="fabric_info" placeholder="e.g. Ankara, lace, cotton">
                    </label>
                    <label>
                        <span>Quantity</span>
                        <input type="number" name="quantity" min="1" value="1">
                    </label>
                    <label>
                        <span>Expected Completion</span>
                        <input type="date" name="expected_completion_date">
                    </label>
                    <label style="grid-column: 1 / -1;">
                        <span>Special Instructions</span>
                        <textarea name="special_instructions" rows="3" placeholder="Fit notes, alterations, etc."></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane"></i> Place Order</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="section-head">
                <h3>My Orders</h3>
            </div>
            <?php if (!$myOrders): ?>
                <div class="empty-state" style="padding: 24px;">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h3 style="margin: 8px 0 0;">No orders yet</h3>
                    <p style="margin: 4px 0 0; color: var(--text-3);">Place your first order above.</p>
                </div>
            <?php else: ?>
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
                        <?php foreach ($myOrders as $order): ?>
                            <tr>
                                <td><?= e($order['order_number']) ?></td>
                                <td><?= e($order['design_name'] ?? 'No design') ?></td>
                                <td><span class="status-badge"><?= e($order['status']) ?></span></td>
                                <td><?= e(formatCurrency((float) $order['total_amount'])) ?></td>
                                <td><?= e(formatCurrency((float) $order['total_paid'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
