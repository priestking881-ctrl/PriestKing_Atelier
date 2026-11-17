<?php
require_once __DIR__ . '/../../includes/functions.php';
requireCustomerPortalAccess();

$pdo = getDbConnection();
$user = currentUser();
$customer = dbQueryOne($pdo, 'SELECT id FROM customers WHERE user_id = :user_id LIMIT 1', ['user_id' => $user['id']]);
$settings = getBusinessSettings();
$designs = dbQueryAll($pdo, 'SELECT * FROM designs ORDER BY name');
$defaultDesignImages = [
    'Men\'s wear' => '82212479032e005c5eaf14a9335bd9fa.jpg',
    'Suits' => 'OIP.webp',
    'Shirts' => 'OIP.webp',
    'Trousers' => 'OIP.webp',
    'Women\'s wear' => '2fbdf65805d56439247590e46d5f6524.jpg',
    'Dresses' => '2fbdf65805d56439247590e46d5f6524.jpg',
    'Skirts' => '2fbdf65805d56439247590e46d5f6524.jpg',
    'Wedding outfits' => '2fbdf65805d56439247590e46d5f6524.jpg',
];
include __DIR__ . '/../../includes/header.php';
?>
<div class="content-wrap">
    <div class="page-header">
        <h2>Available Designs</h2>
        <p class="page-subtitle">Choose a design to order.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!$customer): ?>
        <div class="alert alert-danger" role="alert">Your account has no linked customer profile. You can browse designs, but ordering requires a customer profile linked by an administrator.</div>
    <?php endif; ?>

    <?php if (!$designs): ?>
        <div class="empty-state">
            <i class="fa-solid fa-shirt"></i>
            <h3>No designs available</h3>
            <p>The atelier has not added any designs yet.</p>
        </div>
    <?php else: ?>
    <div class="form-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
        <?php foreach ($designs as $design): ?>
            <?php
            $imagePath = trim((string) ($design['image_path'] ?? ''));
            if ($imagePath === '') {
                $imagePath = $defaultDesignImages[$design['category']] ?? '';
                if ($design['category'] === 'Traditional wear') {
                    $designText = strtolower($design['name'] . ' ' . ($design['notes'] ?? ''));
                    $imagePath = preg_match('/\b(men|male|agbada|kaftan|smock|dashiki)\b/', $designText)
                        ? 'OIP.webp'
                        : '2fbdf65805d56439247590e46d5f6524.jpg';
                }
            }
            if (preg_match('/\b(?:agbada|abgada|agdada)\b/i', (string) $design['name'])) {
                $imagePath = 'OIP.webp';
            }
            ?>
            <div class="card">
                <div style="border-radius: 12px 12px 0 0; overflow: hidden; height: 300px; background: var(--bg-1); display: grid; place-items: center; color: var(--text-2);">
                    <?php if ($imagePath !== ''): ?>
                        <img src="<?= PRIEST_BASE_URL ?>/assets/images/<?= e($imagePath) ?>" alt="<?= e($design['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;object-position:center top;">
                    <?php else: ?>
                        <i class="fa-solid fa-shirt" style="font-size: 2.5rem; opacity: 0.45;"></i>
                    <?php endif; ?>
                </div>
                <div style="padding: 16px;">
                    <h3 style="margin: 0 0 6px; font-size: 1.15rem; color: var(--text-0);"><?= e($design['name']) ?></h3>
                    <small style="color: var(--text-3);"><?= e($design['design_code']) ?> &middot; <?= e($design['category']) ?></small>
                    <p style="margin: 8px 0 0; color: var(--text-2); font-size: 0.85rem;"><?= e($design['notes'] ?? 'No description available.') ?></p>
                    <div style="margin-top: 12px; font-weight: 700; color: var(--accent-strong);"><?= e(formatCurrency((float) $design['estimated_price'])) ?> <small style="font-weight: 500; color: var(--text-3); font-size: 0.8rem;"> est.</small></div>
                    <?php if ($customer): ?>
                        <a href="<?= PRIEST_BASE_URL ?>/modules/customer/orders.php?design=<?= (int) $design['id'] ?>" class="btn btn-primary w-100" style="margin-top: 12px;">Order This Design</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
