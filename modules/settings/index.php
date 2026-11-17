<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdministrator();

$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    try {
        validateCsrf();
        $businessName = trim($_POST['business_name'] ?? 'PRIEST Atelier');
        $businessAddress = trim($_POST['business_address'] ?? 'Accra, Ghana');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $currency = trim($_POST['currency'] ?? 'GHS');
        $measurementUnit = trim($_POST['measurement_unit'] ?? 'cm');
        $receiptInfo = trim($_POST['receipt_info'] ?? '');

        $stmt = $pdo->prepare('INSERT INTO business_settings (business_name, business_address, contact_phone, contact_email, currency, measurement_unit, receipt_info) VALUES (:business_name, :business_address, :contact_phone, :contact_email, :currency, :measurement_unit, :receipt_info)');
        $stmt->execute(['business_name' => $businessName, 'business_address' => $businessAddress, 'contact_phone' => $contactPhone, 'contact_email' => $contactEmail, 'currency' => $currency, 'measurement_unit' => $measurementUnit, 'receipt_info' => $receiptInfo]);
        setFlash('success', 'System settings updated.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('/modules/settings/index.php');
}

$settings = getBusinessSettings();
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Business Settings</h2>
    </div>

    <div class="card">
        <div class="section-head">
            <h3>System Configuration</h3>
        </div>
        <form method="post" class="card-form">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-grid">
                <label>
                    <span>Business Name</span>
                    <input type="text" name="business_name" value="<?= e($settings['business_name'] ?? 'PRIEST Atelier') ?>" required>
                </label>
                <label>
                    <span>Business Address</span>
                    <input type="text" name="business_address" value="<?= e($settings['business_address'] ?? 'Accra, Ghana') ?>">
                </label>
                <label>
                    <span>Contact Phone</span>
                    <input type="text" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>">
                </label>
                <label>
                    <span>Contact Email</span>
                    <input type="email" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>">
                </label>
                <label>
                    <span>Currency</span>
                    <input type="text" name="currency" value="<?= e($settings['currency'] ?? 'GHS') ?>">
                </label>
                <label>
                    <span>Measurement Unit</span>
                    <select name="measurement_unit">
                        <option value="cm" <?= (($settings['measurement_unit'] ?? 'cm') === 'cm') ? 'selected' : '' ?>>Centimetres</option>
                        <option value="inch" <?= (($settings['measurement_unit'] ?? 'cm') === 'inch') ? 'selected' : '' ?>>Inches</option>
                    </select>
                </label>
                <label style="grid-column: 1 / -1;">
                    <span>Receipt Information</span>
                    <textarea name="receipt_info" rows="4"><?= e($settings['receipt_info'] ?? 'Thank you for choosing PRIEST Atelier.') ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
