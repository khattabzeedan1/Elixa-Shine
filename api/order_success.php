<?php
require_once '../config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم استلام طلبك</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        * { font-family: 'Cairo', sans-serif; }
        body { background: #f5f7fa; }
        .success-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 22px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="success-card text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                    </div>
                    <h3 class="mb-2">تم استلام طلبك بنجاح</h3>
                    <?php if ($order_id > 0): ?>
                        <p class="text-muted mb-4">رقم الطلب: <strong>#<?php echo $order_id; ?></strong></p>
                    <?php else: ?>
                        <p class="text-muted mb-4">شكراً لك! تم تسجيل طلبك.</p>
                    <?php endif; ?>

                    <div class="alert alert-info text-start">
                        <strong>ملاحظة:</strong> سيتم التواصل معك لتأكيد الطلب.
                    </div>

                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-store me-2"></i>العودة للمتجر
                        </a>
                        <a href="../cart.php" class="btn btn-outline-secondary">
                            <i class="fas fa-shopping-cart me-2"></i>العودة للسلة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

