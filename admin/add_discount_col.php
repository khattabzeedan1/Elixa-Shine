<?php
require_once '../config.php';

// Check if column exists
$check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'discount'");
if (mysqli_num_rows($check) == 0) {
    // Add column
    $sql = "ALTER TABLE orders ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount";
    if (mysqli_query($conn, $sql)) {
        echo "Successfully added 'discount' column to orders table.";
    } else {
        echo "Error adding column: " . mysqli_error($conn);
    }
} else {
    echo "Column 'discount' already exists.";
}
?>
