<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect(string $path): void
{
    header('Location: ' . PRIEST_BASE_URL . $path);
    exit;
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('/index.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role && ($_SESSION['user_role'] ?? '') !== 'administrator') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'You do not have permission to access that page.'];
        redirect('/dashboard.php');
    }
}

function requireStaff(): void
{
    requireLogin();
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, ['administrator', 'manager', 'tailor'], true)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Staff access required.'];
        redirect('/modules/customer/index.php');
    }
}

function requireAdministrator(): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'administrator') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Only an administrator can access the administration area.'];
        $role = $_SESSION['user_role'] ?? '';
        redirect(in_array($role, ['customer', 'manager', 'tailor'], true) ? '/modules/customer/index.php' : '/index.php');
    }
}

function requireCustomerPortalAccess(): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'] ?? '', ['customer', 'manager', 'tailor'], true)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Customer area access is required.'];
        redirect('/dashboard.php');
    }
}

function isStaff(): bool
{
    return in_array($_SESSION['user_role'] ?? '', ['administrator', 'manager', 'tailor'], true);
}

function isCustomer(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'customer';
}

function navItemsByRole(string $role): array
{
    $all = [
        ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => '/dashboard.php', 'roles' => ['administrator']],
        ['label' => 'Customers', 'icon' => 'fa-users', 'url' => '/modules/customers/index.php', 'roles' => ['administrator']],
        ['label' => 'Measurements', 'icon' => 'fa-ruler', 'url' => '/modules/measurements/index.php', 'roles' => ['administrator']],
        ['label' => 'Designs', 'icon' => 'fa-shirt', 'url' => '/modules/designs/index.php', 'roles' => ['administrator']],
        ['label' => 'Orders', 'icon' => 'fa-clipboard-list', 'url' => '/modules/orders/index.php', 'roles' => ['administrator', 'manager', 'tailor']],
        ['label' => 'Deliveries', 'icon' => 'fa-truck-fast', 'url' => '/modules/deliveries/index.php', 'roles' => ['administrator']],
        ['label' => 'Payments', 'icon' => 'fa-money-bill-wave', 'url' => '/modules/payments/index.php', 'roles' => ['administrator']],
        ['label' => 'Expenses', 'icon' => 'fa-wallet', 'url' => '/modules/expenses/index.php', 'roles' => ['administrator']],
        ['label' => 'Employees', 'icon' => 'fa-user-tie', 'url' => '/modules/employees/index.php', 'roles' => ['administrator']],
        ['label' => 'Reports', 'icon' => 'fa-chart-column', 'url' => '/modules/reports/index.php', 'roles' => ['administrator']],
        ['label' => 'Settings', 'icon' => 'fa-gear', 'url' => '/modules/settings/index.php', 'roles' => ['administrator']],
        ['label' => 'Customer Portal', 'icon' => 'fa-house-user', 'url' => '/modules/customer/index.php', 'roles' => ['manager', 'tailor', 'customer']],
        ['label' => 'Browse Designs', 'icon' => 'fa-shirt', 'url' => '/modules/customer/designs.php', 'roles' => ['manager', 'tailor', 'customer']],
        ['label' => 'My Orders', 'icon' => 'fa-clipboard-list', 'url' => '/modules/customer/orders.php', 'roles' => ['manager', 'tailor', 'customer']],
        ['label' => 'My Payments', 'icon' => 'fa-money-bill-wave', 'url' => '/modules/customer/payments.php', 'roles' => ['manager', 'tailor', 'customer']],
        ['label' => 'My Deliveries', 'icon' => 'fa-truck-fast', 'url' => '/modules/customer/deliveries.php', 'roles' => ['manager', 'tailor', 'customer']],
    ];

    $out = [];
    foreach ($all as $item) {
        if (in_array($role, $item['roles'], true)) {
            $out[] = $item;
        }
    }
    return $out;
}

function loginLandingPageForRole(?string $role): string
{
    return ($role === 'administrator') ? '/dashboard.php' : '/modules/customer/index.php';
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'],
    ];
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatCurrency(float $amount): string
{
    $settings = getBusinessSettings();
    $currency = $settings['currency'] ?? 'GHS';
    return $currency . ' ' . number_format($amount, 2);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        throw new RuntimeException('Invalid CSRF token.');
    }
}

function fetchDashboardStats(PDO $pdo): array
{
    $stats = [];
    $stats['customers'] = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    $stats['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
    $stats['in_progress'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Cutting', 'Sewing', 'Fitting', 'Alteration')")->fetchColumn();
    $stats['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Completed'")->fetchColumn();
    $stats['delivered'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();
    $stats['revenue'] = (float) $pdo->query('SELECT COALESCE(SUM(amount_paid), 0) FROM payments')->fetchColumn();
    $stats['outstanding'] = (float) $pdo->query('SELECT COALESCE(SUM(total_amount - total_paid), 0) FROM orders WHERE total_amount > total_paid')->fetchColumn();
    $stats['expenses'] = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')->fetchColumn();
    return $stats;
}

function dbQueryAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbQueryOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}
