<?php
require_once '../config.php';
header('Content-Type: application/json');

function getCityInfo($conn, $cityName) {
    if (empty($cityName)) return null;
    $sql = "SELECT * FROM cities WHERE name LIKE ? LIMIT 1";
    $param = "%" . $cityName . "%";
    try {
        $exec = executeQuery($conn, $sql, "s", [$param]);
        if ($row = mysqli_fetch_assoc($exec['result'])) {
            return $row;
        }
    } catch (Exception $e) { }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $customer_name = cleanInput($_POST['customer_name']);
        $customer_phone = cleanInput($_POST['customer_phone']);
        $customer_city = isset($_POST['customer_city']) ? cleanInput($_POST['customer_city']) : '';
        $customer_location = cleanInput($_POST['customer_location']);
        $order_notes = isset($_POST['order_notes']) ? cleanInput($_POST['order_notes']) : '';

        if (empty($customer_name) || empty($customer_phone) || empty($customer_city) || empty($customer_location)) {
            throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
        }
        
        $normalized_phone = normalizePhoneForWhatsApp($customer_phone);
        if (!$normalized_phone) {
            throw new Exception('رقم الهاتف غير صحيح');
        }
        $customer_phone = $normalized_phone;

        // Get Cart Items
        $cart_items = getCartItems();
        $items = [];
        $subtotal = 0;
        $total_cost = 0;

        while ($item = mysqli_fetch_assoc($cart_items)) {
            $items[] = $item;
            $subtotal += $item['price'] * $item['quantity'];
            $total_cost += $item['cost_price'] * $item['quantity'];
        }

        if (empty($items)) {
            throw new Exception('السلة فاضية');
        }

        // Get Shipping Cost
        $cityInfo = getCityInfo($conn, $customer_city);
        if (!$cityInfo) {
            throw new Exception('المدينة المختارة غير مدعومة حالياً');
        }

        $base_shipping = floatval($cityInfo['shipping_fee']);
        $city_id = $cityInfo['id'];
        
        $final_shipping_fee = $base_shipping;
        $total_revenue = $subtotal + $final_shipping_fee;
        
        // Append notes to address
        $shipping_address = $customer_location;
        if (!empty($order_notes)) {
            $shipping_address .= "\nملاحظات: " . $order_notes;
        }

        mysqli_begin_transaction($conn);

        // 1. Create Guest User
        $name_parts = explode(' ', $customer_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        // Create unique email for guest
        $guest_email = "guest_" . uniqid() . "@example.com";
        $password_hash = password_hash(uniqid(), PASSWORD_DEFAULT);

        // Check if phone already exists? 
        $user_id = null;
        $check_phone = executeQuery($conn, "SELECT id FROM users WHERE phone = ?", "s", [$customer_phone]);
        if ($row = mysqli_fetch_assoc($check_phone['result'])) {
            $user_id = $row['id'];
        } else {
            $u_sql = "INSERT INTO users (first_name, last_name, email, password_hash, phone, city_id, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')";
            $u_res = executeQuery($conn, $u_sql, "sssssi", [$first_name, $last_name, $guest_email, $password_hash, $customer_phone, $city_id]);
            $user_id = $u_res['insert_id'];
        }

        // 2. Insert Order
        $sql_order = "INSERT INTO orders (user_id, city_id, shipping_address, total_revenue, total_cost, shipping_fee, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $orderRes = executeQuery($conn, $sql_order, "iisddd", [$user_id, $city_id, $shipping_address, $total_revenue, $total_cost, $final_shipping_fee]);
        $order_id = $orderRes['insert_id'];

        // 3. Process Items
        foreach ($items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                throw new Exception('المنتج ' . $item['name'] . ' ما في منه كمية كافية');
            }

            // Insert Order Item
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, unit_cost) VALUES (?, ?, ?, ?, ?)";
            executeQuery($conn, $sql_item, "iiidd", [
                $order_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price'], 
                $item['cost_price']
            ]);

            // Deduct Stock
            $sql_update = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
            $updateRes = executeQuery($conn, $sql_update, "iii", [
                $item['quantity'], 
                $item['product_id'],
                $item['quantity']
            ]);

            if ($updateRes['affected_rows'] === 0) {
                throw new Exception("للأسف، الكمية المطلوبة من المنتج '{$item['name']}' نفذت الآن");
            }
        }

        clearCart();
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'order_id' => $order_id]);

    } catch (Exception $e) {
        if (isset($conn)) mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>