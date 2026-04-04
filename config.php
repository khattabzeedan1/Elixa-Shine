<?php
// config.php - Updated Configuration with Unified Uploads Folder
session_start();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'rootroot');
define('DB_NAME', 'ecommerce_db');

// Create connection with UTF8MB4 support
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error());
}

// Set charset to utf8mb4 for Arabic support
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['SERVER_PORT'] == 8000) {
    define('BASE_URL', 'http://localhost:8000/');
} else {
    define('BASE_URL', 'http://localhost/alexa/');
}

define('SITE_NAME', 'Elixa Shine');

// FILESYSTEM (للرفع والحذف)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('PRODUCTS_UPLOAD_DIR', UPLOAD_DIR . 'products/');

// URL (للعرض)
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('PRODUCTS_UPLOAD_URL', UPLOAD_URL . 'products/');

// إنشاء المجلد
if (!file_exists(PRODUCTS_UPLOAD_DIR)) {
    mkdir(PRODUCTS_UPLOAD_DIR, 0755, true);
}



// Initialize cart session
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = uniqid('cart_', true);
}

// ============================================
// ADMIN AUTHENTICATION
// ============================================

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit();
    }
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF Validation Failed. Please refresh the page and try again.');
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return mysqli_real_escape_string($conn, $data);
}

// Global helper for prepared statements
function executeQuery($conn, $sql, $types = null, $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Database Error: " . mysqli_error($conn));
    }
    
    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Query Failed: " . mysqli_stmt_error($stmt));
    }
    
    // For SELECT queries
    $result = mysqli_stmt_get_result($stmt);
    
    // For INSERT/UPDATE/DELETE
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    $insert_id = mysqli_stmt_insert_id($stmt);
    
    mysqli_stmt_close($stmt);
    
    return [
        'result' => $result,
        'affected_rows' => $affected_rows,
        'insert_id' => $insert_id
    ];
}

function formatPrice($price) {
    return number_format($price, 2) . ' ₪';
}

/**
 * Normalize Palestinian mobile number to WhatsApp format: 9725XXXXXXXX
 * - Accepts formats like: 059xxxxxxxx, +97259xxxxxxxx, 0097259xxxxxxxx
 * - Strips all non-digits
 * - Returns string '9725XXXXXXXX' on success, or null on failure
 */
function normalizePhoneForWhatsApp($phone) {
    // Keep digits only
    $digits = preg_replace('/\D+/', '', (string)$phone);

    if ($digits === '') return null;

    // Remove leading 00 for international prefix
    if (strpos($digits, '00972') === 0) {
        $digits = substr($digits, 2); // remove '00' -> 972...
    } elseif (strpos($digits, '00970') === 0) {
        // تحويل 970 إلى 972
        $digits = '972' . substr($digits, 5);
    }

    // Case 1: already starts with 972...
    if (strpos($digits, '972') === 0) {
        $rest = substr($digits, 3); // rest after 972

        // If rest starts with 0 (e.g. 972059...), strip that 0
        if (isset($rest[0]) && $rest[0] === '0') {
            $rest = substr($rest, 1);
        }

        // Now we expect 9 digits starting with 5 (e.g. 59xxxxxxx)
        if (strlen($rest) === 9 && $rest[0] === '5') {
            return '972' . $rest;
        }

        return null;
    }

    // Case 2: starts with 970 (Palestinian code)
    if (strpos($digits, '970') === 0) {
        $rest = substr($digits, 3); // rest after 970

        // If rest starts with 0, strip it
        if (isset($rest[0]) && $rest[0] === '0') {
            $rest = substr($rest, 1);
        }

        // Now we expect 9 digits starting with 5
        if (strlen($rest) === 9 && $rest[0] === '5') {
            return '972' . $rest; // تحويل إلى 972
        }

        return null;
    }

    // Case 3: local format 05XXXXXXXX (10 digits)
    if (strlen($digits) === 10 && $digits[0] === '0' && $digits[1] === '5') {
        // Drop leading 0 -> 5XXXXXXXX
        return '972' . substr($digits, 1);
    }

    // Case 4: local without leading 0: 5XXXXXXXX (9 digits)
    if (strlen($digits) === 9 && $digits[0] === '5') {
        return '972' . $digits;
    }

    return null;
}

// Get effective price (discount_price if exists and lower, otherwise price)
function getEffectivePrice($price, $discount_price = null) {
    if ($discount_price !== null && $discount_price !== '' && $discount_price < $price) {
        return floatval($discount_price);
    }
    return floatval($price);
}

// Calculate discount percentage
function getDiscountPercentage($price, $discount_price) {
    if ($discount_price === null || $discount_price === '' || $discount_price >= $price) {
        return 0;
    }
    return round((($price - $discount_price) / $price) * 100);
}

// Check if product has active discount
function hasDiscount($price, $discount_price = null) {
    return $discount_price !== null && $discount_price !== '' && $discount_price < $price;
}
function uploadImage($file) {
    if ($file['error'] !== 0) return '';

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) return '';
    if (!getimagesize($file['tmp_name'])) return '';

    $name = time() . '_' . uniqid() . '.' . $ext;
    $target = PRODUCTS_UPLOAD_DIR . $name;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // نخزن path نسبي من root
        return 'uploads/products/' . $name;
    }

    return '';
}


function deleteImage($image) {
    $fullPath = __DIR__ . '/' . $image;
    if ($image && file_exists($fullPath)) {
        unlink($fullPath);
    }
}



// ============================================
// LIBRARY FUNCTIONS (NESTED STRUCTURE)
// ============================================

function getMainCategories() {
    global $conn;
    $query = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name";
    $exec = executeQuery($conn, $query);
    return $exec['result'];
}

function getChildCategories($parent_id) {
    global $conn;
    $query = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
    $exec = executeQuery($conn, $query, "i", [(int)$parent_id]);
    return $exec['result'];
}

function getAllDescendants($library_id) {
    global $conn;
    $path_query = "SELECT path FROM libraries WHERE id = ?";
    $path_exec = executeQuery($conn, $path_query, "i", [(int)$library_id]);
    $path_data = mysqli_fetch_assoc($path_exec['result']);
    
    if (!$path_data) return null;
    $path = $path_data['path'];
    
    $query = "SELECT * FROM libraries WHERE path LIKE ? ORDER BY path";
    $exec = executeQuery($conn, $query, "s", [$path . '.%']);
    return $exec['result'];
}

function getBreadcrumb($library_id) {
    global $conn;
    $breadcrumb = [];
    
    $current_id = (int)$library_id;
    while ($current_id) {
        $query = "SELECT id, name, parent_id FROM libraries WHERE id = ?";
        $exec = executeQuery($conn, $query, "i", [$current_id]);
        if (mysqli_num_rows($exec['result']) > 0) {
            $lib = mysqli_fetch_assoc($exec['result']);
            array_unshift($breadcrumb, $lib);
            $current_id = (int)$lib['parent_id'];
        } else {
            break;
        }
    }
    
    return $breadcrumb;
}

function getCategoryProducts($category_id) {
    global $conn;
    
    // Simplification: Not using path structure from libraries since parent_id is used now.
    // If you need deep nesting, a recursive CTE can be used. For typical usage, just direct.
    // We'll just fetch direct children products or current node.
    $cat_ids = [(int)$category_id];
    $descendants_query = "SELECT id FROM categories WHERE parent_id = ?";
    $descendants_exec = executeQuery($conn, $descendants_query, "i", [(int)$category_id]);
    
    while ($row = mysqli_fetch_assoc($descendants_exec['result'])) {
        $cat_ids[] = (int)$row['id'];
    }
    
    $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
    $types = str_repeat('i', count($cat_ids));
    
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              JOIN categories c ON p.category_id = c.id 
              WHERE p.category_id IN ($placeholders) 
              AND p.is_active = 1 
              ORDER BY p.name";
    
    $exec = executeQuery($conn, $query, $types, $cat_ids);
    return $exec['result'];
}

// ============================================
// CART FUNCTIONS
// ============================================

function getCartCount() {
    global $conn;
    $session_id = $_SESSION['cart_session_id'];
    $query = "SELECT SUM(quantity) as count FROM cart_items WHERE session_id = ?";
    $exec = executeQuery($conn, $query, "s", [$session_id]);
    $row = mysqli_fetch_assoc($exec['result']);
    return $row['count'] ?? 0;
}

function getCartItems() {
    global $conn;
    $session_id = $_SESSION['cart_session_id'];
    $query = "SELECT c.*, p.name, p.price, p.price as discount_price, p.cost_price, p.stock_quantity as stock, pi.image_url as image 
              FROM cart_items c 
              JOIN products p ON c.product_id = p.id 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              WHERE c.session_id = ?";
    $exec = executeQuery($conn, $query, "s", [$session_id]);
    return $exec['result'];
}

function clearCart() {
    global $conn;
    $session_id = $_SESSION['cart_session_id'];
    $query = "DELETE FROM cart_items WHERE session_id = ?";
    return executeQuery($conn, $query, "s", [$session_id]);
}

// ============================================
// PROFIT CALCULATION (ONLY ON DELIVERED)
// ============================================

// recordProfit has been removed because daily_profit_analytics view handles profits dynamically.

function getMonthlyProfit($year_month) {
    global $conn;
    $query = "SELECT SUM(net_profit) as total FROM daily_profit_analytics 
              WHERE DATE_FORMAT(profit_date, '%Y-%m') = ?";
    $exec = executeQuery($conn, $query, "s", [$year_month]);
    $row = mysqli_fetch_assoc($exec['result']);
    return $row['total'] ?? 0;
}
?>