<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    verifyCsrfToken();

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current admin data
    $admin_id = $_SESSION['admin_id'];
    $query = "SELECT * FROM admins WHERE id = $admin_id";
    $result = mysqli_query($conn, $query);
    $admin = mysqli_fetch_assoc($result);

    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif (strlen($new_password) < 6) {
        $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
    } elseif ($new_password !== $confirm_password) {
        $error = 'كلمتا المرور غير متطابقتين';
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $update_query = "UPDATE admins SET password = '$hashed_password' WHERE id = $admin_id";

        if (mysqli_query($conn, $update_query)) {
            $success = 'تم تغيير كلمة المرور بنجاح!';
        } else {
            $error = 'فشل تغيير كلمة المرور';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير كلمة المرور - <?php echo SITE_NAME; ?></title>

    <style>
        .main-content {
            margin-right: 280px;
            padding: 30px;
            overflow-y: auto;
            height: 100vh;
        }
    </style>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="fas fa-key me-2"></i>تغيير كلمة المرور</h2>

            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">كلمة المرور الحالية *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <small class="text-muted">يجب أن تكون 6 أحرف على الأقل</small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>حفظ كلمة المرور الجديدة
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-right me-2"></i>رجوع
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-shield-alt me-2"></i>نصائح الأمان:</h6>
                        <ul class="mb-0">
                            <li>استخدم كلمة مرور قوية (حروف كبيرة وصغيرة + أرقام + رموز)</li>
                            <li>لا تشارك كلمة المرور مع أحد</li>
                            <li>غيّر كلمة المرور بشكل دوري</li>
                            <li>لا تستخدم نفس كلمة المرور في مواقع أخرى</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>