<?php
require_once '../config.php';

$result = mysqli_query($conn, "SHOW CREATE TABLE profits");
$row = mysqli_fetch_assoc($result);
echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
?>
