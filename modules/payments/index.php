<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();
$user = currentUser();

$pdo = getDbConnection();

if (($_GET['action'] ?? '') === 'approve' && !empty($_GET['id']) && isStaff()) {
    $paymentId = (int) $_GET['id'];
    $pdo->beginTransaction();
    $payment = dbQueryOne($pdo, 'SELECT * FROM payments WHERE id = :id LIMIT 1', ['id' => $paymentId]);
    if ($payment && $payment['status'] === 'pending') {
        $pdo->prepare('UPDATE payments SET status = :status, approved_by = :approved_by, approved_at = :approved_at WHERE id = :id')->execute([
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
            'id' => $paymentId,
        ]);
        if ($payment['order_id'] > 0) {
            $pdo->prepare('UPDATE orders SET total_paid = total_paid + :amount WHERE id = :id')->execute(['amount' => $payment['amount_paid'], 'id' => $payment['order_id']]);
        }
        setFlash('success', 'Payment approved.');
    } else {
        setFlash('danger', 'Payment not found or already processed.');
    }
    $pdo->commit();
    redirect('/modules/payments/index.php');
}

if (($_GET['action'] ?? '') === 'reject' && !empty($_GET['id']) && isStaff()) {
    $paymentId = (int) $_GET['id'];
    $pdo->prepare('UPDATE payments SET status = :status, approved_by = :approved_by, approved_at = :approved_at WHERE id = :id AND status = :pending')->execute([
        'status' => 'rejected',
        'approved_by' => $user['id'],
        'approved_at' => date('Y-m-d H:i:s'),
        'pending' => 'pending',
        'id' => $paymentId,
    ]);
    setFlash('success', 'Payment rejected.');
    redirect('/modules/payments/index.php');
}

$payments = dbQueryAll($pdo, 'SELECT p.*, c.full_name, o.order_number, u.full_name AS approver_name FROM payments p LEFT JOIN customers c ON c.id = p.customer_id LEFT JOIN orders o ON o.id = p.order_id LEFT JOIN users u ON u.id = p.approved_by ORDER BY p.created_at DESC');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Payment Management</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Customer Payment Submissions</h3>
            <p class="page-subtitle">Customer-submitted payments appear here for review and approval.</p>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Order</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$payments): ?>
                    <tr><td colspan="8">No customer payments have been submitted yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e($payment['full_name']) ?></td>
                        <td><?= e($payment['order_number'] ?? 'General') ?></td>
                        <td><?= e(formatCurrency((float) $payment['amount_paid'])) ?></td>
                        <td><?= e($payment['payment_method']) ?></td>
                        <td><span class="status-badge"><?= e(ucfirst($payment['status'])) ?></span></td>
                        <td><?= e($payment['approver_name'] ?? ($payment['status'] === 'pending' ? '—' : '—')) ?></td>
                        <td><?= e(date('d M Y', strtotime($payment['payment_date']))) ?></td>
                        <td>
                            <?php if ($payment['status'] === 'pending'): ?>
                                <a href="<?= PRIEST_BASE_URL ?>/modules/payments/index.php?action=approve&id=<?= (int) $payment['id'] ?>" class="btn btn-outline" onclick="return confirm('Approve this payment?')">Approve</a>
                                <a href="<?= PRIEST_BASE_URL ?>/modules/payments/index.php?action=reject&id=<?= (int) $payment['id'] ?>" class="btn btn-danger" style="font-size:0.75rem;padding:6px 10px;" onclick="return confirm('Reject this payment?')">Reject</a>
                            <?php else: ?>
                                <span style="font-size:0.75rem;color:var(--muted);">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
