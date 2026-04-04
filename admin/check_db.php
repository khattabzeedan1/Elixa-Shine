<?php
require_once '../config.php';

echo "<h2>Database Check</h2>";

// 1. Check orders table for 'discount' column
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'discount'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color:green'>[OK] Column 'discount' exists in 'orders' table.</p>";
} else {
    echo "<p style='color:red'>[MISSING] Column 'discount' does NOT exist in 'orders' table. Please run the migration.</p>";
}

// 2. Check profits table constraints
echo "<h3>Profits Table Constraints:</h3>";
// Try to insert a dummy negative profit record to see if it fails (due to FK)
// We use a transaction to roll it back immediately
mysqli_begin_transaction($conn);
try {
    $test_query = "INSERT INTO profits (order_id, product_id, product_name, quantity_sold, purchase_price, selling_price, profit) 
                   VALUES (1, 0, 'Test Discount', 1, 0, -20, -20)";
    if (mysqli_query($conn, $test_query)) {
        echo "<p style='color:green'>[OK] Profits table accepts product_id=0 (No strict FK blocking discounts).</p>";
    } else {
        echo "<p style='color:red'>[ERROR] Cannot insert discount record: " . mysqli_error($conn) . "</p>";
        echo "<p>If the error mentions a foreign key constraint, we need to drop that constraint.</p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
mysqli_rollback($conn); // Undo the test

?>
