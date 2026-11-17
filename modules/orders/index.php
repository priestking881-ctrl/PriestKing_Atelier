<?php
require_once __DIR__ . '/../../includes/functions.php';
requireStaff();

$pdo = getDbConnection();
$isAdministrator = ($_SESSION['user_role'] ?? '') === 'administrator';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    try {
        validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Pending');
        $statuses = ['Pending', 'Cutting', 'Sewing', 'Fitting', 'Alteration', 'Completed', 'Ready for collection', 'Cancelled'];

        if ($id <= 0 || !in_array($status, $statuses, true)) {
            throw new RuntimeException('Select a valid order status.');
        }

        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
        if ($stmt->rowCount() === 0 && !dbQueryOne($pdo, 'SELECT id FROM orders WHERE id = :id', ['id' => $id])) {
            throw new RuntimeException('Order not found.');
        }
        setFlash('success', 'Order status updated. The customer will see a portal alert when the order is completed or ready for collection.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/orders/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_order') {
    try {
        validateCsrf();
        if (!$isAdministrator) {
            throw new RuntimeException('Only an administrator can edit order details.');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $orderNumber = trim($_POST['order_number'] ?? '');
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $designId = (int) ($_POST['design_id'] ?? 0);
        $fabricInfo = trim($_POST['fabric_info'] ?? '');
        $quantity = (int) ($_POST['quantity'] ?? 1);
        $orderDate = trim($_POST['order_date'] ?? date('Y-m-d'));
        $expectedDate = trim($_POST['expected_completion_date'] ?? '');
        $status = trim($_POST['status'] ?? 'Pending');
        $specialInstructions = trim($_POST['special_instructions'] ?? '');
        $assignedTailor = trim($_POST['assigned_tailor'] ?? '');
        $totalAmount = (float) ($_POST['total_amount'] ?? 0);
        $totalPaid = (float) ($_POST['total_paid'] ?? 0);
        $statuses = ['Pending', 'Cutting', 'Sewing', 'Fitting', 'Alteration', 'Completed', 'Ready for collection', 'Cancelled'];

        if ($id <= 0) {
            throw new RuntimeException('Orders must be placed by a customer.');
        }
        if ($orderNumber === '' || $customerId <= 0 || !in_array($status, $statuses, true) || $quantity < 1 || $totalAmount < 0 || $totalPaid < 0) {
            throw new RuntimeException('Please check the order details and try again.');
        }

        $stmt = $pdo->prepare('UPDATE orders SET order_number = :order_number, customer_id = :customer_id, design_id = :design_id, fabric_info = :fabric_info, quantity = :quantity, order_date = :order_date, expected_completion_date = :expected_completion_date, status = :status, special_instructions = :special_instructions, assigned_tailor = :assigned_tailor, total_amount = :total_amount, total_paid = :total_paid WHERE id = :id');
        $stmt->execute(['order_number' => $orderNumber, 'customer_id' => $customerId, 'design_id' => $designId ?: null, 'fabric_info' => $fabricInfo, 'quantity' => $quantity, 'order_date' => $orderDate, 'expected_completion_date' => $expectedDate ?: null, 'status' => $status, 'special_instructions' => $specialInstructions, 'assigned_tailor' => $assignedTailor, 'total_amount' => $totalAmount, 'total_paid' => $totalPaid, 'id' => $id]);
        setFlash('success', 'Order updated.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/orders/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    if (!$isAdministrator) {
        setFlash('danger', 'Only an administrator can delete orders.');
        redirect('/modules/orders/index.php');
    }
    $pdo->prepare('DELETE FROM orders WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Order deleted.');
    redirect('/modules/orders/index.php');
}

$editOrder = null;
if ($isAdministrator && !empty($_GET['edit_id'])) {
    $editOrder = dbQueryOne($pdo, 'SELECT * FROM orders WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
}

$customers = $isAdministrator ? dbQueryAll($pdo, 'SELECT id, customer_id, full_name FROM customers ORDER BY full_name') : [];
$designs = $isAdministrator ? dbQueryAll($pdo, 'SELECT * FROM designs ORDER BY name') : [];
$orders = dbQueryAll($pdo, 'SELECT o.*, c.full_name, d.name AS design_name FROM orders o LEFT JOIN customers c ON c.id = o.customer_id LEFT JOIN designs d ON d.id = o.design_id ORDER BY o.created_at DESC');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Sewing Order Management</h2>
    </div>

    <?php if ($editOrder): ?>
    <div class="card">
        <div class="section-head">
            <h3>Edit Order Details</h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_order">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= e($editOrder['id']) ?>">
            <div class="form-grid">
                <label>
                    <span>Order Number</span>
                    <input type="text" name="order_number" value="<?= e($editOrder['order_number'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Customer</span>
                    <select name="customer_id" required>
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer['id'] ?>" <?= (($editOrder['customer_id'] ?? '') == $customer['id']) ? 'selected' : '' ?>><?= e($customer['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Design</span>
                    <select name="design_id">
                        <option value="0">Select design</option>
                        <?php foreach ($designs as $design): ?>
                            <option value="<?= (int) $design['id'] ?>" <?= (($editOrder['design_id'] ?? 0) == $design['id']) ? 'selected' : '' ?>><?= e($design['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Fabric Info</span>
                    <input type="text" name="fabric_info" value="<?= e($editOrder['fabric_info'] ?? '') ?>">
                </label>
                <label>
                    <span>Quantity</span>
                    <input type="number" name="quantity" min="1" value="<?= e($editOrder['quantity'] ?? 1) ?>">
                </label>
                <label>
                    <span>Order Date</span>
                    <input type="date" name="order_date" value="<?= e($editOrder['order_date'] ?? date('Y-m-d')) ?>">
                </label>
                <label>
                    <span>Expected Completion</span>
                    <input type="date" name="expected_completion_date" value="<?= e($editOrder['expected_completion_date'] ?? '') ?>">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <?php foreach (['Pending','Cutting','Sewing','Fitting','Alteration','Completed','Ready for collection','Cancelled'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= (($editOrder['status'] ?? 'Pending') === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Assigned Tailor</span>
                    <input type="text" name="assigned_tailor" value="<?= e($editOrder['assigned_tailor'] ?? '') ?>">
                </label>
                <label>
                    <span>Total Amount</span>
                    <input type="number" step="0.01" name="total_amount" value="<?= e($editOrder['total_amount'] ?? 0) ?>">
                </label>
                <label>
                    <span>Total Paid</span>
                    <input type="number" step="0.01" name="total_paid" value="<?= e($editOrder['total_paid'] ?? 0) ?>">
                </label>
                <label style="grid-column: 1 / -1;">
                    <span>Special Instructions</span>
                    <textarea name="special_instructions" rows="3"><?= e($editOrder['special_instructions'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Order</button>
                <a href="<?= PRIEST_BASE_URL ?>/modules/orders/index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-head">
            <h3>Order History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Design</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['full_name']) ?></td>
                        <td><?= e($order['design_name'] ?? 'No design') ?></td>
                        <td>
                            <form method="post" class="form-actions" style="margin:0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                <select name="status" aria-label="Status for order <?= e($order['order_number']) ?>">
                                    <?php foreach (['Pending','Cutting','Sewing','Fitting','Alteration','Completed','Ready for collection','Cancelled'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </td>
                        <td><?= e(formatCurrency((float) $order['total_amount'])) ?></td>
                        <td><?= e(formatCurrency((float) $order['total_paid'])) ?></td>
                        <td>
                            <?php if ($isAdministrator): ?>
                                <a href="<?= PRIEST_BASE_URL ?>/modules/orders/index.php?edit_id=<?= (int) $order['id'] ?>" class="btn btn-outline">Edit</a>
                                <a href="<?= PRIEST_BASE_URL ?>/modules/orders/index.php?action=delete&id=<?= (int) $order['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this order?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>


