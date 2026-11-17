<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(loginLandingPageForRole($_SESSION['user_role'] ?? null));
}

$settings = getBusinessSettings();
$formValues = ['full_name' => '', 'email' => '', 'phone' => ''];
$pdo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $readPostString = static fn(string $key): string => isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $formValues = [
        'full_name' => trim($readPostString('full_name')),
        'email' => trim($readPostString('email')),
        'phone' => trim($readPostString('phone')),
    ];

    try {
        if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token.');
        }
        validateCsrf();
        $name = $formValues['full_name'];
        $email = $formValues['email'];
        $phone = $formValues['phone'];
        $password = $readPostString('password');
        $confirm = $readPostString('confirm_password');

        if ($name === '' || $email === '' || $password === '' || $confirm === '') {
            throw new InvalidArgumentException('Full name, email, password and confirmation are required.');
        }

        $nameLength = preg_match_all('/./us', $name);
        $emailLength = preg_match_all('/./us', $email);
        $phoneLength = preg_match_all('/./us', $phone);
        if ($nameLength === false || $emailLength === false || $phoneLength === false) {
            throw new InvalidArgumentException('Please use valid text characters in your details.');
        }
        if ($nameLength > 120 || $emailLength > 150 || $phoneLength > 40) {
            throw new InvalidArgumentException('Please shorten your name, email address, or phone number.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters.');
        }
        if ($password !== $confirm) {
            throw new InvalidArgumentException('Passwords do not match.');
        }

        $pdo = getDbConnection();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            throw new InvalidArgumentException('An account with this email already exists.');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (:full_name, :email, :password_hash, :role, :status)');
        $stmt->execute([
            'full_name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
        ]);
        $userId = (int) $pdo->lastInsertId();

        $customerId = 'CUST-' . str_pad($userId, 6, '0', STR_PAD_LEFT);
        $cstmt = $pdo->prepare('INSERT INTO customers (customer_id, user_id, full_name, phone, email) VALUES (:customer_id, :user_id, :full_name, :phone, :email)');
        $cstmt->execute([
            'customer_id' => $customerId,
            'user_id' => $userId,
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
        $pdo->commit();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'customer';

        redirect('/modules/customer/index.php');
    } catch (InvalidArgumentException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', $e->getMessage());
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
        error_log('Customer registration database error: ' . $message);

        if (preg_match('/(Unknown column|SQLSTATE\[42S22\]|Data truncated|doesn\'t have a default value|Column count doesn\'t match)/i', $message)) {
            setFlash('danger', 'Customer account setup needs the latest database schema. Please run the migration in database/migrate_customer.sql or database/schema.sql before retrying.');
        } else {
            setFlash('danger', 'We could not create your account right now. Please try again shortly.');
        }
    } catch (RuntimeException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getMessage() === 'Invalid CSRF token.') {
            setFlash('danger', 'Your form session expired. Please reload the page and try again.');
        } else {
            error_log('Customer registration error: ' . $e->getMessage());
            setFlash('danger', 'We could not create your account right now. Please try again shortly.');
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Customer Sign Up · <?= e($settings['business_name'] ?? 'PRIEST') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= PRIEST_BASE_URL ?>/assets/css/style.css">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('priest-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="auth-page">
    <?php $backgroundVideo = 'fashion-runway-red.mp4'; include __DIR__ . '/includes/background-video.php'; ?>
    <div class="auth-wrapper">
        <div class="auth-panel">
            <div class="auth-brand">
                <img class="auth-logo" src="<?= PRIEST_BASE_URL ?>/assets/images/priest-logo.svg" alt="PRIEST Atelier logo">
                <div>
                    <h2>PRIEST</h2>
                    <small>ATELIER · TAILORING & DESIGN</small>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="full_name" placeholder="Your full name" maxlength="120" value="<?= e($formValues['full_name']) ?>" required>
                </label>
                <label>
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="you@example.com" maxlength="150" value="<?= e($formValues['email']) ?>" required>
                </label>
                <label>
                    <span>Phone Number</span>
                    <input type="tel" name="phone" placeholder="Your phone number" maxlength="40" value="<?= e($formValues['phone']) ?>">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="At least 6 characters" required>
                </label>
                <label>
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </label>
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-user-plus"></i> Create Customer Account</button>
            </form>

            <div class="auth-foot">
                <small>Already have an account? <a href="<?= PRIEST_BASE_URL ?>/index.php">Sign in</a></small>
            </div>
        </div>
    </div>
    <script src="<?= PRIEST_BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
