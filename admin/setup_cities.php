<?php
require_once '../config.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$tableName = "cities";
$dropTable = false; // Set to true if you want to recreate the table

if ($dropTable) {
    mysqli_query($conn, "DROP TABLE IF EXISTS $tableName");
}

// 1. Create Cities Table
$createTableQuery = "CREATE TABLE IF NOT EXISTS $tableName (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    region_type ENUM('WB', 'Jerusalem_Suburbs', 'Jerusalem', 'Inner_48') NOT NULL, 
    -- WB = الضفة الغربية, Jerusalem_Suburbs = ضواحي القدس
    shipping_cost DECIMAL(10, 2) NOT NULL DEFAULT 20.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (mysqli_query($conn, $createTableQuery)) {
    echo "Table '$tableName' created or already exists.<br>";
} else {
    die("Error creating table: " . mysqli_error($conn));
}

// 2. Initial Data
// Regions: 
// WB (West Bank) -> 20 NIS (Free if > 300)
// Jerusalem_Suburbs -> 20 NIS (Free if > 300)
// Jerusalem -> 30 NIS (No Discount)
// Inner_48 -> 70 NIS (No Discount)

$cities = [
    // West Bank (WB)
    ['طوباس', 'WB', 20],
    ['جنين', 'WB', 20],
    ['طولكرم', 'WB', 20],
    ['نابلس', 'WB', 20],
    ['سلفيت', 'WB', 20],
    ['قلقيلية', 'WB', 20],
    ['رام الله والبيرة', 'WB', 20],
    ['الخليل', 'WB', 20],
    ['أريحا', 'WB', 20],
    // Jerusalem Suburbs
    ['ضواحي القدس', 'Jerusalem_Suburbs', 20],
    // Jerusalem
    ['ال القدس', 'Jerusalem', 30],
    ['القدس', 'Jerusalem', 30], // duplicate handling just in case
    // Inner 48
    ['الداخل 48', 'Inner_48', 70]
];

// 3. Insert Data
echo "Inserting/Updating cities...<br>";
foreach ($cities as $city) {
    $name = mysqli_real_escape_string($conn, $city[0]);
    $region = $city[1];
    $cost = $city[2];

    // Using ON DUPLICATE KEY UPDATE to handle re-runs without errors
    $sql = "INSERT INTO $tableName (name, region_type, shipping_cost) 
            VALUES ('$name', '$region', $cost) 
            ON DUPLICATE KEY UPDATE region_type='$region', shipping_cost=$cost";
            
    if (!mysqli_query($conn, $sql)) {
        echo "Error inserting $name: " . mysqli_error($conn) . "<br>";
    }
}

echo "City setup completed successfully.";
?>
