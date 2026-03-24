<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UA ACADEMY</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ems-primary: #1e3a5f;
            --ems-primary-2: #2a5f8f;
            --ems-accent: #0ea5a4;
            --ems-bg: #eef3f9;
            --ems-text: #1f2a37;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--ems-text);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .login-shell {
            width: 100%;
            max-width: 930px;
            background: #fff;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid #d8e2ef;
            box-shadow: 0 24px 48px rgba(30, 58, 95, 0.2);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
        }

        .brand-panel {
            position: relative;
            background: linear-gradient(145deg, var(--ems-primary), var(--ems-primary-2));
            color: #fff;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            right: -100px;
            top: -120px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle at center, rgba(14, 165, 164, 0.35), transparent 72%);
        }

        .brand-head {
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            padding: 6px;
        }

        .brand-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 900;
            margin: 0;
            font-size: 1.95rem;
            line-height: 1.05;
            letter-spacing: -0.4px;
        }

        .brand-title .subline {
            display: block;
            margin-top: 4px;
            font-size: 0.63em;
            font-weight: 700;
            letter-spacing: 0.6px;
            color: rgba(255, 255, 255, 0.88);
        }

        .brand-sub {
            margin-top: 12px;
            color: rgba(255,255,255,0.82);
            max-width: 350px;
            font-size: 0.95rem;
            line-height: 1.45;
        }

        .brand-foot {
            position: relative;
            z-index: 1;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.82);
        }

        .login-panel {
            padding: 34px 32px;
            background: #fff;
        }

        .login-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            border-radius: 999px;
            background: #edf4ff;
            color: var(--ems-primary);
            padding: 7px 12px;
            margin-bottom: 14px;
        }

        .login-panel h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            margin-bottom: 6px;
            color: var(--ems-primary);
            font-size: 2.1rem;
        }

        .login-panel p {
            color: #6b7787;
            margin-bottom: 22px;
            font-size: 0.95rem;
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            border-right: none;
            background: #f6f9ff;
            border-color: #d4dfee;
        }

        .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
            border-color: #d4dfee;
            min-height: 46px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #9db5d2;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .btn-login {
            background: linear-gradient(115deg, var(--ems-primary), var(--ems-primary-2));
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            letter-spacing: 0.3px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(30, 58, 95, 0.25);
        }

        .back-link {
            text-align: center;
            margin-top: 18px;
        }

        .back-link a {
            color: #5b6a7f;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            color: var(--ems-primary);
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 220px;
            }

            .brand-title {
                font-size: 1.5rem;
            }

            .login-panel h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-shell">
        <div class="brand-panel">
            <div class="brand-head">
                <img src="assets/img/logo.png" alt="UA ACADEMY Logo" class="brand-mark">
                <h1 class="brand-title">UA Academy<span class="subline">Enrollment Management System</span></h1>
                <p class="brand-sub">A modern enrollment management platform for admissions, student tracking, enrollment, and payment operations.</p>
            </div>
            <div class="brand-foot">Secure access for authorized staff only.</div>
        </div>

        <div class="login-panel">
            <span class="login-kicker"><i class="fas fa-fingerprint"></i> Staff Login</span>
            <h2>Welcome Back</h2>
            <p>Sign in to continue to your dashboard and manage enrollment modules.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center rounded-3 py-2">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
                </div>
                
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <button type="submit" class="btn btn-login mt-2"><i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard</button>
            </form>

            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>