<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();
$deliveries = dbQueryAll(
    $pdo,
    'SELECT dv.*, o.order_number, c.full_name AS customer_name, d.name AS design_name
     FROM deliveries dv
     JOIN orders o ON o.id = dv.order_id
     JOIN customers c ON c.id = o.customer_id
     LEFT JOIN designs d ON d.id = o.design_id
     ORDER BY dv.created_at DESC'
);

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Delivery Management</h2>
        <p class="page-subtitle">Customer-approved deliveries and receipt details.</p>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Delivery History</h3>
        </div>
        <?php if (!$deliveries): ?>
            <div class="empty-state" style="padding: 24px;">
                <i class="fa-solid fa-truck-fast"></i>
                <h3 style="margin: 8px 0 0;">No deliveries yet</h3>
                <p style="margin: 4px 0 0; color: var(--text-3);">Customer-approved deliveries will appear here.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Design</th>
                        <th>Status</th>
                        <th>Collected By</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td><?= e($delivery['order_number']) ?></td>
                            <td><?= e($delivery['customer_name']) ?></td>
                            <td><?= e($delivery['design_name'] ?? 'No design') ?></td>
                            <td><span class="status-badge"><?= e($delivery['status']) ?></span></td>
                            <td><?= e($delivery['collected_by'] ?? '—') ?></td>
                            <td><?= e($delivery['collection_date'] ? date('d M Y', strtotime($delivery['collection_date'])) : '—') ?></td>
                            <td><?= e($delivery['delivery_notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
