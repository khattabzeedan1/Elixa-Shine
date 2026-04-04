<?php
require_once 'config.php';

$main_categories = getMainCategories();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - متجر مواد التنظيف</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

        * {
            font-family: 'Cairo', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e0f7fa 0%, #f1f8ff 100%);
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, #0277bd 0%, #0097a7 50%, #26c6da 100%);
            position: relative;
            overflow: hidden;
            padding: 80px 0 60px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            color: white;
            text-align: center;
        }

        .hero-logo {
            max-width: 150px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5));
            border-radius: 10px; /* Adding slight radius to match the user's screenshot if it was a square image */
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); /* Adding shadow to look like the card in the screenshot */
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 2px;
            display: none; /* Hidden visually if logo is present, or keep for SEO */
        }

        .hero-subtitle {
            font-size: 1.4rem;
            margin-top: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .search-container {
            max-width: 600px;
            margin: 30px auto 0;
        }

        .search-input {
            border-radius: 50px;
            padding: 15px 25px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.95);
            font-size: 1.1rem;
        }

        .search-input:focus {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            border-color: white;
        }

        .library-selector {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 188, 212, 0.15);
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }

        .library-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            border: 2px solid #e1f5fe;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .library-card:hover {
            border-color: #00bcd4;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 188, 212, 0.2);
        }

        .library-card.active {
            border-color: #00bcd4;
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
            box-shadow: 0 6px 20px rgba(0, 188, 212, 0.3);
        }

        .library-card h5 {
            color: #00838f;
            margin: 0;
        }

        .library-card.has-children::after {
            content: '\f105';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #00bcd4;
            font-size: 1.5rem;
        }

        .library-card {
            position: relative;
        }

        .back-button {
            background: linear-gradient(135deg, #00838f 0%, #006064 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: linear-gradient(135deg, #006064 0%, #004d40 100%);
            transform: translateX(5px);
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            border: 2px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 188, 212, 0.25);
            border-color: #00bcd4;
        }

        .product-image {
            height: 240px;
            object-fit: cover;
            width: 100%;
        }

        .product-body {
            padding: 20px;
        }

        .product-price {
            color: #00838f;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .product-price-original {
            color: #999;
            font-size: 1.2rem;
            text-decoration: line-through;
            margin-left: 10px;
        }

        .product-price-discounted {
            color: #dc3545;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .discount-badge {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 5px;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #00acc1 0%, #00838f 100%);
            transform: scale(1.05);
        }

        .cart-icon {
            position: fixed;
            top: 20px;
            left: -20px;
            z-index: 1000;
            background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%);
            box-shadow: 0 6px 20px rgba(0, 188, 212, 0.4);
        }

        .cart-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(0, 188, 212, 0.6);
        }

        .message-icon {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 6px 25px rgba(0, 188, 212, 0.4);
            transition: all 0.3s;
            z-index: 1000;
        }

        .message-icon:hover {
            transform: scale(1.15);
            box-shadow: 0 8px 35px rgba(0, 188, 212, 0.6);
        }

        .breadcrumb-custom {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .hero-section {
                padding: 60px 0 50px;
            }

            .product-image {
                height: 200px;
            }

            .library-selector {
                padding: 20px;
            }
        }
        
        #products-container {
            min-height: 60vh;
            transition: height 0.3s ease;
        }

        #library-selector {
            min-height: 200px;
            transition: height 0.3s ease;
        }

        .promo-banner {
            background: linear-gradient(90deg, #0288d1 0%, #26c6da 100%);
            color: white;
            padding: 12px;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1001; /* Ensure it's above hero but below modals */
        }
        
        .promo-banner i {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-5px);}
            60% {transform: translateY(-3px);}
        }
    </style>
</head>

<body>
    <div id="promoBanner" class="promo-banner text-center fade show" role="alert">
        <div class="container position-relative">
            <i class="fas fa-truck me-2"></i>
            عرض خاص! اطلب بـ 300 شيكل أو أكثر… والتوصيل علينا 🚚
            <button type="button" class="btn-close btn-close-white shadow-none position-absolute top-50 end-0 translate-middle-y" onclick="document.getElementById('promoBanner').remove()" aria-label="Close" style="left: 10px; right: auto;"></button>
        </div>
    </div>
    <a href="cart.php" class="cart-icon btn btn-primary btn-lg rounded-circle position-relative">
        <i class="fas fa-shopping-cart fa-lg"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
            <?php echo getCartCount(); ?>
        </span>
    </a>

    <div class="hero-section">
        <div class="hero-content container">
            <img src="<?php echo BASE_URL; ?>assets/logo.png" alt="<?php echo SITE_NAME; ?>" class="hero-logo">
            <h1 class="hero-title"><?php echo SITE_NAME; ?></h1>
            <p class="hero-subtitle">متجرك المميز لمواد التنظيف بأسعار منافسة</p>

            <div class="search-container">
                <input type="text" class="form-control search-input" id="searchInput" placeholder="دور على أي منتج...">
            </div>
        </div>
    </div>

    <div class="container">
        <div class="library-selector">
            <div id="back-button-container"></div>

            <div class="text-center mb-4">
                <h3 class="text-cyan"><i class="fas fa-layer-group me-2"></i><span id="selector-title">اختار القسم</span></h3>
                <p class="text-muted" id="selector-subtitle">اختار المكتبة المناسبة لعرض المنتجات</p>
            </div>

            <div class="row" id="library-selector">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5" id="products-section">
        <div id="breadcrumb-container"></div>

        <div class="text-center mb-4">
            <h2 id="section-title" style="color: #00838f;">كل المنتجات</h2>
        </div>

        <div class="row" id="products-container">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </div>

    <div class="message-icon" data-bs-toggle="modal" data-bs-target="#messageModal">
        <i class="fas fa-comment fa-2x text-white"></i>
    </div>

    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white;">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>ابعتلنا رسالة</h5>
                </div>
                <div class="modal-body">
                    <form id="messageForm">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">الاسم</label>
                            <input type="text" class="form-control" id="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">رقم الموبايل</label>
                            <input type="tel" class="form-control" id="customer_phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="message_text" class="form-label">الرسالة</label>
                            <textarea class="form-control" id="message_text" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white;">
                            <i class="fas fa-paper-plane me-2"></i>ابعت الرسالة
                        </button>
                    </form>
                    <div id="messageAlert" class="alert mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productModalBody"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allProducts = [];
        let currentLibraryId = 'all';
        let navigationStack = [];

        $(document).ready(function() {
            loadLibraries('all');
            loadProducts('all');

            $('#searchInput').on('input', function() {
                let searchTerm = $(this).val().trim().toLowerCase();

                if (searchTerm.length === 0) {
                    displayProducts(allProducts);
                    return;
                }

                let filtered = allProducts.filter(function(product) {
                    return product.name.toLowerCase().includes(searchTerm);
                });

                displayProducts(filtered);
            });
        });

        function loadLibraries(parentId) {
            $('#library-selector').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.get('api/get_categories.php', {
                parent_id: parentId
            }, function(response) {
                if (response.success) {
                    displayLibraries(response.categories, parentId);
                }
            }, 'json');
        }

        function displayLibraries(libraries, parentId) {
            let html = '';

            // زر "كل المنتجات" فقط في المستوى الأول
            if (parentId === 'all' || parentId === null) {
                html += `
                    <div class="col-12 mb-3">
                        <button class="library-card w-100 text-end ${currentLibraryId === 'all' ? 'active' : ''}" 
                                onclick="selectLibrary('all', 'كل المنتجات')">
                            <h5><i class="fas fa-th-large me-2"></i>كل المنتجات</h5>
                        </button>
                    </div>
                `;
            }

            libraries.forEach(function(library) {
                let hasChildren = library.has_children === 1 || library.has_children === '1' || library.children_count > 0;

                html += `
                    <div class="col-6 col-md-6 col-lg-4 col-xl-3">
                        <div class="library-card text-end ${hasChildren ? 'has-children' : ''} ${currentLibraryId == library.id ? 'active' : ''}" 
                             onclick="selectLibrary(${library.id}, '${library.name.replace(/'/g, "\\'")}', ${hasChildren})">
                            <h5>
                                <i class="fas fa-folder me-2"></i>
                                ${library.name}
                            </h5>
                            ${library.children_count > 0 ? 
                                `<small class="text-muted">
                                    <i class="fas fa-sitemap me-1"></i>
                                    ${library.children_count} قسم فرعي
                                </small>` : ''}
                        </div>
                    </div>
                `;
            });

            if (libraries.length === 0 && parentId !== 'all') {
                html = '<div class="col-12 text-center py-4"><p class="text-muted">لا توجد أقسام فرعية</p></div>';
            }

            $('#library-selector').html(html);
        }

        function selectLibrary(libraryId, libraryName, hasChildren) {
            currentLibraryId = libraryId;

            // إذا كانت المكتبة تحتوي على مكتبات فرعية
            if (hasChildren) {
                navigationStack.push({
                    id: libraryId,
                    name: libraryName
                });

                loadLibraries(libraryId);
                $('#selector-title').text('الأقسام الفرعية');
                $('#selector-subtitle').text('اختار القسم الفرعي المناسب');
                showBackButton();
            } else {
                // تحميل المنتجات مباشرة
                loadProducts(libraryId);
            }

            // تحديث الـ active class
            $('.library-card').removeClass('active');
            $(`[onclick*="selectLibrary(${libraryId}"]`).addClass('active');
        }

        function showBackButton() {
            if (navigationStack.length > 0) {
                let html = `
                    <button class="back-button" onclick="goBack()">
                        <i class="fas fa-arrow-right me-2"></i>رجوع للأقسام الرئيسية
                    </button>
                `;
                $('#back-button-container').html(html);
            }
        }

        function goBack() {
            if (navigationStack.length > 0) {
                navigationStack.pop();

                if (navigationStack.length === 0) {
                    loadLibraries('all');
                    $('#selector-title').text('اختار القسم');
                    $('#selector-subtitle').text('اختار المكتبة المناسبة لعرض المنتجات');
                    $('#back-button-container').html('');
                    currentLibraryId = 'all';
                    loadProducts('all');
                } else {
                    let previous = navigationStack[navigationStack.length - 1];
                    loadLibraries(previous.id);
                }
            }
        }

        function loadProducts(libraryId) {
            $('#products-container').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>');

            $.get('api/get_products.php', {
                library_id: libraryId
            }, function(response) {
                if (response.success) {
                    allProducts = response.products;
                    displayProducts(response.products);
                    displayBreadcrumb(response.breadcrumb);
                    $('#section-title').text(response.title);
                    $('#searchInput').val('');
                }
            }, 'json');
        }

        function displayProducts(products) {
            if (products.length === 0) {
                $('#products-container').html('<div class="col-12 text-center py-5"><p class="text-muted">ما في منتجات متوفرة</p></div>');
                return;
            }

            let html = '';
            products.forEach(function(product) {
                html += `
                    <div class="col-6 col-md-6 col-lg-4 col-xl-3">
                        <div class="product-card">
                            ${product.image ? 
                                `<img src="<?php echo BASE_URL; ?>${product.image}" class="product-image" alt="${product.name}">` :
                                `<div class="product-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-box fa-3x text-muted opacity-50"></i>
                                </div>`
                            }
                            <div class="product-body">
                                <h5 class="mb-2">${product.name}</h5>
                                <p class="text-muted small mb-3">${product.description ? product.description.substring(0, 60) + '...' : ''}</p>
                                ${product.has_discount ? 
                                    `<div class="mb-3">
                                        <span class="discount-badge">خصم ${product.discount_percentage}%</span>
                                        <div class="mt-2">
                                            <span class="product-price-original">${parseFloat(product.price).toFixed(2)} ₪</span>
                                            <span class="product-price-discounted d-block mt-1">${parseFloat(product.effective_price).toFixed(2)} ₪</span>
                                        </div>
                                    </div>` :
                                    `<h4 class="product-price mb-3">${parseFloat(product.price).toFixed(2)} ₪</h4>`
                                }
                                <button class="btn btn-add-cart w-100 mb-2" onclick="addToCart(${product.id}, this)">
                                    <i class="fas fa-cart-plus me-2"></i>ضيف للسلة
                                </button>
                                <button class="btn btn-outline-info w-100" onclick="viewProduct(${product.id})">
                                    <i class="fas fa-eye me-2"></i>التفاصيل
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#products-container').html(html);
        }

        function displayBreadcrumb(breadcrumb) {
            if (!breadcrumb || breadcrumb.length === 0) {
                $('#breadcrumb-container').html('');
                return;
            }

            let html = '<nav class="breadcrumb-custom"><ol class="breadcrumb mb-0">';
            html += '<li class="breadcrumb-item"><a href="#" onclick="loadProducts(\'all\'); return false;">الرئيسية</a></li>';

            breadcrumb.forEach(function(item, index) {
                if (index === breadcrumb.length - 1) {
                    html += `<li class="breadcrumb-item active">${item.name}</li>`;
                } else {
                    html += `<li class="breadcrumb-item"><a href="#" onclick="loadProducts(${item.id}); return false;">${item.name}</a></li>`;
                }
            });

            html += '</ol></nav>';
            $('#breadcrumb-container').html(html);
        }

        function addToCart(productId, btnElement) {
            // Disable button to prevent double clicks
            let originalText = $(btnElement).html();
            $(btnElement).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post('api/add_to_cart.php', {
                product_id: productId
            }, function(response) {
                if (response.success) {
                    refreshCartCount(); // Update badge without reload
                    
                    // Show success feedback
                    $(btnElement).removeClass('btn-add-cart').addClass('btn-success')
                        .html('<i class="fas fa-check me-2"></i>تمت الإضافة');
                    
                    // Revert button after 1.5 seconds
                    setTimeout(function() {
                        $(btnElement).removeClass('btn-success').addClass('btn-add-cart')
                            .prop('disabled', false).html(originalText);
                    }, 1500);
                } else {
                    alert(response.message);
                    $(btnElement).prop('disabled', false).html(originalText);
                }
            }, 'json').fail(function() {
                alert('حدث خطأ في الاتصال');
                $(btnElement).prop('disabled', false).html(originalText);
            });
        }

        function viewProduct(productId) {
            $('#productModal').modal('show');
            $('#productModalBody').html('<div class="text-center py-4"><div class="spinner-border"></div></div>');

            $.get('api/product_detail.php', {
                id: productId
            }, function(data) {
                $('#productModalBody').html(data);
            });
        }

        $('#messageForm').submit(function(e) {
            e.preventDefault();

            $.post('api/send_message.php', {
                customer_name: $('#customer_name').val(),
                customer_phone: $('#customer_phone').val(),
                message: $('#message_text').val()
            }, function(response) {
                if (response.success) {
                    $('#messageAlert').removeClass('alert-danger').addClass('alert-success')
                        .text('تم ارسال الرسالة بنجاح!').show();
                    $('#messageForm')[0].reset();

                    setTimeout(function() {
                        $('#messageModal').modal('hide');
                        $('#messageAlert').hide();
                    }, 2000);
                }
            }, 'json');
        });

        function refreshCartCount() {
            $.ajax({
                url: 'api/cart_count.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cart-count').text(response.count);
                        if (response.count > 0) {
                             $('#cart-count').addClass('animate__animated animate__pulse');
                             setTimeout(() => $('#cart-count').removeClass('animate__animated animate__pulse'), 1000);
                        }
                    }
                },
                error: function() {
                    // Fail silently on network errors
                }
            });
        }

        setInterval(refreshCartCount, 10000);
    </script>
</body>

</html>