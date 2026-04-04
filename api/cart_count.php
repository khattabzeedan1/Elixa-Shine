<?php
require_once '../config.php';
header('Content-Type: application/json');

$count = getCartCount();

echo json_encode([
    'success' => true,
    'count' => $count
]);
?>