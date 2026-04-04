<?php
require_once '../config.php';

if (isset($_GET['id'])) {
    $product_id = (int) cleanInput($_GET['id']);
    
    $query = "SELECT p.*, c.name as category_name,
              (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = $product_id AND p.is_active = 1";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        ?>
        <div class="row">
            <div class="col-md-6">
                <?php if ($product['image']): ?>
                    <img src="<?php echo BASE_URL . $product['image']; ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div class="bg-light text-center p-5 rounded">
                        <i class="fas fa-box fa-5x text-muted opacity-50"></i>
                        <p class="text-muted mt-3">لا توجد صورة</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h3 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="text-muted mb-4">
                    <i class="fas fa-folder me-2"></i>
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </p>
                
                <?php if ($product['description']): ?>
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">الوصف:</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="bg-light p-3 rounded mb-4">
                    <h4 class="text-success mb-0">
                        <i class="fas fa-tag me-2"></i>
                        <?php echo formatPrice($product['price']); ?>
                    </h4>
                </div>
                
                <div class="d-grid gap-2">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <button class="btn btn-success btn-lg" onclick="addToCartFromModal(<?php echo $product['id']; ?>, this)">
                            <i class="fas fa-cart-plus me-2"></i>ضيف للسلة
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-times me-2"></i>خلص من المخزن
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <script>
            function addToCartFromModal(productId, btnElement) {
                // Save original text
                let originalText = $(btnElement).html();
                
                // Disable button and show spinner
                $(btnElement).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الإضافة...');

                $.post('api/add_to_cart.php', { product_id: productId }, function(response) {
                    if (response.success) {
                        // Refresh global cart count
                        if (typeof refreshCartCount === 'function') {
                            refreshCartCount();
                        }
                        
                        // Show success feedback
                        $(btnElement).removeClass('btn-success').addClass('btn-info')
                            .html('<i class="fas fa-check me-2"></i>تمت الإضافة');
                        
                        // Revert button after 1.5 seconds
                        setTimeout(function() {
                            $(btnElement).removeClass('btn-info').addClass('btn-success')
                                .prop('disabled', false).html(originalText);
                        }, 1500);
                        
                    } else {
                        alert(response.message);
                        $(btnElement).prop('disabled', false).html(originalText);
                    }
                }, 'json').fail(function() {
                    alert('حدث خطأ في الاتصال');
                    $(btnElement).prop('disabled', false).html(originalText);
                });
            }
        </script>
        <?php
    } else {
        echo '<div class="alert alert-danger">المنتج مش موجود</div>';
    }
} else {
    echo '<div class="alert alert-danger">رقم المنتج مطلوب</div>';
}
?>