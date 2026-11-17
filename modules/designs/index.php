<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_design') {
    try {
        validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $designCode = trim($_POST['design_code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $estimatedPrice = (float) ($_POST['estimated_price'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($designCode === '' || $name === '') {
            throw new RuntimeException('Design code and name are required.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE designs SET design_code = :design_code, name = :name, category = :category, estimated_price = :estimated_price, notes = :notes WHERE id = :id');
            $stmt->execute(['design_code' => $designCode, 'name' => $name, 'category' => $category, 'estimated_price' => $estimatedPrice, 'notes' => $notes, 'id' => $id]);
            setFlash('success', 'Design updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO designs (design_code, name, category, estimated_price, notes) VALUES (:design_code, :name, :category, :estimated_price, :notes)');
            $stmt->execute(['design_code' => $designCode, 'name' => $name, 'category' => $category, 'estimated_price' => $estimatedPrice, 'notes' => $notes]);
            setFlash('success', 'Design created.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/designs/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM designs WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Design deleted.');
    redirect('/modules/designs/index.php');
}

$editDesign = null;
if (!empty($_GET['edit_id'])) {
    $editDesign = dbQueryOne($pdo, 'SELECT * FROM designs WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
}

$designs = dbQueryAll($pdo, 'SELECT * FROM designs ORDER BY created_at DESC');
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Design and Style Management</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3><?= $editDesign ? 'Edit Design' : 'Add Design' ?></h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_design">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($editDesign): ?>
                <input type="hidden" name="id" value="<?= e($editDesign['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>
                    <span>Design Code</span>
                    <input type="text" name="design_code" value="<?= e($editDesign['design_code'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Design Name</span>
                    <input type="text" name="name" value="<?= e($editDesign['name'] ?? '') ?>" required>
                </label>
                <label>
                    <span>Category</span>
                    <select name="category">
                        <?php foreach (['Men\'s wear','Women\'s wear','Children\'s wear','Traditional wear','Suits','Dresses','Shirts','Trousers','Skirts','Wedding outfits','Uniforms','Other custom designs'] as $category): ?>
                            <option value="<?= e($category) ?>" <?= (($editDesign['category'] ?? '') === $category) ? 'selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Estimated Price</span>
                    <input type="number" step="0.01" name="estimated_price" value="<?= e($editDesign['estimated_price'] ?? '0.00') ?>">
                </label>
                <label style="grid-column: 1 / -1;">
                    <span>Notes</span>
                    <textarea name="notes" rows="4"><?= e($editDesign['notes'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editDesign ? 'Update Design' : 'Save Design' ?></button>
                <?php if ($editDesign): ?>
                    <a href="<?= PRIEST_BASE_URL ?>/modules/designs/index.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Design Catalogue</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Estimated Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($designs as $design): ?>
                    <tr>
                        <td><?= e($design['design_code']) ?></td>
                        <td><?= e($design['name']) ?></td>
                        <td><?= e($design['category']) ?></td>
                        <td><?= e(formatCurrency((float) $design['estimated_price'])) ?></td>
                        <td>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/designs/index.php?edit_id=<?= (int) $design['id'] ?>" class="btn btn-outline">Edit</a>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/designs/index.php?action=delete&id=<?= (int) $design['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this design?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
