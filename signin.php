<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$settings = getBusinessSettings();
$landingPage = loginLandingPageForRole($_SESSION['user_role'] ?? null);

if (!empty($_SESSION['user_id'])) {
    redirect($landingPage);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if ($email === '' || $password === '') {
            throw new RuntimeException('Email and password are required.');
        }

        $pdo = getDbConnection();
        if (!priestLogin($pdo, $email, $password)) {
            throw new RuntimeException('Invalid email or password.');
        }

        if ($remember) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);
        }

        setFlash('success', 'Welcome back, ' . ($_SESSION['user_name'] ?? '') . '.');
        redirect(loginLandingPageForRole($_SESSION['user_role'] ?? null));
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
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
    <title>Sign In · <?= e($settings['business_name'] ?? 'PRIEST') ?></title>
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
                    <span>Email Address</span>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter password" required>
                </label>
                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>
                <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-right-to-bracket"></i> Continue</button>
            </form>

            <div class="auth-divider">
                <span>Or continue with</span>
            </div>

            <div class="auth-social">
                <button type="button" class="btn btn-outline w-100"><i class="fa-brands fa-google"></i> Continue with Google</button>
                <button type="button" class="btn btn-outline w-100"><i class="fa-brands fa-apple"></i> Continue with Apple</button>
            </div>

            <div class="auth-foot">
                <small>New user? <a href="<?= PRIEST_BASE_URL ?>/customer-register.php">Create a customer account</a></small><br>
                <small><a href="<?= PRIEST_BASE_URL ?>/index.php">Back to sign in</a></small>
            </div>
        </div>
    </div>
    <script src="<?= PRIEST_BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
