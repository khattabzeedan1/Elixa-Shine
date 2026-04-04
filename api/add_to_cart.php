<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) cleanInput($_POST['product_id']);
    $session_id = $_SESSION['cart_session_id'];
    
    // Check if product exists and has stock
    $product_query = "SELECT * FROM products WHERE id = $product_id";
    $product_result = mysqli_query($conn, $product_query);
    
    if (mysqli_num_rows($product_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'المنتج مش موجود']);
        exit();
    }
    
    $product = mysqli_fetch_assoc($product_result);
    
    if ($product['stock_quantity'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'المنتج خلص من المخزن']);
        exit();
    }
    
    // Check if product already in cart
    $cart_check = "SELECT * FROM cart_items WHERE session_id = '$session_id' AND product_id = $product_id";
    $cart_result = mysqli_query($conn, $cart_check);
    
    if (mysqli_num_rows($cart_result) > 0) {
        // Update quantity
        $cart_item = mysqli_fetch_assoc($cart_result);
        $new_quantity = $cart_item['quantity'] + 1;
        
        if ($new_quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'ما في كمية كافية بالمخزن']);
            exit();
        }
        
        $update_query = "UPDATE cart_items SET quantity = $new_quantity WHERE id = {$cart_item['id']}";
        mysqli_query($conn, $update_query);
    } else {
        // Add new item
        $insert_query = "INSERT INTO cart_items (session_id, product_id, quantity) 
                        VALUES ('$session_id', $product_id, 1)";
        mysqli_query($conn, $insert_query);
    }
    
    echo json_encode(['success' => true, 'message' => 'تم إضافة المنتج للسلة']);
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
}
?>