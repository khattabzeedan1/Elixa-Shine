<?php
require_once '../config.php';

header('Content-Type: application/json');

$parent_id = $_GET['parent_id'] ?? 'all';

// إذا كان المطلوب كل الأقسام الرئيسية
if ($parent_id === 'all' || $parent_id === null || $parent_id === '') {
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
              CASE WHEN EXISTS (SELECT 1 FROM categories WHERE parent_id = c.id) THEN 1 ELSE 0 END as has_children
              FROM categories c 
              WHERE c.parent_id IS NULL 
              ORDER BY c.name ASC";
} else {
    // جلب الأقسام الفرعية للقسم المحدد
    $parent_id = intval($parent_id);
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
              CASE WHEN EXISTS (SELECT 1 FROM categories WHERE parent_id = c.id) THEN 1 ELSE 0 END as has_children
              FROM categories c 
              WHERE c.parent_id = $parent_id 
              ORDER BY c.name ASC";
}

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات'
    ]);
    exit;
}

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'parent_id' => $row['parent_id'],
        'children_count' => intval($row['children_count']),
        'has_children' => intval($row['has_children'])
    ];
}

echo json_encode([
    'success' => true,
    'libraries' => $categories, // Keep 'libraries' key for frontend compatibility if needed, or update frontend to 'categories'
    'categories' => $categories,
    'parent_id' => $parent_id
]);
?>