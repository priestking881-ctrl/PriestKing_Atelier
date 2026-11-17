<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_employee') {
    try {
        validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $employeeId = trim($_POST['employee_id'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'Tailor');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($employeeId === '' || $fullName === '') {
            throw new RuntimeException('Employee ID and name are required.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE employees SET employee_id = :employee_id, full_name = :full_name, role = :role, phone = :phone, email = :email, status = :status WHERE id = :id');
            $stmt->execute(['employee_id' => $employeeId, 'full_name' => $fullName, 'role' => $role, 'phone' => $phone, 'email' => $email, 'status' => $status, 'id' => $id]);
            setFlash('success', 'Employee updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO employees (employee_id, full_name, role, phone, email, status) VALUES (:employee_id, :full_name, :role, :phone, :email, :status)');
            $stmt->execute(['employee_id' => $employeeId, 'full_name' => $fullName, 'role' => $role, 'phone' => $phone, 'email' => $email, 'status' => $status]);
            setFlash('success', 'Employee added.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/employees/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM employees WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Employee deleted.');
    redirect('/modules/employees/index.php');
}

$editEmployee = null;
if (!empty($_GET['edit_id'])) {
    $editEmployee = dbQueryOne($pdo, 'SELECT * FROM employees WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
}

$employees = dbQueryAll($pdo, 'SELECT * FROM employees ORDER BY created_at DESC');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Employee Management</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3><?= $editEmployee ? 'Edit Employee' : 'Register Employee' ?></h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_employee">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($editEmployee): ?>
                <input type="hidden" name="id" value="<?= e($editEmployee['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>
                    <span>Employee ID</span>
                    <input type="text" name="employee_id" value="<?= e($editEmployee['employee_id'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Full Name</span>
                    <input type="text" name="full_name" value="<?= e($editEmployee['full_name'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Role</span>
                    <input type="text" name="role" value="<?= e($editEmployee['role'] ?? 'Tailor') ?>" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="text" name="phone" value="<?= e($editEmployee['phone'] ?? '') ?>">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($editEmployee['email'] ?? '') ?>">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= (($editEmployee['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($editEmployee['status'] ?? 'active') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editEmployee ? 'Update Employee' : 'Save Employee' ?></button>
                <?php if ($editEmployee): ?>
                    <a href="<?= PRIEST_BASE_URL ?>/modules/employees/index.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Employee List</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= e($employee['employee_id']) ?></td>
                        <td><?= e($employee['full_name']) ?></td>
                        <td><?= e($employee['role']) ?></td>
                        <td><?= e($employee['phone']) ?></td>
                        <td><?= e($employee['status']) ?></td>
                        <td>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/employees/index.php?edit_id=<?= (int) $employee['id'] ?>" class="btn btn-outline">Edit</a>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/employees/index.php?action=delete&id=<?= (int) $employee['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this employee?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
