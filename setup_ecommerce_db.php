<?php
$host = 'localhost';
$user = 'root';
$pass = 'rootroot';

// 1. Connect without DB to create DB
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create DB
$sql = "CREATE DATABASE IF NOT EXISTS ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db('ecommerce_db');

// 3. Create Tables
$tables = [
    "cities" => "
        CREATE TABLE IF NOT EXISTS cities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            shipping_fee DECIMAL(10, 2) DEFAULT 0.00,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ",

    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            city_id INT UNSIGNED DEFAULT NULL,
            role ENUM('customer', 'admin', 'super_admin') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
    ",

    "categories" => "
        CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parent_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
    ",

    "products" => "
        CREATE TABLE IF NOT EXISTS products (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            cost_price DECIMAL(10, 2) NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_products_slug (slug),
            INDEX idx_products_category (category_id)
        ) ENGINE=InnoDB;
    ",

    "product_images" => "
        CREATE TABLE IF NOT EXISTS product_images (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            is_primary BOOLEAN DEFAULT FALSE,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_product_images_product (product_id)
        ) ENGINE=InnoDB;
    ",

    "cart_items" => "
        CREATE TABLE IF NOT EXISTS cart_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            session_id VARCHAR(100) DEFAULT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY uq_user_product (user_id, product_id)
        ) ENGINE=InnoDB;
    ",

    "orders" => "
        CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            city_id INT UNSIGNED DEFAULT NULL,
            shipping_address TEXT NOT NULL,
            total_revenue DECIMAL(10, 2) NOT NULL,
            total_cost DECIMAL(10, 2) NOT NULL,
            shipping_fee DECIMAL(10, 2) DEFAULT 0.00,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'canceled', 'returned') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'cash_on_delivery',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
            INDEX idx_orders_user (user_id),
            INDEX idx_orders_status (status)
        ) ENGINE=InnoDB;
    ",

    "order_items" => "
        CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL CHECK (quantity > 0),
            unit_price DECIMAL(10, 2) NOT NULL,
            unit_cost DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB;
    ",

    "messages" => "
        CREATE TABLE IF NOT EXISTS messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED DEFAULT NULL,
            sender_name VARCHAR(100) NOT NULL,
            sender_email VARCHAR(255) NOT NULL,
            subject VARCHAR(255),
            body TEXT NOT NULL,
            status ENUM('unread', 'read', 'resolved') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
    "
];

foreach ($tables as $name => $query) {
    if ($conn->query($query) === TRUE) {
        echo "Table $name checked/created.<br>";
    } else {
        echo "Error creating table $name: " . $conn->error . "<br>";
    }
}

// 4. Create View
$viewQuery = "
    CREATE OR REPLACE VIEW daily_profit_analytics AS
    SELECT 
        DATE(created_at) AS profit_date,
        COUNT(id) AS total_orders,
        SUM(total_revenue) AS gross_revenue,
        SUM(total_cost) AS total_goods_cost,
        SUM(shipping_fee) AS total_shipping_fees_collected,
        (SUM(total_revenue) - SUM(total_cost)) AS net_profit
    FROM orders
    WHERE status IN ('delivered', 'shipped')
    GROUP BY DATE(created_at);
";
if ($conn->query($viewQuery) === TRUE) {
    echo "View daily_profit_analytics checked/created.<br>";
} else {
    echo "Error creating view: " . $conn->error . "<br>";
}

// 5. Create default Admin user
$passwordHash = password_hash('123456', PASSWORD_DEFAULT);
$adminSql = "INSERT IGNORE INTO users (first_name, last_name, email, password_hash, role) 
             VALUES ('Super', 'Admin', 'admin@example.com', '$passwordHash', 'super_admin')";
if ($conn->query($adminSql) === TRUE) {
    echo "Super Admin user created successfully (email: admin@example.com, pass: 123456).<br>";
}

// Also create legacy test admin if they logged in with username 'admin', let's use email 'admin' for backend compatibility check, or create a specific record. 
// Wait, email has unique so:
$adminSql2 = "INSERT IGNORE INTO users (first_name, last_name, email, password_hash, role) 
             VALUES ('Old', 'Admin', 'admin', '$passwordHash', 'admin')";
$conn->query($adminSql2);

$conn->close();
echo "Database Setup Complete!";
?>
