<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int) cleanInput($_POST['cart_id']);
    $quantity = (int) cleanInput($_POST['quantity']);
    $session_id = $_SESSION['cart_session_id'];
    
    // Get cart item and verify ownership (IDOR Fix)
    $cart_query = "SELECT c.*, p.stock_quantity as stock FROM cart_items c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.id = $cart_id AND c.session_id = '$session_id'";
    $cart_result = mysqli_query($conn, $cart_query);
    
    if (mysqli_num_rows($cart_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'العنصر مش موجود بالسلة أو لا تملك صلاحية تعديله']);
        exit();
    }
    
    $cart_item = mysqli_fetch_assoc($cart_result);
    
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'الكمية المطلوبة أكبر من المتوفر']);
        exit();
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'الكمية لازم تكون أكبر من صفر']);
        exit();
    }
    
    // Ensure we only update the item belonging to this session
    $update_query = "UPDATE cart_items SET quantity = $quantity WHERE id = $cart_id AND session_id = '$session_id'";
    
    if (mysqli_query($conn, $update_query)) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث الكمية']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل التحديث']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
}
?>