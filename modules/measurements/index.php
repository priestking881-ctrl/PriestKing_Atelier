<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();
$customers = dbQueryAll($pdo, 'SELECT id, customer_id, full_name FROM customers ORDER BY full_name ASC');

$measurementFields = ['Neck','Shoulder','Chest','Waist','Hip','Sleeve Length','Armhole','Wrist','Shirt Length','Trouser Length','Inseam','Outseam','Thigh','Knee','Ankle','Dress Length','Skirt Length'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_measurement') {
    try {
        validateCsrf();
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $measurementDate = trim($_POST['measurement_date'] ?? date('Y-m-d'));
        $clothingType = trim($_POST['clothing_type'] ?? 'Dress');
        $unit = trim($_POST['unit'] ?? 'cm');
        $notes = trim($_POST['notes'] ?? '');

        if ($customerId <= 0) {
            throw new RuntimeException('Select a customer.');
        }

        $measurementId = (int) ($_POST['measurement_id'] ?? 0);
        if ($measurementId > 0) {
            $pdo->prepare('UPDATE measurements SET measurement_date = :measurement_date, clothing_type = :clothing_type, unit = :unit, notes = :notes WHERE id = :id')->execute([
                'measurement_date' => $measurementDate,
                'clothing_type' => $clothingType,
                'unit' => $unit,
                'notes' => $notes,
                'id' => $measurementId,
            ]);
            $pdo->prepare('DELETE FROM measurement_details WHERE measurement_id = :measurement_id')->execute(['measurement_id' => $measurementId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO measurements (customer_id, measurement_date, clothing_type, unit, notes) VALUES (:customer_id, :measurement_date, :clothing_type, :unit, :notes)');
            $stmt->execute(['customer_id' => $customerId, 'measurement_date' => $measurementDate, 'clothing_type' => $clothingType, 'unit' => $unit, 'notes' => $notes]);
            $measurementId = (int) $pdo->lastInsertId();
        }

        foreach ($measurementFields as $field) {
            $value = $_POST['measure_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($field))] ?? null;
            if ($value !== null && $value !== '') {
                $pdo->prepare('INSERT INTO measurement_details (measurement_id, field_name, value) VALUES (:measurement_id, :field_name, :value)')->execute([
                    'measurement_id' => $measurementId,
                    'field_name' => $field,
                    'value' => $value,
                ]);
            }
        }

        setFlash('success', 'Measurement saved successfully.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/measurements/index.php');
}

if (($_GET['action'] ?? '') === 'delete' && !empty($_GET['id'])) {
    $pdo->prepare('DELETE FROM measurements WHERE id = :id')->execute(['id' => (int) $_GET['id']]);
    setFlash('success', 'Measurement record deleted.');
    redirect('/modules/measurements/index.php');
}

$editMeasurement = null;
if (!empty($_GET['edit_id'])) {
    $editMeasurement = dbQueryOne($pdo, 'SELECT * FROM measurements WHERE id = :id', ['id' => (int) $_GET['edit_id']]);
    if ($editMeasurement) {
        $editMeasurement['fields'] = dbQueryAll($pdo, 'SELECT * FROM measurement_details WHERE measurement_id = :measurement_id', ['measurement_id' => $editMeasurement['id']]);
    }
}

$measurements = dbQueryAll($pdo, 'SELECT m.*, c.full_name FROM measurements m LEFT JOIN customers c ON c.id = m.customer_id ORDER BY m.measurement_date DESC');

include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Measurement Management</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3><?= $editMeasurement ? 'Edit Measurement' : 'Add Measurement' ?></h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_measurement">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <?php if ($editMeasurement): ?>
                <input type="hidden" name="measurement_id" value="<?= e($editMeasurement['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <label>
                    <span>Customer</span>
                    <select name="customer_id" required>
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer['id'] ?>" <?= (($editMeasurement['customer_id'] ?? '') == $customer['id']) ? 'selected' : '' ?>><?= e($customer['full_name']) ?> (<?= e($customer['customer_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Measurement Date</span>
                    <input type="date" name="measurement_date" value="<?= e($editMeasurement['measurement_date'] ?? date('Y-m-d')) ?>" required>
                </label>
                <label>
                    <span>Clothing Type</span>
                    <input type="text" name="clothing_type" value="<?= e($editMeasurement['clothing_type'] ?? 'Dress') ?>" required>
                </label>
                <label>
                    <span>Unit</span>
                    <select name="unit">
                        <option value="cm" <?= (($editMeasurement['unit'] ?? 'cm') === 'cm') ? 'selected' : '' ?>>Centimetres</option>
                        <option value="inch" <?= (($editMeasurement['unit'] ?? 'cm') === 'inch') ? 'selected' : '' ?>>Inches</option>
                    </select>
                </label>
            </div>
            <div class="form-grid">
                <?php foreach ($measurementFields as $field): ?>
                    <?php
                    $fieldName = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($field));
                    $fieldValue = '';
                    if ($editMeasurement && !empty($editMeasurement['fields'])) {
                        foreach ($editMeasurement['fields'] as $entry) {
                            if ($entry['field_name'] === $field) {
                                $fieldValue = $entry['value'];
                                break;
                            }
                        }
                    }
                    ?>
                    <label>
                        <span><?= e($field) ?></span>
                        <input type="number" step="0.01" name="measure_<?= e($fieldName) ?>" value="<?= e($fieldValue) ?>">
                    </label>
                <?php endforeach; ?>
                <label style="grid-column: 1 / -1;">
                    <span>Notes</span>
                    <textarea name="notes" rows="3"><?= e($editMeasurement['notes'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editMeasurement ? 'Update Measurement' : 'Save Measurement' ?></button>
                <?php if ($editMeasurement): ?>
                    <a href="<?= PRIEST_BASE_URL ?>/modules/measurements/index.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>Measurement History</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Clothing</th>
                    <th>Date</th>
                    <th>Unit</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($measurements as $measurement): ?>
                    <tr>
                        <td><?= e($measurement['full_name']) ?></td>
                        <td><?= e($measurement['clothing_type']) ?></td>
                        <td><?= e(date('d M Y', strtotime($measurement['measurement_date']))) ?></td>
                        <td><?= e(strtoupper($measurement['unit'])) ?></td>
                        <td>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/measurements/index.php?edit_id=<?= (int) $measurement['id'] ?>" class="btn btn-outline">Edit</a>
                            <a href="<?= PRIEST_BASE_URL ?>/modules/measurements/index.php?action=delete&id=<?= (int) $measurement['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this measurement record?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
