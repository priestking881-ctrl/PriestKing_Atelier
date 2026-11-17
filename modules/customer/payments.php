<?php
require_once __DIR__ . '/../../includes/functions.php';
requireCustomerPortalAccess();

$pdo = getDbConnection();
$user = currentUser();
$customer = dbQueryOne($pdo, 'SELECT * FROM customers WHERE user_id = :user_id LIMIT 1', ['user_id' => $user['id']]);
$settings = getBusinessSettings();

if (!$customer) {
    setFlash('danger', 'No customer profile is linked to your account. Contact an administrator to enable payments.');
    redirect('/modules/customer/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'make_payment') {
    try {
        validateCsrf();
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $amount = (float) ($_POST['amount_paid'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Cash');
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $reference = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        if ($orderId > 0) {
            $order = dbQueryOne($pdo, 'SELECT * FROM orders WHERE id = :id AND customer_id = :cid LIMIT 1', ['id' => $orderId, 'cid' => $customer['id']]);
            if (!$order) {
                throw new RuntimeException('Order not found.');
            }
            $outstanding = (float) $order['total_amount'] - (float) $order['total_paid'];
            if ($amount > $outstanding + 0.01) {
                throw new RuntimeException('Amount exceeds outstanding balance of ' . e(formatCurrency($outstanding)) . '.');
            }
        }

        $stmt = $pdo->prepare('INSERT INTO payments (order_id, customer_id, amount_paid, payment_date, payment_method, reference_number, notes, status) VALUES (:order_id, :customer_id, :amount_paid, :payment_date, :payment_method, :reference_number, :notes, :status)');
        $stmt->execute([
            'order_id' => $orderId ?: null,
            'customer_id' => $customer['id'],
            'amount_paid' => $amount,
            'payment_date' => $paymentDate,
            'payment_method' => $method,
            'reference_number' => $reference,
            'notes' => $notes,
            'status' => 'pending',
        ]);

        setFlash('success', 'Payment submitted and is pending admin approval.');
        redirect('/modules/customer/payments.php');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
}

$myOrders = dbQueryAll($pdo, 'SELECT id, order_number, total_amount, total_paid FROM orders WHERE customer_id = :cid ORDER BY created_at DESC', ['cid' => $customer['id']]);
$myPayments = dbQueryAll($pdo, 'SELECT p.*, o.order_number FROM payments p LEFT JOIN orders o ON o.id = p.order_id WHERE p.customer_id = :cid ORDER BY p.created_at DESC', ['cid' => $customer['id']]);
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Make a Payment</h2>
        <p class="page-subtitle">Pay for an order. Payments require admin approval.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="two-col">
        <div class="card">
            <div class="section-head">
                <h3>New Payment</h3>
            </div>
            <form method="post" class="card-form">
                <input type="hidden" name="action" value="make_payment">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="form-grid">
                    <label>
                        <span>Order</span>
                        <select name="order_id">
                            <option value="0">General payment / no order</option>
                            <?php foreach ($myOrders as $order): ?>
                                <option value="<?= (int) $order['id'] ?>">
                                    <?= e($order['order_number']) ?> &mdash; <?= e(formatCurrency((float) $order['total_amount'] - (float) $order['total_paid'])) ?> due
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Amount</span>
                        <input type="number" step="0.01" name="amount_paid" required>
                    </label>
                    <label>
                        <span>Payment Date</span>
                        <input type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required>
                    </label>
                    <label>
                        <span>Payment Method</span>
                        <select name="payment_method">
                            <?php foreach (['Cash','Mobile Money','Bank Transfer','Card','Other'] as $method): ?>
                                <option value="<?= e($method) ?>"><?= e($method) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Reference</span>
                        <input type="text" name="reference_number" placeholder="Transaction or receipt ID">
                    </label>
                    <label style="grid-column: 1 / -1;">
                        <span>Notes</span>
                        <textarea name="notes" rows="3"></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-money-bill-wave"></i> Submit Payment</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="section-head">
                <h3>My Payments</h3>
            </div>
            <?php if (!$myPayments): ?>
                <div class="empty-state" style="padding: 24px;">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <h3 style="margin: 8px 0 0;">No payments yet</h3>
                    <p style="margin: 4px 0 0; color: var(--text-3);">Submit a payment above.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPayments as $payment): ?>
                            <tr>
                                <td><?= e($payment['order_number'] ?? 'General') ?></td>
                                <td><?= e(formatCurrency((float) $payment['amount_paid'])) ?></td>
                                <td><?= e($payment['payment_method']) ?></td>
                                <td><span class="status-badge"><?= e(ucfirst($payment['status'])) ?></span></td>
                                <td><?= e(date('d M Y', strtotime($payment['payment_date']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
