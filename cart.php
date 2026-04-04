<?php
require_once 'config.php';

$cart_items = getCartItems();
$total = 0;
$items = [];

while ($item = mysqli_fetch_assoc($cart_items)) {
    $effective_price = getEffectivePrice($item['price'], $item['discount_price']);
    $item['effective_price'] = $effective_price;
    $item['has_discount'] = hasDiscount($item['price'], $item['discount_price']);
    $items[] = $item;
    $total += $effective_price * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>السلة - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        /* استبدل الـ CSS الموجود بهذا الكود المحسّن */

        /* تحسينات خاصة بنافذة معلومات العميل للهواتف */
        .customer-info-modal-header {
            background: #007bff;
            color: white;
            border-bottom: none;
            padding: 15px;
        }

        .customer-info-modal-body {
            padding: 15px;
        }

        /* تحسينات للهواتف المحمولة - نافذة معلومات العميل */
        @media (max-width: 767px) {
            #customerInfoModal .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
                width: 100%;
            }

            #customerInfoModal .modal-content {
                border-radius: 10px;
                overflow: hidden;
            }

            .customer-info-modal-header {
                padding: 12px 15px;
            }

            .customer-info-modal-header .modal-title {
                font-size: 1rem;
                white-space: normal;
                line-height: 1.3;
            }

            .customer-info-modal-body {
                padding: 15px 12px;
                max-height: calc(100vh - 100px);
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* تحسين الفورم */
            #customerInfoForm .form-label {
                font-size: 0.9rem;
                margin-bottom: 6px;
                font-weight: 600;
            }

            #customerInfoForm .form-control,
            #customerInfoForm .form-select {
                font-size: 0.95rem;
                padding: 10px 12px;
                border-radius: 6px;
            }

            #customerInfoForm .mb-3 {
                margin-bottom: 1rem !important;
            }

            #customerInfoForm textarea.form-control {
                min-height: 80px;
                resize: none;
            }

            /* تحسين عرض تكلفة الشحن */
            .shipping-cost-display {
                background: #e7f3ff;
                padding: 10px;
                border-radius: 6px;
                margin-top: 10px;
                font-size: 0.9rem;
                color: #0056b3;
                font-weight: 600;
                text-align: center;
            }

            /* تحسين زر التالي */
            #customerInfoForm button[type="submit"] {
                padding: 12px 20px;
                font-size: 1rem;
                font-weight: 600;
                border-radius: 8px;
                margin-top: 5px;
            }

            /* منع التمرير الأفقي */
            #customerInfoModal .modal-body {
                overflow-x: hidden !important;
            }

            #customerInfoModal input,
            #customerInfoModal select,
            #customerInfoModal textarea {
                max-width: 100%;
                box-sizing: border-box;
            }
        }

        /* للشاشات الصغيرة جداً */
        @media (max-width: 400px) {
            .customer-info-modal-header .modal-title {
                font-size: 0.9rem;
            }

            #customerInfoForm .form-label {
                font-size: 0.85rem;
            }

            #customerInfoForm .form-control,
            #customerInfoForm .form-select {
                font-size: 0.9rem;
                padding: 8px 10px;
            }

            .shipping-cost-display {
                font-size: 0.85rem;
                padding: 8px;
            }
        }

        .confirmation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .confirmation-table thead {
            background: #f8f9fa;
        }

        .confirmation-table th,
        .confirmation-table td {
            padding: 12px 10px;
            text-align: right;
            border-bottom: 1px solid #dee2e6;
        }

        .confirmation-table th {
            font-weight: 600;
            color: #495057;
        }

        .confirmation-table tbody tr:hover {
            background: #f8f9fa;
        }

        .price-original {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85em;
            margin-left: 8px;
        }

        .price-discounted {
            color: #dc3545;
            font-weight: 600;
        }

        .order-summary-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 1rem;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.3em;
            color: #dc3545;
            margin-top: 10px;
            padding-top: 15px;
        }

        .customer-info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .customer-info-section h6 {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .customer-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 1rem;
        }

        .customer-info-row:last-child {
            border-bottom: none;
        }

        .customer-info-row span:first-child {
            color: #666;
            flex-shrink: 0;
            margin-left: 15px;
            min-width: 100px;
        }

        .customer-info-row strong {
            text-align: left;
            word-break: break-word;
            max-width: 70%;
        }

        .confirm-btn,
        .cancel-btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: #28a745;
            color: white;
        }

        .confirm-btn:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        /* تحسينات خاصة بالأجهزة اللوحية (Tablets) */
        @media (min-width: 768px) and (max-width: 991px) {
            .modal-dialog.modal-lg {
                max-width: 90%;
            }

            .confirmation-modal-body {
                padding: 20px;
            }

            .confirmation-table {
                font-size: 0.95rem;
            }

            .confirmation-table th,
            .confirmation-table td {
                padding: 10px 8px;
            }

            .customer-info-section,
            .order-summary-section {
                padding: 18px;
            }

            .confirm-btn,
            .cancel-btn {
                padding: 12px 25px;
                font-size: 0.95rem;
            }
        }

        /* تحسينات خاصة بالكمبيوتر */
        @media (min-width: 992px) {
            .modal-dialog.modal-lg {
                max-width: 900px;
            }

            .confirmation-modal-header {
                padding: 25px 30px;
            }

            .confirmation-modal-header .modal-title {
                font-size: 1.3rem;
            }

            .confirmation-modal-body {
                padding: 30px;
            }

            .confirmation-table {
                font-size: 1rem;
            }

            .confirmation-table th,
            .confirmation-table td {
                padding: 14px 12px;
            }

            .customer-info-section,
            .order-summary-section {
                padding: 25px;
            }

            .customer-info-section h6,
            .order-summary-section h6 {
                font-size: 1.15rem;
                margin-bottom: 18px;
            }

            .customer-info-row,
            .summary-row {
                padding: 12px 0;
                font-size: 1.05rem;
            }

            .summary-row:last-child {
                font-size: 1.4em;
                padding-top: 18px;
                margin-top: 12px;
            }

            #order_notes {
                font-size: 1rem;
                padding: 12px;
            }

            .d-flex.gap-2 {
                gap: 15px !important;
                margin-top: 25px;
            }

            .confirm-btn,
            .cancel-btn {
                padding: 14px 35px;
                font-size: 1.05rem;
            }
        }

        /* تحسينات خاصة بالشاشات الكبيرة جداً */
        @media (min-width: 1200px) {
            .modal-dialog.modal-lg {
                max-width: 1000px;
            }

            .confirmation-modal-body {
                padding: 35px;
            }

            .customer-info-row span:first-child {
                min-width: 120px;
            }
        }

        /* تحسينات خاصة بالهواتف المحمولة */
        @media (max-width: 767px) {
            .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .confirmation-modal-header {
                padding: 12px 15px;
            }

            .confirmation-modal-header .modal-title {
                font-size: 1rem;
            }

            .confirmation-modal-body {
                padding: 15px 12px;
                max-height: calc(100vh - 100px);
                overflow-y: auto;
            }

            .confirmation-table {
                font-size: 0.8rem;
                display: block;
                overflow-x: auto;
            }

            .confirmation-table th,
            .confirmation-table td {
                padding: 8px 4px;
                font-size: 0.75rem;
            }

            .confirmation-table th {
                font-size: 0.75rem;
            }

            /* تصغير أسماء المنتجات */
            .confirmation-table td:first-child {
                max-width: 80px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .price-original {
                font-size: 0.7em;
                display: block;
                margin-left: 0;
            }

            .price-discounted {
                font-size: 0.85em;
            }

            .customer-info-section,
            .order-summary-section {
                padding: 12px;
                margin-bottom: 12px;
            }

            .customer-info-section h6,
            .order-summary-section h6 {
                font-size: 0.9rem;
                margin-bottom: 10px;
            }

            .customer-info-row,
            .summary-row {
                font-size: 0.85rem;
                padding: 6px 0;
            }

            .customer-info-row span:first-child {
                min-width: auto;
                margin-left: 10px;
            }

            .customer-info-row strong {
                max-width: 60%;
                font-size: 0.85rem;
            }

            .summary-row:last-child {
                font-size: 1rem;
            }

            #order_notes {
                font-size: 0.9rem;
                padding: 8px;
            }

            .confirm-btn,
            .cancel-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .d-flex.gap-2 {
                gap: 8px !important;
            }

            /* تحسين عرض الجدول على الشاشات الصغيرة جداً */
            @media (max-width: 400px) {
                .confirmation-table {
                    font-size: 0.7rem;
                }

                .confirmation-table th,
                .confirmation-table td {
                    padding: 6px 2px;
                }

                .customer-info-row,
                .summary-row {
                    font-size: 0.8rem;
                }

                .confirmation-modal-header .modal-title {
                    font-size: 0.9rem;
                }
            }
        }

        /* تحسين التمرير للأجهزة الصغيرة */
        @media (max-height: 700px) and (max-width: 767px) {
            .confirmation-modal-body {
                max-height: calc(100vh - 80px);
            }
        }

        /* إخفاء أيقونات غير ضرورية على الأجهزة الصغيرة */
        @media (max-width: 576px) {

            .customer-info-section .fas,
            .order-summary-section .fas {
                font-size: 0.85rem;
            }
        }

        /* تحسينات إضافية للجدول */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* تحسين شكل الـ Modal Backdrop */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.6);
        }

        /* تحسين الانتقالات */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
        }

        /* تحسين شكل الأزرار في حالة التحميل */
        .confirm-btn:disabled,
        .cancel-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* تحسين التباعد في الأجهزة المتوسطة */
        @media (min-width: 768px) {
            .mb-3 {
                margin-bottom: 1.5rem !important;
            }

            .mb-4 {
                margin-bottom: 2rem !important;
            }
        }

        /* تحسينات خاصة بالكمبيوتر */
        @media (min-width: 992px) {
            .modal-dialog.modal-lg {
                max-width: 700px;
                /* غيّر من 900px إلى 700px */
            }

            .confirmation-modal-body {
                padding: 20px;
                /* قلّل من 30px إلى 20px */
            }
        }

        /* تحسينات خاصة بالشاشات الكبيرة جداً */
        @media (min-width: 1200px) {
            .modal-dialog.modal-lg {
                max-width: 750px;
                /* غيّر من 1000px إلى 750px */
            }

            .confirmation-modal-body {
                padding: 25px;
                /* قلّل من 35px إلى 25px */
            }
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-cart me-2"></i>سلة المشتريات</h2>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-right me-2"></i>رجوع للمتجر
            </a>
        </div>

        <?php if (empty($items)): ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                <h4>السلة فاضية</h4>
                <p>ضيف منتجات للسلة عشان تكمل</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-store me-2"></i>شوف المنتجات
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo $item['image']; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height: 100px;">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-5">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <?php if ($item['has_discount']): ?>
                                        <p class="text-muted small mb-1">
                                            <span style="text-decoration: line-through;"><?php echo formatPrice($item['price']); ?></span>
                                            <span class="text-danger fw-bold"><?php echo formatPrice($item['effective_price']); ?></span>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted small mb-2">السعر: <?php echo formatPrice($item['effective_price']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-3">
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary update-qty" data-action="decrease">-</button>
                                        <input type="number" class="form-control text-center item-quantity"
                                            value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" readonly>
                                        <button class="btn btn-outline-secondary update-qty" data-action="increase">+</button>
                                    </div>
                                    <div class="text-center mt-2">
                                        <strong class="item-price"><?php echo formatPrice($item['effective_price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                                <div class="col-1 text-center">
                                    <button class="btn btn-danger btn-sm remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5 class="mb-4">ملخص الطلب</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>المجموع الفرعي:</span>
                            <strong id="subtotal"><?php echo formatPrice($total); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>رسوم التوصيل:</span>
                            <strong id="summaryShipping">-</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>الإجمالي:</strong>
                            <strong class="text-danger fs-4" id="total"><?php echo formatPrice($total); ?></strong>
                        </div>
                        <button class="btn btn-success w-100 btn-lg" onclick="openConfirmationModal()">
                            <i class="fas fa-check me-2"></i>تأكيد الطلب
                        </button>
                        <p class="text-muted text-center mt-3 small">
                            <i class="fas fa-truck me-1"></i>الدفع عند الاستلام
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Customer Info Modal -->
    <div class="modal fade" id="customerInfoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header customer-info-modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>معلومات العميل</h5>
                </div>
                <div class="modal-body customer-info-modal-body">
                    <form id="customerInfoForm">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">الاسم الكامل *</label>
                            <input type="text" class="form-control" id="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">رقم الموبايل *</label>
                            <input type="tel" class="form-control" id="customer_phone" required placeholder="05xxxxxxxx" maxlength="10" minlength="10" pattern="^05[0-9]{8}$" inputmode="numeric">
                        </div>
                        <div class="mb-3">
                            <label for="customer_city" class="form-label">المدينة / المحافظة *</label>
                            <select class="form-select city-select" id="customer_city" required onchange="updateShippingCost()">
                                <option value="">-- اختر المدينة --</option>
                                <option value="طوباس">طوباس</option>
                                <option value="جنين">جنين</option>
                                <option value="طولكرم">طولكرم</option>
                                <option value="نابلس">نابلس</option>
                                <option value="سلفيت">سلفيت</option>
                                <option value="قلقيلية">قلقيلية</option>
                                <option value="رام الله والبيرة">رام الله والبيرة</option>
                                <option value="الخليل">الخليل</option>
                                <option value="أريحا">أريحا</option>
                                <option value="ضواحي القدس">ضواحي القدس</option>
                                <option value="القدس">القدس</option>
                                <option value="الداخل 48">الداخل 48</option>
                            </select>
                            <div id="shippingCostDisplay" class="shipping-cost-display" style="display: none;">
                                <i class="fas fa-truck me-2"></i>
                                رسوم التوصيل: <span id="shippingCostValue"></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="customer_location" class="form-label">عنوان التوصيل *</label>
                            <textarea class="form-control" id="customer_location" rows="3" required placeholder="الشارع، رقم البناية، التفاصيل الإضافية..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-left me-2"></i>التالي
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header confirmation-modal-header">
                    <button type="button" class="btn-close btn-close" onclick="closeConfirmationModal()"></button>
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>الخطوة الأخيرة لتأكيد طلبك</h5>

                </div>
                <div class="modal-body confirmation-modal-body">
                    <!-- تفاصيل الطلب -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="fas fa-shopping-bag me-2"></i>تفاصيل الطلب</h6>
                        <div class="table-responsive">
                            <table class="confirmation-table">
                                <thead>
                                    <tr>
                                        <th>اسم المنتج</th>
                                        <th>السعر</th>
                                        <th>الكمية</th>
                                        <th>المجموع</th>
                                    </tr>
                                </thead>
                                <tbody id="confirmationItemsTable">
                                    <?php
                                    $subtotal = 0;
                                    foreach ($items as $item):
                                        $item_total = $item['effective_price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                    ?>
                                        <tr>
                                            <td title="<?php echo htmlspecialchars($item['name']); ?>">
                                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($item['name']); ?></span>
                                                <span class="d-inline d-md-none"><?php echo htmlspecialchars(mb_substr($item['name'], 0, 15)); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($item['has_discount']): ?>
                                                    <span class="price-original"><?php echo formatPrice($item['price']); ?></span>
                                                    <span class="price-discounted"><?php echo formatPrice($item['effective_price']); ?></span>
                                                <?php else: ?>
                                                    <?php echo formatPrice($item['effective_price']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><strong><?php echo formatPrice($item_total); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- معلومات العميل -->
                    <div class="customer-info-section mb-4">
                        <h6 class="mb-3"><i class="fas fa-user me-2"></i>معلومات العميل</h6>
                        <div class="customer-info-row">
                            <span>الاسم:</span>
                            <strong id="confirmationCustomerName">-</strong>
                        </div>
                        <div class="customer-info-row">
                            <span>رقم الموبايل:</span>
                            <strong id="confirmationCustomerPhone">-</strong>
                        </div>
                        <div class="customer-info-row">
                            <span>المدينة:</span>
                            <strong id="confirmationCustomerCity">-</strong>
                        </div>
                        <div class="customer-info-row">
                            <span>العنوان:</span>
                            <strong id="confirmationCustomerAddress">-</strong>
                        </div>
                    </div>

                    <!-- ملخص الطلب -->
                    <div class="order-summary-section">
                        <h6 class="mb-3"><i class="fas fa-calculator me-2"></i>ملخص الطلب</h6>
                        <div class="summary-row">
                            <span>المجموع الفرعي:</span>
                            <span id="confirmationSubtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>رسوم التوصيل:</span>
                            <span id="confirmationShipping">-</span>
                        </div>
                        <div class="summary-row">
                            <span>الإجمالي النهائي:</span>
                            <span id="confirmationTotal">-</span>
                        </div>
                    </div>

                    <!-- ملاحظات الطلب -->
                    <div class="mb-3 mt-4">
                        <label class="form-label" for="order_notes"><i class="fas fa-sticky-note me-2"></i>ملاحظات الطلب (اختياري)</label>
                        <textarea class="form-control" id="order_notes" rows="3" placeholder="اكتب ملاحظتك هنا إن وجدت"></textarea>
                    </div>

                    <!-- أزرار التحكم -->
                    <div class="d-flex gap-2">
                        <button type="button" class="btn cancel-btn flex-fill" onclick="closeConfirmationModal()">
                            <i class="fas fa-times me-2"></i>إلغاء
                        </button>
                        <button type="button" class="btn confirm-btn flex-fill" onclick="confirmOrder(this)">
                            <i class="fas fa-check-circle me-2"></i>تأكيد الطلب
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Shipping cost calculation based on city
        const shippingCosts = {
            'طوباس': 20,
            'جنين': 20,
            'طولكرم': 20,
            'نابلس': 20,
            'سلفيت': 20,
            'قلقيلية': 20,
            'رام الله والبيرة': 20,
            'الخليل': 20,
            'أريحا': 20,
            'ضواحي القدس': 20,
            'القدس': 30,
            'الداخل 48': 70
        };

        // Get subtotal from PHP
        const cartSubtotal = <?php echo $total; ?>;

        // Update shipping cost when city is selected
        function updateShippingCost() {
            const city = document.getElementById('customer_city').value;
            const shippingDisplay = document.getElementById('shippingCostDisplay');
            const shippingValue = document.getElementById('shippingCostValue');
            const summaryShipping = document.getElementById('summaryShipping');

            if (city && shippingCosts[city]) {
                const baseShipping = shippingCosts[city];
                
                // Progressive Discount Logic: 20 NIS off per 300 NIS
                const discountUnits = Math.floor(cartSubtotal / 300);
                const totalDiscount = discountUnits * 20;

                let finalShipping = baseShipping - totalDiscount;
                let orderDiscount = 0;

                // Handle negative shipping (apply to order total)
                if (finalShipping < 0) {
                    orderDiscount = Math.abs(finalShipping);
                    finalShipping = 0;
                }

                // Display Shipping
                if (finalShipping === 0 && baseShipping > 0) {
                    shippingValue.innerHTML = '<span class="text-success">مجاني</span> <span class="text-decoration-line-through text-muted small">' + baseShipping.toFixed(2) + '</span>';
                    summaryShipping.innerHTML = '<span class="text-success">مجاني</span>';
                } else {
                    shippingValue.textContent = finalShipping.toFixed(2) + ' ₪';
                    summaryShipping.textContent = finalShipping.toFixed(2) + ' ₪';
                }

                shippingDisplay.style.display = 'block';

                // Display Order Discount if exists
                // We add a row for this or just subtract from total
                // For simplicity in this view, we just show the final total
                
                const finalTotal = cartSubtotal + finalShipping - orderDiscount;
                document.getElementById('total').textContent = finalTotal.toFixed(2) + ' ₪';
                
                // If there is an extra order discount, maybe show a hint
                if (orderDiscount > 0) {
                     // Optionally append info about extra discount
                     // Not adding complex DOM elements to keep it simple unless requested
                }

            } else {
                shippingDisplay.style.display = 'none';
                summaryShipping.textContent = '-';
                document.getElementById('total').textContent = cartSubtotal.toFixed(2) + ' ₪';
            }
        }

        // Frontend phone validation (customer)
        (function() {
            const phoneInput = document.getElementById('customer_phone');
            if (!phoneInput) return;

            phoneInput.addEventListener('input', function() {
                const value = this.value.trim();
                const re = /^05[0-9]{8}$/;

                if (value.length === 0) {
                    this.setCustomValidity('');
                } else if (!re.test(value)) {
                    this.setCustomValidity('رقم الموبايل يجب أن يتكون من 10 أرقام ويبدأ بـ 05');
                } else {
                    this.setCustomValidity('');
                }
            });
        })();

        // Cart quantity and remove functions
        $('.update-qty').click(function() {
            let action = $(this).data('action');
            let cartItem = $(this).closest('.cart-item');
            let cartId = cartItem.data('cart-id');
            let qtyInput = cartItem.find('.item-quantity');
            let currentQty = parseInt(qtyInput.val());
            let maxQty = parseInt(qtyInput.attr('max'));
            let newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

            if (newQty >= 1 && newQty <= maxQty) {
                $.post('api/update_cart.php', {
                    cart_id: cartId,
                    quantity: newQty
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }, 'json');
            }
        });

        $('.remove-item').click(function() {
            if (confirm('متأكد إنك بدك تحذف هاد المنتج من السلة؟')) {
                let cartItem = $(this).closest('.cart-item');
                let cartId = cartItem.data('cart-id');

                $.post('api/remove_from_cart.php', {
                    cart_id: cartId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }, 'json');
            }
        });

        // Customer Info Form Handler
        $('#customerInfoForm').submit(function(e) {
            e.preventDefault();

            // Validate customer info
            const customerName = $('#customer_name').val();
            const customerPhone = $('#customer_phone').val();
            const customerCity = $('#customer_city').val();
            const customerLocation = $('#customer_location').val();

            if (!customerName || !customerPhone || !customerCity || !customerLocation) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            // Validate shipping cost
            if (!shippingCosts[customerCity]) {
                alert('يرجى اختيار مدينة صحيحة');
                return;
            }

            // Close customer info modal
            var customerInfoModalEl = document.getElementById('customerInfoModal');
            var customerInfoModal = bootstrap.Modal.getInstance(customerInfoModalEl);
            if (customerInfoModal) {
                customerInfoModal.hide();
            } else {
                $(customerInfoModalEl).modal('hide');
            }

            // Wait for modal to close, then open confirmation modal
            $(customerInfoModalEl).on('hidden.bs.modal', function() {
                openConfirmationModal();
                $(customerInfoModalEl).off('hidden.bs.modal');
            });
        });

        // Open confirmation modal
        function openConfirmationModal() {
            // First check if customer info is filled
            const customerName = $('#customer_name').val();
            const customerPhone = $('#customer_phone').val();
            const customerCity = $('#customer_city').val();
            const customerLocation = $('#customer_location').val();

            if (!customerName || !customerPhone || !customerCity || !customerLocation) {
                // Open customer info modal first
                var customerInfoModalEl = document.getElementById('customerInfoModal');
                var customerInfoModal = bootstrap.Modal.getOrCreateInstance(customerInfoModalEl);
                customerInfoModal.show();
                return;
            }

            // Get shipping cost
            const baseShipping = shippingCosts[customerCity] || 0;
            
            // Progressive Discount Logic
            const discountUnits = Math.floor(cartSubtotal / 300);
            const totalDiscount = discountUnits * 20;

            let finalShipping = baseShipping - totalDiscount;
            let orderDiscount = 0;

            if (finalShipping < 0) {
                orderDiscount = Math.abs(finalShipping);
                finalShipping = 0;
            }
            
            const finalTotal = cartSubtotal + finalShipping - orderDiscount;

            // Update confirmation modal with customer info
            document.getElementById('confirmationCustomerName').textContent = customerName;
            document.getElementById('confirmationCustomerPhone').textContent = customerPhone;
            document.getElementById('confirmationCustomerCity').textContent = customerCity;
            document.getElementById('confirmationCustomerAddress').textContent = customerLocation;

            // Update order summary
             if (finalShipping === 0 && baseShipping > 0) {
                 document.getElementById('confirmationShipping').innerHTML = '<span class="text-success fw-bold">مجاني</span>';
             } else {
                 document.getElementById('confirmationShipping').textContent = finalShipping.toFixed(2) + ' ₪';
             }
            
            // Show any extra discount if applied?
            // For now, implicit in total.
            
            document.getElementById('confirmationTotal').textContent = finalTotal.toFixed(2) + ' ₪';

            // Open confirmation modal
            var confirmationModalEl = document.getElementById('confirmationModal');
            var confirmationModal = bootstrap.Modal.getOrCreateInstance(confirmationModalEl);
            confirmationModal.show();
        }

        // Close confirmation modal
        function closeConfirmationModal() {
            var confirmationModalEl = document.getElementById('confirmationModal');
            var confirmationModal = bootstrap.Modal.getInstance(confirmationModalEl);
            if (confirmationModal) {
                confirmationModal.hide();
            } else {
                $(confirmationModalEl).modal('hide');
            }
        }

        // Confirm order function
        function confirmOrder(btnElement) {
            var customerName = $('#customer_name').val();
            var customerPhone = $('#customer_phone').val();
            var customerCity = $('#customer_city').val();
            var customerLocation = $('#customer_location').val();
            var orderNotes = $('#order_notes').val();

            if (!customerName || !customerPhone || !customerCity || !customerLocation) {
                alert('يرجى ملء جميع المعلومات المطلوبة');
                return;
            }

            // Get shipping cost
            const shipping = shippingCosts[customerCity] || 0;
            const finalTotal = cartSubtotal + shipping;

            // Disable confirm button to prevent double submission
            btnElement.disabled = true;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';

            // Submit order
            $.post('api/place_order.php', {
                customer_name: customerName,
                customer_phone: customerPhone,
                customer_city: customerCity,
                customer_location: customerLocation,
                shipping_cost: shipping,
                final_total: finalTotal,
                order_notes: orderNotes || ''
            }, function(response) {
                if (response.success) {
                    // Close modal
                    closeConfirmationModal();

                    // Show success alert and go to index
                    alert('تم استلام طلبك بنجاح! رقم الطلب: #' + response.order_id);
                    window.location.href = 'index.php';
                } else {
                    alert(response.message || 'حدث خطأ أثناء تأكيد الطلب');
                    btnElement.disabled = false;
                    btnElement.innerHTML = '<i class="fas fa-check-circle me-2"></i>تأكيد الطلب';
                }
            }, 'json').fail(function() {
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى');
                btnElement.disabled = false;
                btnElement.innerHTML = '<i class="fas fa-check-circle me-2"></i>تأكيد الطلب';
            });
        }
    </script>
</body>

</html>