<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = (int) cleanInput($_POST['cart_id']);
    $session_id = $_SESSION['cart_session_id'];
    
    // Verify this cart item belongs to this session
    $verify_query = "SELECT * FROM cart_items WHERE id = $cart_id AND session_id = '$session_id'";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (mysqli_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'العنصر مش موجود']);
        exit();
    }
    
    $delete_query = "DELETE FROM cart_items WHERE id = $cart_id AND session_id = '$session_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        echo json_encode(['success' => true, 'message' => 'تم حذف المنتج من السلة']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل الحذف']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
}