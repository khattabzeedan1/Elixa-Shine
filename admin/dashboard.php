<?php
require_once '../config.php';
requireAdmin();

$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM categories"))['count'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status='pending'"))['count'];

$current_month = date('Y-m');
$monthly_profit = getMonthlyProfit($current_month);

$revenue_query = "SELECT SUM(total_revenue) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month' AND status IN ('delivered', 'shipped')";
$monthly_revenue = mysqli_fetch_assoc(mysqli_query($conn, $revenue_query))['revenue'] ?? 0;

$unread_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM messages WHERE status='unread'"))['count'];

$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock_quantity < 5"))['count'];

$recent_orders_query = "SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);

$top_products_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       JOIN orders o ON oi.order_id = o.id 
                       WHERE DATE_FORMAT(o.created_at, '%Y-%m') = '$current_month'
                       AND o.status = 'delivered'
                       GROUP BY oi.product_id 
                       ORDER BY total_sold DESC 
                       LIMIT 5";
$top_products = mysqli_query($conn, $top_products_query);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            left: 25px;
            top: 20px;
        }

        .stat-card .card-body {
            padding-right: 4.5rem;
        }

        .stat-card .card-footer {
            padding-right: 1.5rem;
            padding-left: 1.5rem;
        }

        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }
        }

       

       .main-content {
            margin-right: 280px;
            padding: 30px;
            overflow-y: auto;
            height: 100vh;
        }
   

    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <h2><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h2>
                    <p class="text-muted mb-0">أهلاً، <?php echo $_SESSION['admin_username']; ?>!</p>
                </div>
                <div>
                    <span class="badge bg-primary fs-6 p-2">
                        <i class="far fa-calendar me-1"></i><?php echo date('Y/m/d'); ?>
                    </span>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-primary text-white shadow">
                        <div class="card-body position-relative">
                            <i class="fas fa-folder stat-icon"></i>
                            <h6 class="text-uppercase mb-2">الأقسام</h6>
                            <h2 class="mb-0"><?php echo $total_categories; ?></h2>
                            <small>إجمالي الأقسام</small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="categories.php" class="text-white text-decoration-none">
                                عرض الكل <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-success text-white shadow">
                        <div class="card-body position-relative">
                            <i class="fas fa-box stat-icon"></i>
                            <h6 class="text-uppercase mb-2">المنتجات</h6>
                            <h2 class="mb-0"><?php echo $total_products; ?></h2>
                            <small>في المخزن</small>
                            <?php if ($low_stock_count > 0): ?>
                                <div class="mt-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $low_stock_count; ?> قليل
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="products.php" class="text-white text-decoration-none">
                                إدارة <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-info text-white shadow">
                        <div class="card-body position-relative">
                            <i class="fas fa-shopping-cart stat-icon"></i>
                            <h6 class="text-uppercase mb-2">الطلبات</h6>
                            <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                            <small>إجمالي الطلبات</small>
                            <?php if ($pending_orders > 0): ?>
                                <div class="mt-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock"></i> <?php echo $pending_orders; ?> قيد الانتظار
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="orders.php" class="text-white text-decoration-none">
                                عرض الطلبات <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-warning text-white shadow">
                        <div class="card-body position-relative">
                            <i class="fas fa-chart-line stat-icon"></i>
                            <h6 class="text-uppercase mb-2">الربح الشهري</h6>
                            <h2 class="mb-0"><?php echo formatPrice($monthly_profit); ?></h2>
                            <small><?php echo date('F Y'); ?></small>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="profits.php" class="text-white text-decoration-none">
                                التقرير <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">المبيعات الشهرية</h6>
                                    <h3 class="text-success mb-0"><?php echo formatPrice($monthly_revenue); ?></h3>
                                </div>
                                <i class="fas fa-dollar-sign fa-3x text-success opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">الرسائل</h6>
                                    <h3 class="text-primary mb-0"><?php echo $unread_messages; ?></h3>
                                </div>
                                <i class="fas fa-envelope fa-3x text-primary opacity-25"></i>
                            </div>
                            <a href="messages.php" class="btn btn-sm btn-outline-primary mt-2 w-100">
                                <i class="fas fa-comments"></i> عرض الرسائل
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">طلبات معلقة</h6>
                                    <h3 class="text-danger mb-0"><?php echo $pending_orders; ?></h3>
                                </div>
                                <i class="fas fa-clock fa-3x text-danger opacity-25"></i>
                            </div>
                            <a href="orders.php" class="btn btn-sm btn-outline-danger mt-2 w-100">
                                <i class="fas fa-tasks"></i> معالجة الطلبات
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>آخر الطلبات</h5>
                            <a href="orders.php" class="btn btn-sm btn-primary">عرض الكل</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>رقم الطلب</th>
                                            <th>الزبون</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                            <tr>
                                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                <td><strong class="text-success"><?php echo formatPrice($order['total_revenue']); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                                            echo $order['status'] === 'pending' ? 'warning' : ($order['status'] === 'delivered' ? 'success' : 'danger');
                                                                            ?>">
                                                        <?php
                                                        echo $order['status'] === 'pending' ? 'قيد الانتظار' : ($order['status'] === 'delivered' ? 'تم التوصيل' : 'ملغي');
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-fire me-2"></i>الأكثر مبيعاً</h5>
                            <small class="text-muted">هذا الشهر</small>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($top_products) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php $rank = 1; ?>
                                    <?php while ($product = mysqli_fetch_assoc($top_products)): ?>
                                        <div class="list-group-item border-0 px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; line-height: 20px;">
                                                        <?php echo $rank++; ?>
                                                    </span>
                                                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                                                </div>
                                                <span class="badge bg-success">
                                                    <?php echo $product['total_sold']; ?> قطعة
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-bar fa-3x mb-3 d-block"></i>
                                    <p>لا توجد مبيعات بعد</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>