<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_expense') {
    try {
        validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $category = trim($_POST['category'] ?? 'Other expenses');
        $description = trim($_POST['description'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));
        $notes = trim($_POST['notes'] ?? '');

        if ($description === '' || $amount <= 0) {
            throw new RuntimeException('Description and amount are required.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE expenses SET category = :category, description = :description, amount = :amount, expense_date = :expense_date, notes = :notes WHERE id = :id');
            $stmt->execute(['category' => $category, 'description' => $description, 'amount' => $amount, 'expense_date' => $expenseDate, 'notes' => $notes, 'id' => $id]);
            setFlash('success', 'Expense updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO expenses (category, description, amount, expense_date, notes) VALUES (:category, :description, :amount, :expense_date, :notes)');
            $stmt->execute(['category' => $category, 'description' => $description, 'amount' => $amount, 'expense_date' => $expenseDate, 'notes' => $notes]);
            setFlash('success', 'Expense added.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/expenses/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM expenses WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Expense deleted.');
    redirect('/modules/expenses/index.php');
}

$editExpense = null;
if (!empty($_GET['edit_id'])) {
    $editExpense = dbQueryOne($pdo, 'SELECT * FROM expenses WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
}

$expenses = dbQueryAll($pdo, 'SELECT * FROM expenses ORDER BY expense_date DESC');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Expense Management</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3><?= $editExpense ? 'Edit Expense' : 'Add Expense' ?></h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_expense">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($editExpense): ?>
                <input type="hidden" name="id" value="<?= e($editExpense['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>
                    <span>Category</span>
                    <select name="category">
                        <?php foreach (['Fabric purchases','Sewing materials','Electricity','Rent','Transportation','Staff salaries','Equipment maintenance','Other expenses'] as $category): ?>
                            <option value="<?= e($category) ?>" <?= (($editExpense['category'] ?? 'Other expenses') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description" value="<?= e($editExpense['description'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Amount</span>
                    <input type="number" step="0.01" name="amount" value="<?= e($editExpense['amount'] ?? '0.00') ?>" required>
                </label>
                <label>
                    <span>Expense Date</span>
                    <input type="date" name="expense_date" value="<?= e($editExpense['expense_date'] ?? date('Y-m-d')) ?>" required>
                </label>
                <label style="grid-column: 1 / -1;">
                    <span>Notes</span>
                    <textarea name="notes" rows="3"><?= e($editExpense['notes'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editExpense ? 'Update Expense' : 'Save Expense' ?></button>
                <?php if ($editExpense): ?>
                    <a href="<?= PRIEST_BASE_URL ?>/modules/expenses/index.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Expense Ledger</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= e($expense['category']) ?></td>
                        <td><?= e($expense['description']) ?></td>
                        <td><?= e(formatCurrency((float) $expense['amount'])) ?></td>
                        <td><?= e(date('d M Y', strtotime($expense['expense_date']))) ?></td>
                        <td>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/expenses/index.php?edit_id=<?= (int) $expense['id'] ?>" class="btn btn-outline">Edit</a>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/expenses/index.php?action=delete&id=<?= (int) $expense['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this expense?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
