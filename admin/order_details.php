<?php
require_once '../config.php';
define('WEBSITE_URL', BASE_URL);

function formatMoneyILS($amount)
{
    return number_format((float)$amount, 2) . ' ₪';
}

if (isset($_GET['id'])) {
    
    $order_id = cleanInput($_GET['id']);

    // Get order info
    $order_query = "SELECT o.*, u.first_name, u.last_name, u.phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?";
    $order_exec = executeQuery($conn, $order_query, "i", [$order_id]);

    if (mysqli_num_rows($order_exec['result']) > 0) {
        $order = mysqli_fetch_assoc($order_exec['result']);

        // Get order items with product names
        $items_query = "SELECT oi.*, p.name as product_name 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?";
        $items_exec = executeQuery($conn, $items_query, "i", [$order_id]);
        $items = [];
        while ($row = mysqli_fetch_assoc($items_exec['result'])) {
            $items[] = $row;
        }

        // Compute subtotal and shipping
        $subtotal = 0.0;
        foreach ($items as $row) {
            $subtotal += ((float)$row['unit_price']) * ((int)$row['quantity']);
        }

        $total_revenue = (float)$order['total_revenue'];
        $shipping_fee = (float)$order['shipping_fee'];

        $city = ''; 
        if ($order['city_id']) {
            $city_q = executeQuery($conn, "SELECT name FROM cities WHERE id = ?", "i", [$order['city_id']]);
            if ($c = mysqli_fetch_assoc($city_q['result'])) {
                $city = $c['name'];
            }
        }
        if (!$city) $city = '-';

        $address = $order['shipping_address'];
        $notes = ''; // We didn't explicitly add notes to orders in our schema, but can be skipped.

        // تنسيق رقم الهاتف للواتساب
        $wa_phone = normalizePhoneForWhatsApp($order['phone']);

        $store_name = SITE_NAME;
        $customer_name = $order['first_name'] . ' ' . $order['last_name'];
        
        $product_lines = [];
        foreach ($items as $it) {
            $product_lines[] = '- ' . $it['product_name'] . ' (عدد ' . ((int)$it['quantity']) . ')';
        }

        $product_list = implode("\n", $product_lines);
        
        $full_address = $city;
        if (!empty($address) && $address !== '-') {
            $full_address .= ' - ' . $address;
        }

        $msg_lines = [];
        $msg_lines[] = "مرحباً " . $customer_name . "، يتحدث إليك فريق {$store_name} 🌸";
        $msg_lines[] = "";
        $msg_lines[] = "بخصوص طلبك رقم (#{$order_id}) الذي يحتوي على:";
        $msg_lines[] = $product_list;
        $msg_lines[] = "";
        $msg_lines[] = "رسوم التوصيل: " . formatMoneyILS($shipping_fee);
        $msg_lines[] = "المجموع الكلي: " . formatMoneyILS($total_revenue);
        $msg_lines[] = "";
        $msg_lines[] = "📍 العنوان: {$full_address}";
        $msg_lines[] = "";
        $msg_lines[] = "يرجى الرد بكلمة *موافق* لتأكيد الطلب واعتماده ✅";
        $msg_lines[] = "";
        $msg_lines[] = "🌐 موقعنا: " . WEBSITE_URL;
        $whatsapp_message = implode("\n", $msg_lines);
        $whatsapp_url = $wa_phone ? ('https://wa.me/' . $wa_phone . '?text=' . rawurlencode($whatsapp_message)) : null;
?>

        <div class="order-details">
            <!-- Order Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-user me-2"></i>بيانات الزبون</h5>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-2"><strong>الاسم:</strong> <?php echo htmlspecialchars($customer_name); ?></p>
                        <p class="mb-2"><strong>الموبايل:</strong>
                            <a href="tel:<?php echo $order['phone']; ?>"><span dir="ltr"><?php echo htmlspecialchars($order['phone']); ?></span></a>
                            <br>
                            <?php if ($wa_phone): ?>
                                <small class="text-success">✓ صالح للواتساب: <?php echo $wa_phone; ?></small>
                            <?php else: ?>
                                <small class="text-danger">✗ غير صالح للواتساب</small>
                            <?php endif; ?>
                        </p>
                        <p class="mb-2"><strong>المدينة:</strong> <?php echo htmlspecialchars($city); ?></p>
                        <p class="mb-2"><strong>العنوان:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5><i class="fas fa-info-circle me-2"></i>معلومات الطلب</h5>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-2"><strong>رقم الطلب:</strong> #<?php echo $order['id']; ?></p>
                        <p class="mb-2"><strong>التاريخ:</strong> <?php echo date('Y/m/d h:i A', strtotime($order['created_at'])); ?></p>
                        <p class="mb-2"><strong>طريقة الدفع:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p class="mb-2"><strong>الحالة:</strong>
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
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <h5 class="mb-3"><i class="fas fa-shopping-bag me-2"></i>المنتجات المطلوبة</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item['unit_price']); ?></td>
                                <td><strong><?php echo formatPrice($item['unit_price'] * $item['quantity']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end"><strong>رسوم الشحن:</strong></td>
                            <td><strong><?php echo formatPrice($shipping_fee); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>المجموع الكلي للإيراد:</strong></td>
                            <td><strong class="text-success fs-5"><?php echo formatPrice($order['total_revenue']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Quick Actions -->
            <div class="mt-4 text-center">
                <a href="tel:<?php echo $order['phone']; ?>" class="btn btn-success">
                    <i class="fas fa-phone me-2"></i>اتصل بالزبون
                </a>
                <?php if ($whatsapp_url): ?>
                    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener" class="btn btn-success ms-2" style="background:#25D366;border-color:#25D366;">
                        <i class="fab fa-whatsapp me-2"></i>إرسال رسالة واتساب للزبون
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary ms-2" disabled title="رقم الموبايل غير صالح لواتساب - الرجاء التأكد من الرقم">
                        <i class="fab fa-whatsapp me-2"></i>إرسال رسالة واتساب للزبون
                    </button>
                    <div class="alert alert-warning mt-3">
                        <strong>تنبيه:</strong> رقم الموبايل "<?php echo htmlspecialchars($order['phone']); ?>" غير صالح لإرسال رسائل واتساب.
                        <br>الرجاء التأكد من أن الرقم بصيغة صحيحة مثل: 0599123456 أو 972599123456
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php
    } else {
        echo '<div class="alert alert-danger">الطلب مش موجود</div>';
    }
} else {
    echo '<div class="alert alert-danger">رقم الطلب مطلوب</div>';
}
?>