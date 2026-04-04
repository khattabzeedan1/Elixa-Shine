<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $name = cleanInput($_POST['name']);
            $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? cleanInput($_POST['parent_id']) : 'NULL';
            $description = cleanInput($_POST['description'] ?? '');

            $check_query = "SELECT id FROM categories WHERE name = '$name'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = 'القسم موجود مسبقاً! اختار اسم ثاني.';
            } else {
                $insert_query = "INSERT INTO categories (name, parent_id, description) 
                                VALUES ('$name', $parent_id, '$description')";

                if (mysqli_query($conn, $insert_query)) {
                    $success = 'تم إضافة القسم بنجاح';
                } else {
                    $error = 'فشل في إضافة القسم';
                }
            }
        } elseif ($action === 'edit') {
            $id = cleanInput($_POST['id']);
            $name = cleanInput($_POST['name']);
            $description = cleanInput($_POST['description'] ?? '');

            $check_query = "SELECT id FROM categories WHERE name = '$name' AND id != $id";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = 'اسم القسم موجود مسبقاً!';
            } else {
                $update_query = "UPDATE categories SET name = '$name', description = '$description' WHERE id = $id";

                if (mysqli_query($conn, $update_query)) {
                    $success = 'تم تحديث القسم بنجاح';
                } else {
                    $error = 'فشل في تحديث القسم';
                }
            }
        } elseif ($action === 'delete') {
            $id = cleanInput($_POST['id']);

            $delete_query = "DELETE FROM categories WHERE id = $id";

            if (mysqli_query($conn, $delete_query)) {
                $success = 'تم حذف القسم بنجاح';
            } else {
                $error = 'فشل في الحذف. تأكد من عدم وجود منتجات مرتبطة بهذا القسم.';
            }
        }
    }
}

$categories_query = "SELECT c.*, 
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count,
                    (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) as sub_count,
                    parent.name as parent_name
                    FROM categories c 
                    LEFT JOIN categories parent ON c.parent_id = parent.id
                    ORDER BY c.parent_id, c.name";
$categories_result = mysqli_query($conn, $categories_query);

// جلب جميع الأقسام
$all_categories_query = "SELECT id, name, parent_id FROM categories ORDER BY name";
$all_categories_result = mysqli_query($conn, $all_categories_query);
$all_categories = [];
while ($cat = mysqli_fetch_assoc($all_categories_result)) {
    $all_categories[] = $cat;
}

// Function to build tree visually
function buildCategoryTree($elements, $parentId = null, $depth = 0) {
    $branch = array();
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $element['level'] = $depth;
            $branch[] = $element;
            $children = buildCategoryTree($elements, $element['id'], $depth + 1);
            if ($children) {
                $branch = array_merge($branch, $children);
            }
        }
    }
    return $branch;
}
$categoryTree = buildCategoryTree($all_categories);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأقسام - <?php echo SITE_NAME; ?></title>
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
                <h2><i class="fas fa-tags me-2"></i>إدارة الأقسام</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>إضافة قسم
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
                                    <th>اسم القسم</th>
                                    <th>القسم الأم</th>
                                    <th>عدد المنتجات</th>
                                    <th>أقسام فرعية</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)):
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '-'; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $category['product_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $category['sub_count']; ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning"
                                                onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('متأكد من الحذف؟')">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal إضافة قسم -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>إضافة قسم جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">اسم القسم *</label>
                            <input type="text" class="form-control" name="name" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">وصف القسم (اختياري)</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">القسم الأم (اختياري)</label>
                            <select class="form-select" name="parent_id">
                                <option value="">بدون - قسم رئيسي</option>
                                <?php foreach ($categoryTree as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo str_repeat('—', $cat['level']); ?>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">إذا كان قسم فرعي، اختار القسم الأم</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal تعديل قسم -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>تعديل القسم
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_category_id">
                        <div class="mb-3">
                            <label class="form-label">اسم القسم *</label>
                            <input type="text" class="form-control" name="name" id="edit_category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">وصف القسم (اختياري)</label>
                            <textarea class="form-control" name="description" id="edit_category_description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>تحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.name;
            document.getElementById('edit_category_description').value = category.description || '';

            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
    </script>
</body>
</html>
