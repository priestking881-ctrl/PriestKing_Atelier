<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_customer') {
    try {
        validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $customerId = trim($_POST['customer_id'] ?? '');
        $name = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($customerId === '' || $name === '') {
            throw new RuntimeException('Customer ID and full name are required.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE customers SET customer_id = :customer_id, full_name = :full_name, gender = :gender, phone = :phone, email = :email, address = :address, notes = :notes WHERE id = :id');
            $stmt->execute(['customer_id' => $customerId, 'full_name' => $name, 'gender' => $gender, 'phone' => $phone, 'email' => $email, 'address' => $address, 'notes' => $notes, 'id' => $id]);
            setFlash('success', 'Customer updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (customer_id, full_name, gender, phone, email, address, notes) VALUES (:customer_id, :full_name, :gender, :phone, :email, :address, :notes)');
            $stmt->execute(['customer_id' => $customerId, 'full_name' => $name, 'gender' => $gender, 'phone' => $phone, 'email' => $email, 'address' => $address, 'notes' => $notes]);
            setFlash('success', 'Customer added successfully.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/customers/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare('DELETE FROM customers WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Customer deleted.');
    redirect('/modules/customers/index.php');
}

$editCustomer = null;
if (!empty($_GET['edit_id'])) {
    $editCustomer = dbQueryOne($pdo, 'SELECT * FROM customers WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
}

$customers = dbQueryAll($pdo, 'SELECT * FROM customers ORDER BY created_at DESC');

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Customer Management</h2>
        <a href="<?= PRIEST_BASE_URL ?>/modules/customers/index.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Customer</a>
    </div>

    <div class="card">
        <div class="section-head">
            <h3><?= $editCustomer ? 'Edit Customer' : 'Add Customer' ?></h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_customer">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($editCustomer): ?>
                <input type="hidden" name="id" value="<?= e($editCustomer['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>
                    <span>Customer ID</span>
                    <input type="text" name="customer_id" value="<?= e($editCustomer['customer_id'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Full Name</span>
                    <input type="text" name="full_name" value="<?= e($editCustomer['full_name'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Gender</span>
                    <select name="gender">
                        <option value="">Select</option>
                        <option value="Male" <?= (($editCustomer['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (($editCustomer['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= (($editCustomer['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </label>
                <label>
                    <span>Phone Number</span>
                    <input type="text" name="phone" value="<?= e($editCustomer['phone'] ?? '') ?>">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($editCustomer['email'] ?? '') ?>">
                </label>
                <label>
                    <span>Address</span>
                    <input type="text" name="address" value="<?= e($editCustomer['address'] ?? '') ?>">
                </label>
                <label style="grid-column: 1 / -1;">
                    <span>Notes</span>
                    <textarea name="notes" rows="4"><?= e($editCustomer['notes'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editCustomer ? 'Update Customer' : 'Save Customer' ?></button>
                <?php if ($editCustomer): ?>
                    <a href="<?= PRIEST_BASE_URL ?>/modules/customers/index.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Customer List</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= e($customer['customer_id']) ?></td>
                        <td><?= e($customer['full_name']) ?></td>
                        <td><?= e($customer['phone']) ?></td>
                        <td><?= e($customer['email']) ?></td>
                        <td><?= e(date('d M Y', strtotime($customer['created_at']))) ?></td>
                        <td>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/customers/index.php?edit_id=<?= (int) $customer['id'] ?>" class="btn btn-outline">Edit</a>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/customers/index.php?action=delete&id=<?= (int) $customer['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
