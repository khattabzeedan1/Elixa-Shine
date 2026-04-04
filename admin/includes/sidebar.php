<?php
$unread_query = "SELECT COUNT(*) as count FROM messages WHERE status='unread'";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_result)['count'];

$pending_query = "SELECT COUNT(*) as count FROM orders WHERE status='pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

    * {
        font-family: 'Cairo', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #e0f7fa 0%, #f1f8ff 100%);
        min-height: 100vh;
    }

    .sidebar {
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        width: 280px;
        background: linear-gradient(180deg, #00bcd4 0%, #0097a7 100%);
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        transition: transform 0.3s ease;
        overflow-y: auto;
    }

    .sidebar.hidden {
        transform: translateX(100%);
    }

    .sidebar-header {
        padding: 25px 20px;
        background: rgba(0, 0, 0, 0.15);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        color: white;
    }

    .sidebar-header h4 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .sidebar-header small {
        opacity: 0.9;
        font-size: 0.85rem;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.9);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        border-right: 3px solid transparent;
        margin: 3px 0;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding-right: 25px;
    }

    .sidebar .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        border-right-color: white;
        color: white;
        font-weight: 600;
    }

    .sidebar .nav-link i {
        margin-left: 12px;
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
    }

    .hamburger-btn {
        position: fixed;
        top: 15px;
        right: 15px;
        z-index: 1001;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 188, 212, 0.4);
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .hamburger-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 188, 212, 0.6);
    }

    .hamburger-btn i {
        color: white;
        font-size: 1.3rem;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    .main-content {
        margin-right: 280px;
        padding: 30px;
        transition: margin 0.3s ease;
    }

    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .hamburger-btn {
            display: flex;
        }

        .main-content {
            margin-right: 0;
            padding: 80px 15px 30px;
        }
    }

    .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 10px;
    }

    

</style>

<button class="hamburger-btn" id="hamburgerBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><?php echo SITE_NAME; ?></h4>
        <small>لوحة التحكم</small>
        <hr class="bg-light my-2 opacity-25">
        <small><i class="fas fa-user-shield ms-1"></i><?php echo $_SESSION['admin_username']; ?></small>
    </div>

    <nav class="nav flex-column mt-3">
        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i>
            <span>الرئيسية</span>
        </a>

        <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
            <i class="fas fa-folder"></i>
            <span>الأقسام</span>
        </a>

        <a class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="fas fa-box"></i>
            <span>المنتجات</span>
        </a>

        <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
            <i class="fas fa-shopping-cart"></i>
            <span>الطلبات</span>
            <?php if ($pending_count > 0): ?>
                <span class="badge bg-danger ms-auto"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>

        <a class="nav-link <?php echo $current_page === 'profits.php' ? 'active' : ''; ?>" href="profits.php">
            <i class="fas fa-chart-line"></i>
            <span>الأرباح الشهرية</span>
        </a>

        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="messages.php">
            <i class="fas fa-comments"></i>
            <span>الرسائل</span>
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-warning ms-auto"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>

        <hr class="bg-light mx-3 opacity-25">

        <a class="nav-link" href="../index.php" target="_blank">
            <i class="fas fa-store"></i>
            <span>المتجر</span>
        </a>

        <a class="nav-link <?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>" href="change_password.php">
            <i class="fas fa-key"></i>
            <span>تغيير كلمة المرور</span>
        </a>


        <a class="nav-link" href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </nav>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('sidebarOverlay');

        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
        });

        if (window.innerWidth <= 992) {
            document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('active');
                });
            });
        }
    });
</script>