<?php
require_once '../config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ? AND role IN ('admin', 'super_admin')";
    $exec = executeQuery($conn, $query, "s", [$username]);
    $result = $exec['result'];

    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);

        if (password_verify($password, $admin['password_hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['first_name'] . ' ' . $admin['last_name'];
            
            // Generate CSRF token immediately upon login
            generateCsrfToken();

            header('Location: dashboard.php');
            exit();
        } else {
            // Slow down brute force attacks
            sleep(1);
            $error = 'كلمة المرور غلط';
        }
    } else {
        $error = 'اسم المستخدم مش موجود';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
        }

        .form-control:focus {
            border-color: #00bcd4;
            box-shadow: 0 0 0 0.2rem rgba(0, 188, 212, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 188, 212, 0.4);
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-user-shield fa-3x mb-3"></i>
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="mb-0">لوحة التحكم</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>الإيميل أو اسم المستخدم
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                        autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>كلمة المرور
                    </label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted text-decoration-none">
                    <i class="fas fa-arrow-right me-1"></i>رجوع للمتجر
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>