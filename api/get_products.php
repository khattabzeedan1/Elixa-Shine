<?php
require_once '../config.php';

header('Content-Type: application/json');

$category_id = $_GET['library_id'] ?? $_GET['category_id'] ?? 'all';

// دالة للحصول على كل الأقسام الفرعية بشكل تكراري
function getAllSubCategories($parent_id, $conn) {
    $ids = [$parent_id];
    
    $query = "SELECT id FROM categories WHERE parent_id = " . (int)$parent_id;
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $sub_ids = getAllSubCategories($row['id'], $conn);
        $ids = array_merge($ids, $sub_ids);
    }
    
    return $ids;
}

// جلب المنتجات
if ($category_id === 'all') {
    // جلب كل المنتجات
    $query = "SELECT p.*, c.name as category_name,
              (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.stock_quantity > 0 AND p.is_active = 1
              ORDER BY p.created_at DESC";
    $title = "كل المنتجات";
    $breadcrumb = [];
} else {
    $category_id = (int)$category_id;
    
    // الحصول على القسم الحالي ومعلوماته
    $category_query = "SELECT * FROM categories WHERE id = $category_id";
    $category_result = mysqli_query($conn, $category_query);
    $category = mysqli_fetch_assoc($category_result);
    
    if (!$category) {
        echo json_encode([
            'success' => false,
            'message' => 'القسم غير موجود'
        ]);
        exit;
    }
    
    // الحصول على جميع الأقسام الفرعية (تكراري)
    $all_category_ids = getAllSubCategories($category_id, $conn);
    $ids_string = implode(',', $all_category_ids);
    
    // جلب المنتجات من القسم الحالي وجميع الأقسام الفرعية
    $query = "SELECT p.*, c.name as category_name,
              (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.category_id IN ($ids_string) 
              AND p.stock_quantity > 0 AND p.is_active = 1
              ORDER BY p.created_at DESC";
    
    $title = $category['name'];
    
    // بناء breadcrumb
    $breadcrumb = [];
    $current_id = $category_id;
    
    while ($current_id) {
        $bc_query = "SELECT id, name, parent_id FROM categories WHERE id = $current_id";
        $bc_result = mysqli_query($conn, $bc_query);
        $bc_item = mysqli_fetch_assoc($bc_result);
        
        if ($bc_item) {
            array_unshift($breadcrumb, [
                'id' => $bc_item['id'],
                'name' => $bc_item['name']
            ]);
            $current_id = $bc_item['parent_id'];
        } else {
            break;
        }
    }
}

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات'
    ]);
    exit;
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    // In our new schema, we don't have discount_price directly, but frontend expects it.
    // We'll set it to null and let effective_price handle it.
    $effective_price = $row['price'];
    $has_discount = false;
    
    $products[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'price' => $row['price'],
        'discount_price' => null,
        'effective_price' => $effective_price,
        'has_discount' => $has_discount,
        'discount_percentage' => 0,
        'quantity' => $row['stock_quantity'],
        'image' => $row['image'],
        'library_name' => $row['category_name'], // For frontend compatibility
        'category_name' => $row['category_name']
    ];
}

echo json_encode([
    'success' => true,
    'products' => $products,
    'title' => $title,
    'breadcrumb' => $breadcrumb,
    'library_id' => $category_id
]);
?>