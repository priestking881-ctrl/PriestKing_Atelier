<?php
$user = currentUser();
$role = $user['role'] ?? '';
$nav = navItemsByRole($role);
$active = $_SERVER['PHP_SELF'];
?>
<aside class="sidebar">
    <a class="brand" href="<?= PRIEST_BASE_URL ?><?= $role === 'administrator' ? '/dashboard.php' : '/modules/customer/index.php' ?>" aria-label="PRIEST Atelier">
        <img class="brand-logo" src="<?= PRIEST_BASE_URL ?>/assets/images/priest-logo.svg" alt="PRIEST monogram">
        <div>
            <strong>PRIEST</strong>
            <small>ATELIER · GHANA</small>
        </div>
    </a>
    <nav class="nav-menu">
        <?php foreach ($nav as $item): ?>
            <?php
            $currentPath = rtrim(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/');
            $itemPath = PRIEST_BASE_URL . $item['url'];
            $isActive = $currentPath === $itemPath
                || ($item['url'] !== '/dashboard.php' && str_starts_with($currentPath, dirname($itemPath) . '/'));
            ?>
            <a class="nav-item <?= $isActive ? 'active' : '' ?>" href="<?= e($itemPath) ?>">
                <i class="fa-solid <?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
