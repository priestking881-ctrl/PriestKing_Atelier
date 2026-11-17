<?php
require_once __DIR__ . '/functions.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$settings = getBusinessSettings();
$flash = getFlash();
$user = currentUser();
$backgroundVideo = 'fashion-runway-red.mp4';
$pageBackgrounds = [
    '/modules/customers/' => 'fashion-shopping-editorial.jpg',
    '/modules/measurements/' => 'couture-wardrobe-editorial.jpg',
    '/modules/designs/' => 'fashion-report-runway.jpg',
    '/modules/orders/' => 'luxury-delivery-rack.jpg',
    '/modules/payments/' => 'payments-service.jpg',
    '/modules/expenses/' => 'couture-workroom.jpg',
    '/modules/employees/' => 'designer-at-work.jpg',
    '/modules/reports/' => 'fashion-campaign-editorial.jpg',
];
$currentPageBackground = 'runway-luxe.jpg';
foreach ($pageBackgrounds as $path => $image) {
    if (str_contains($_SERVER['PHP_SELF'] ?? '', $path)) {
        $currentPageBackground = $image;
        break;
    }
}

// Optional branded backgrounds: drop Gucci/Louis Vuitton images into
// assets/images as gucci-brand.jpg / lv-brand.jpg (or .png/.webp) and they
// will be used automatically for the matching pages.
$brandImages = [
    'gucci' => ['gucci-brand.jpg', 'gucci-brand.png', 'gucci-brand.webp'],
    'lv'    => ['lv-brand.jpg', 'lv-brand.png', 'lv-brand.webp'],
];
$resolveBrand = function (array $candidates): ?string {
    foreach ($candidates as $file) {
        if (is_file(__DIR__ . '/../assets/images/' . $file)) {
            return $file;
        }
    }
    return null;
};
if (str_contains($_SERVER['PHP_SELF'] ?? '', '/modules/designs/') && ($b = $resolveBrand($brandImages['gucci']))) {
    $currentPageBackground = $b;
}
if (str_contains($_SERVER['PHP_SELF'] ?? '', '/modules/settings/') && ($b = $resolveBrand($brandImages['lv']))) {
    $currentPageBackground = $b;
}
// Dashboard / default: prefer a brand image if present.
if ($currentPageBackground === 'runway-luxe.jpg') {
    foreach (array_merge($brandImages['gucci'], $brandImages['lv']) as $file) {
        if (is_file(__DIR__ . '/../assets/images/' . $file)) {
            $currentPageBackground = $file;
            break;
        }
    }
}

// Friendly page title derived from the current script.
$pageTitleMap = [
    '/dashboard.php' => 'Dashboard',
    '/modules/customers/' => 'Customers',
    '/modules/measurements/' => 'Measurements',
    '/modules/designs/' => 'Designs',
    '/modules/orders/' => 'Orders',
    '/modules/payments/' => 'Payments',
    '/modules/expenses/' => 'Expenses',
    '/modules/employees/' => 'Employees',
    '/modules/reports/' => 'Reports',
    '/modules/settings/' => 'Settings',
];
$currentPageTitle = $settings['business_name'] ?? 'PRIEST';
foreach ($pageTitleMap as $path => $title) {
    if (str_contains($_SERVER['PHP_SELF'] ?? '', $path)) {
        $currentPageTitle = $title . ' Â· ' . ($settings['business_name'] ?? 'PRIEST');
        break;
    }
}

// Restrict staff-only areas from customer accounts.
$staffOnlyPaths = [
    '/dashboard.php',
    '/modules/customers/',
    '/modules/measurements/',
    '/modules/designs/',
    '/modules/orders/',
    '/modules/payments/',
    '/modules/expenses/',
    '/modules/employees/',
    '/modules/reports/',
    '/modules/settings/',
];
$currentPath = $_SERVER['PHP_SELF'] ?? '';
$isStaffPath = false;
foreach ($staffOnlyPaths as $staffPath) {
    if (str_contains($currentPath, $staffPath)) {
        $isStaffPath = true;
        break;
    }
}
if ($isStaffPath && (!empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'customer')) {
    redirect('/modules/customer/index.php');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= e($currentPageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= PRIEST_BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('priest-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="has-page-background">
    <?php include __DIR__ . '/background-video.php'; ?>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="main-panel">
            <header class="topbar">
                <div>
                    <h1 class="page-title"><?= e(explode(' Â· ', $currentPageTitle)[0]) ?></h1>
                    <p class="page-subtitle"><?= e($settings['business_name'] ?? 'PRIEST') ?> Â· Atelier Management</p>
                </div>
                <div class="topbar-actions">
                    <button type="button" class="theme-toggle btn btn-outline" aria-label="Toggle dark mode" title="Toggle dark mode">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <span class="user-pill"><i class="fa-solid fa-user"></i> <?= e($user['name'] ?? 'Guest') ?> (<?= e($user['role'] ?? 'User') ?>)</span>
                    <a href="<?= PRIEST_BASE_URL ?>/logout.php" class="btn btn-outline"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

