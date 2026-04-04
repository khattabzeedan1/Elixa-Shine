<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = cleanInput($_POST['status']);

    $current_query = "SELECT * FROM orders WHERE id = ?";
    $current_exec = executeQuery($conn, $current_query, "i", [$order_id]);
    $current_data = mysqli_fetch_assoc($current_exec['result']);
    
    if (!$current_data) {
        $error = 'الطلب غير موجود';
    } else {
        $old_status = $current_data['status'];

        $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
        
        if (executeQuery($conn, $update_sql, "si", [$new_status, $order_id])) {
            
            // Stock Restoration
            if ($new_status === 'canceled' && $old_status !== 'canceled') {
                $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                $items_exec_stock = executeQuery($conn, $items_query, "i", [$order_id]);
                while ($item = mysqli_fetch_assoc($items_exec_stock['result'])) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    executeQuery($conn, "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", "ii", [$qty, $pid]);
                }
            } else if ($old_status === 'canceled' && $new_status !== 'canceled') {
                // Deduct stock again if un-canceled
                $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                $items_exec_stock = executeQuery($conn, $items_query, "i", [$order_id]);
                while ($item = mysqli_fetch_assoc($items_exec_stock['result'])) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    executeQuery($conn, "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?", "ii", [$qty, $pid]);
                }
            }

            $success = 'تم تحديث حالة الطلب بنجاح';
        }
    }
}


$orders_query = "SELECT o.*, u.first_name, u.last_name, u.phone 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 ORDER BY o.created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

        * {
            font-family: 'Cairo', sans-serif;
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
            <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>إدارة الطلبات</h2>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-times-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>اسم الزبون</th>
                                    <th>الموبايل</th>
                                    <th>العنوان</th>
                                    <th>الإيراد</th>
                                    <th>اجمالي التكلفة</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><span dir="ltr"><?php echo htmlspecialchars($order['phone']); ?></span></td>
                                        <td><?php echo htmlspecialchars(substr($order['shipping_address'], 0, 30)); ?>...</td>
                                        <td><strong class="text-success"><?php echo formatPrice($order['total_revenue']); ?></strong></td>
                                        <td><span class="text-danger"><?php echo formatPrice($order['total_cost']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo match($order['status']) {
                                                'pending', 'processing' => 'warning',
                                                'shipped', 'delivered' => 'success',
                                                'canceled', 'returned' => 'danger',
                                                default => 'secondary'
                                            }; ?>">
                                                <?php echo match($order['status']) {
                                                    'pending' => 'قيد الانتظار',
                                                    'processing' => 'قيد التجهيز',
                                                    'shipped' => 'تم الشحن',
                                                    'delivered' => 'تم التوصيل',
                                                    'canceled' => 'ملغي',
                                                    'returned' => 'مرتجع',
                                                    default => $order['status']
                                                }; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    <h5 class="modal-title">تحديث حالة الطلب</h5>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">حالة الطلب</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>قيد التجهيز</option>
                                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>تم التوصيل</option>
                                                                <option value="returned" <?php echo $order['status'] === 'returned' ? 'selected' : ''; ?>>مرتجع</option>
                                                                <option value="canceled" <?php echo $order['status'] === 'canceled' ? 'selected' : ''; ?>>ملغي</option>
                                                            </select>
                                                            <small class="text-muted">ملاحظة: الأرباح تحسب فقط للطلبات (تم الشحن / تم التوصيل).</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title">تفاصيل الطلب</h5>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function viewOrderDetails(orderId) {
            $('#orderDetailsModal').modal('show');
            $.get('order_details.php?id=' + orderId, function(data) {
                $('#orderDetailsContent').html(data);
            });
        }
    </script>
</body>
</html>