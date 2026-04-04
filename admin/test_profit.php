<?php
require_once '../config.php';

echo "<h2>Testing Profit Logic</h2>";
echo "1. Creating Test Order...<br>";

// 1. Create a dummy order
$name = "Test User " . rand(1000,9999);
$total = 500;
$discount = 20;
$sql = "INSERT INTO orders (customer_name, phone, location, total_amount, status, discount) 
        VALUES ('$name', '0599000000', 'Test', $total, 'pending', $discount)";
        
if (mysqli_query($conn, $sql)) {
    $order_id = mysqli_insert_id($conn);
    echo "Order Created: ID #$order_id (Discount: $discount)<br>";
    
    // 2. Add Item
    echo "2. Adding Order Item...<br>";
    // Assuming product ID 1 exists, if not finding one
    $res = mysqli_query($conn, "SELECT id, name, price, purchase_price FROM products LIMIT 1");
    $prod = mysqli_fetch_assoc($res);
    if ($prod) {
        $pid = $prod['id'];
        $pname = $prod['name'];
        $price = $prod['price'];
        $cost = $prod['purchase_price'];
        
        mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, purchase_price) 
                            VALUES ($order_id, $pid, '$pname', 1, $price, $cost)");
        echo "Item Added: $pname (Price: $price, Cost: $cost)<br>";
    }
    
    // 3. Simulate Marking as Delivered
    echo "3. Changing Status to Delivered (Triggering Logic)...<br>";
    
    // -- Logic from admin/orders.php --
    // Fetch info
    $current_query = "SELECT * FROM orders WHERE id = $order_id";
    $current_result = mysqli_query($conn, $current_query);
    $current_data = mysqli_fetch_assoc($current_result);
    $order_discount = isset($current_data['discount']) ? (float)$current_data['discount'] : 0.0;
    
    // Record Item Profit
    $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
    $items_result = mysqli_query($conn, $items_query);
    while ($item = mysqli_fetch_assoc($items_result)) {
        recordProfit($order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['purchase_price'], $item['price']);
        echo "Recorded Item Profit.<br>";
    }
    
    // Record Discount
    if ($order_discount > 0) {
        echo "Attempting to Record Discount: $order_discount...<br>";
        $res = recordProfit($order_id, 0, 'خصم الطلب (Order Discount)', 1, 0, -$order_discount);
        if ($res) {
            echo "<b>SUCCESS: Recorded Negative Profit for Discount.</b><br>";
        } else {
            echo "<b>FAILED: " . mysqli_error($conn) . "</b><br>";
        }
    }
    // -- End Logic --
    
    // 4. Verify in Database
    echo "4. Verifying Profits Table...<br>";
    $chk = mysqli_query($conn, "SELECT * FROM profits WHERE order_id = $order_id");
    while ($row = mysqli_fetch_assoc($chk)) {
        echo "Row: Product='{$row['product_name']}' | Profit='{$row['profit']}'<br>";
    }
    
} else {
    echo "Failed to create order: " . mysqli_error($conn);
}
?>
