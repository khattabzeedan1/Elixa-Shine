<?php
require_once '../config.php';
requireAdmin();

// Get selected month (default: current month)
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get monthly profit
$monthly_profit = getMonthlyProfit($selected_month);

// Get monthly revenue from view
$revenue_query = "SELECT SUM(gross_revenue) as revenue FROM daily_profit_analytics 
                  WHERE DATE_FORMAT(profit_date, '%Y-%m') = '$selected_month'";
$monthly_revenue = mysqli_fetch_assoc(mysqli_query($conn, $revenue_query))['revenue'] ?? 0;

// Get profit details dynamically for the month
$details_query = "SELECT o.id as order_id, p.name as product_name, oi.quantity as quantity_sold, 
                         oi.unit_cost as purchase_price, oi.unit_price as selling_price, 
                         ((oi.unit_price - oi.unit_cost) * oi.quantity) as profit, o.created_at 
                  FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE DATE_FORMAT(o.created_at, '%Y-%m') = '$selected_month' 
                  AND o.status IN ('delivered', 'shipped')
                  ORDER BY o.created_at DESC";
$details_result = mysqli_query($conn, $details_query);

// Get last 12 months for dropdown
$months = [];
for ($i = 0; $i < 12; $i++) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = $month;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأرباح - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
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
                <h2><i class="fas fa-chart-line me-2"></i>الأرباح الشهرية</h2>

                <form method="GET" class="d-flex gap-2">
                    <select class="form-select" name="month" onchange="this.form.submit()">
                        <?php foreach ($months as $month): ?>
                            <option value="<?php echo $month; ?>" <?php echo $month === $selected_month ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($month . '-01')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0">
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
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">الربح الصافي</h6>
                                    <h3 class="text-primary mb-0"><?php echo formatPrice($monthly_profit); ?></h3>
                                </div>
                                <i class="fas fa-chart-line fa-3x text-primary opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">نسبة الربح</h6>
                                    <h3 class="text-info mb-0">
                                        <?php
                                        if ($monthly_revenue > 0) {
                                            echo number_format(($monthly_profit / $monthly_revenue) * 100, 1) . '%';
                                        } else {
                                            echo '0%';
                                        }
                                        ?>
                                    </h3>
                                </div>
                                <i class="fas fa-percent fa-3x text-info opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit Details Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>تفاصيل الأرباح</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($details_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>سعر الشراء</th>
                                        <th>سعر البيع</th>
                                        <th>الربح</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($detail = mysqli_fetch_assoc($details_result)): ?>
                                        <tr>
                                            <td><strong>#<?php echo $detail['order_id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($detail['product_name']); ?></td>
                                            <td><?php echo $detail['quantity_sold']; ?></td>
                                            <td><?php echo formatPrice($detail['purchase_price']); ?></td>
                                            <td><?php echo formatPrice($detail['selling_price']); ?></td>
                                            <td><strong class="text-success"><?php echo formatPrice($detail['profit']); ?></strong></td>
                                            <td><?php echo date('Y/m/d', strtotime($detail['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>الإجمالي:</strong></td>
                                        <td colspan="2"><strong class="text-success fs-5"><?php echo formatPrice($monthly_profit); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                            <p>ما في أرباح لهذا الشهر</p>
                            <small class="text-muted">الأرباح تُحسب فقط للطلبات اللي تم توصيلها</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>