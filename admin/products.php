<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

function generateSlug($string) {
    $slug = trim($string);
    $slug = mb_strtolower($slug, 'UTF-8');
    // Replace non-letter/number characters with hyphen
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
    // Trim hyphens
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'product-' . time();
    }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $category_id = cleanInput($_POST['category_id']);
            $name = cleanInput($_POST['name']);
            $slug = generateSlug($name) . '-' . uniqid(); // Ensure uniqueness
            $description = cleanInput($_POST['description']);
            $price = cleanInput($_POST['price']);
            $cost_price = cleanInput($_POST['cost_price']);
            $stock_quantity = cleanInput($_POST['stock_quantity']);
            
            $check_query = "SELECT id FROM products WHERE name = ?";
            $check_exec = executeQuery($conn, $check_query, "s", [$name]);

            if (mysqli_num_rows($check_exec['result']) > 0) {
                $error = 'المنتج موجود مسبقاً! اختار اسم ثاني.';
            } else {
                $insert_query = "INSERT INTO products (category_id, name, slug, description, price, cost_price, stock_quantity) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    (int)$category_id, 
                    $name, 
                    $slug,
                    $description, 
                    (float)$price, 
                    (float)$cost_price, 
                    (int)$stock_quantity
                ];
                $types = "isssddi";

                $insertResult = executeQuery($conn, $insert_query, $types, $params);
                if ($insertResult) {
                    $new_product_id = $insertResult['insert_id'];
                    
                    // Handle image upload
                    if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $image_url = uploadImage($_FILES['image']);
                        if ($image_url) {
                            $img_query = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)";
                            executeQuery($conn, $img_query, "is", [$new_product_id, $image_url]);
                        }
                    }
                    
                    $success = 'تم إضافة المنتج بنجاح';
                } else {
                    $error = 'فشل في إضافة المنتج';
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $category_id = (int)$_POST['category_id'];
            $name = cleanInput($_POST['name']);
            $description = cleanInput($_POST['description']);
            $price = (float)$_POST['price'];
            $cost_price = (float)$_POST['cost_price'];
            $stock_quantity = (int)$_POST['stock_quantity'];

            $check_query = "SELECT id FROM products WHERE name = ? AND id != ?";
            $check_exec = executeQuery($conn, $check_query, "si", [$name, $id]);

            if (mysqli_num_rows($check_exec['result']) > 0) {
                $error = 'اسم المنتج موجود مسبقاً!';
            } else {
                
                // Upload new image if exists
                if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $image_url = uploadImage($_FILES['image']);
                    if ($image_url) {
                        // delete old primary image maybe?
                        $old_img_q = "SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1";
                        $old_img_r = executeQuery($conn, $old_img_q, "i", [$id]);
                        if ($old = mysqli_fetch_assoc($old_img_r['result'])) {
                            deleteImage($old['image_url']);
                            executeQuery($conn, "DELETE FROM product_images WHERE product_id = ?", "i", [$id]);
                        }
                        
                        $img_query = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)";
                        executeQuery($conn, $img_query, "is", [$id, $image_url]);
                    }
                }

                $update_query = "UPDATE products SET 
                                category_id = ?,
                                name = ?,
                                description = ?,
                                price = ?,
                                cost_price = ?,
                                stock_quantity = ?
                                WHERE id = ?";

                $params = [
                    $category_id, 
                    $name, 
                    $description, 
                    $price, 
                    $cost_price, 
                    $stock_quantity, 
                    $id
                ];
                $types = "issddii";

                if (executeQuery($conn, $update_query, $types, $params)) {
                    $success = 'تم تحديث المنتج بنجاح';
                } else {
                    $error = 'فشل في تحديث المنتج';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            // images get deleted by CASCADE from database but physical files won't
            $image_query = "SELECT image_url FROM product_images WHERE product_id = ?";
            $image_exec = executeQuery($conn, $image_query, "i", [$id]);
            
            $delete_query = "DELETE FROM products WHERE id = ?";

            if (executeQuery($conn, $delete_query, "i", [$id])) {
                while ($image_data = mysqli_fetch_assoc($image_exec['result'])) {
                    deleteImage($image_data['image_url']);
                }
                $success = 'تم حذف المنتج بنجاح';
            } else {
                $error = 'فشل في حذف المنتج';
            }
        }
    }
}

$products_query = "SELECT p.*, c.name as category_name, 
                   (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC";
$products_result = mysqli_query($conn, $products_query);

$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-right: 280px;
            padding: 30px;
            overflow-y: auto;
            height: 100vh;
        }

        /* تحسينات للآيباد */
        @media (min-width: 768px) and (max-width: 1024px) {
            .modal-dialog {
                max-width: 90%;
            }

            .form-control,
            .form-select {
                min-height: 45px;
                font-size: 16px;
            }

            .price-inputs-row {
                display: flex;
                gap: 15px;
                align-items: flex-start;
            }

            .price-inputs-row>div {
                flex: 1;
            }
        }

        /* تنسيق عام أفضل */
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #00bcd4;
            box-shadow: 0 0 0 0.2rem rgba(0, 188, 212, 0.25);
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h2><i class="fas fa-box me-2"></i>إدارة المنتجات</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>إضافة منتج
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>الصورة</th>
                                    <th>اسم المنتج</th>
                                    <th>القسم</th>
                                    <th>السعر</th>
                                    <th>سعر التكلفة</th>
                                    <th>الكمية</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($products_result) > 0): ?>
                                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                        <tr>
                                            <td>
                                                <?php if ($product['image']): ?>
                                                    <img src="<?php echo  BASE_URL . $product['image']; ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category_name']); ?></span></td>
                                            <td>
                                                <strong class="text-success"><?php echo formatPrice($product['price']); ?></strong>
                                            </td>
                                            <td><?php echo formatPrice($product['cost_price']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('متأكد من الحذف؟')">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                            <p>ما في منتجات. ضيف منتج جديد!</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نموذج إضافة منتج -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white;">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>إضافة منتج جديد</h5>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="add">

                        <!-- التصنيف -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">القسم *</label>
                            <select id="category_id" class="form-select" name="category_id" required>
                                <option value="">اختار القسم</option>
                                <?php
                                mysqli_data_seek($categories_result, 0);
                                while ($cat = mysqli_fetch_assoc($categories_result)):
                                ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- اسم المنتج -->
                        <div class="mb-3">
                            <label for="product_name" class="form-label">اسم المنتج *</label>
                            <input type="text" id="product_name" class="form-control" name="name" autocomplete="name" required>
                        </div>

                        <!-- الوصف -->
                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea id="description" class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <!-- الأسعار والكمية -->
                        <div class="row price-inputs-row mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">سعر البيع (₪) *</label>
                                <input type="number" id="price" class="form-control" name="price" step="0.01" required>
                            </div>

                            <div class="col-md-4">
                                <label for="cost_price" class="form-label">سعر التكلفة (₪) *</label>
                                <input type="number" id="cost_price" class="form-control" name="cost_price" step="0.01" required>
                            </div>

                            <div class="col-md-4">
                                <label for="stock_quantity" class="form-label">الكمية *</label>
                                <input type="number" id="stock_quantity" class="form-control" name="stock_quantity" required>
                            </div>
                        </div>

                        <!-- الصورة -->
                        <div class="mb-3">
                            <label for="image" class="form-label">صورة المنتج</label>
                            <input type="file" id="image" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج تعديل منتج -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل المنتج</h5>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_product_id">

                        <div class="mb-3">
                            <label for="edit_category_id" class="form-label">القسم *</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <?php
                                mysqli_data_seek($categories_result, 0);
                                while ($cat = mysqli_fetch_assoc($categories_result)):
                                ?>
                                    <option value="<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">اسم المنتج *</label>
                            <input type="text" class="form-control" name="name" id="edit_name" autocomplete="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_description" class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>

                        <div class="row price-inputs-row mb-3">
                            <div class="col-md-4">
                                <label for="edit_price" class="form-label">سعر البيع (₪) *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>

                            <div class="col-md-4">
                                <label for="edit_cost_price" class="form-label">سعر التكلفة (₪) *</label>
                                <input type="number" class="form-control" name="cost_price" id="edit_cost_price" step="0.01" required>
                            </div>

                            <div class="col-md-4">
                                <label for="edit_stock_quantity" class="form-label">الكمية *</label>
                                <input type="number" class="form-control" name="stock_quantity" id="edit_stock_quantity" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="form-label fw-semibold mb-2">الصورة الحالية</p>
                            <div id="current_product_image"></div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_image" class="form-label">تغيير الصورة</label>
                            <input type="file" class="form-control" name="image" id="edit_image" accept="image/*">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>تحديث
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_cost_price').value = product.cost_price;
            document.getElementById('edit_stock_quantity').value = product.stock_quantity;

            const imagePreview = document.getElementById('current_product_image');
            if (product.image) {
                imagePreview.innerHTML =
                    '<img src="<?php echo BASE_URL; ?>' + product.image + '" class="img-thumbnail" style="max-height:150px;">';
            } else {
                imagePreview.innerHTML = '<p class="text-muted">لا توجد صورة</p>';
            }

            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }
    </script>
</body>

</html>