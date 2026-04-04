<?php
require_once '../config.php';
requireAdmin();

$success = '';

if (isset($_POST['mark_read'])) {
    $message_id = (int)$_POST['message_id'];
    executeQuery($conn, "UPDATE messages SET status = 'read', read_at = NOW() WHERE id = ?", "i", [$message_id]);
    $success = 'تم وضع علامة مقروء';
}

if (isset($_POST['delete_message'])) {
    $message_id = (int)$_POST['message_id'];
    executeQuery($conn, "DELETE FROM messages WHERE id = ?", "i", [$message_id]);
    $success = 'تم حذف الرسالة';
}

$messages_query = "SELECT * FROM messages ORDER BY status ASC, created_at DESC";
$messages_result = mysqli_query($conn, $messages_query);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="fas fa-comments me-2"></i>رسائل الزبائن</h2>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (mysqli_num_rows($messages_result) === 0): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            <p>ما في رسائل لحد الآن</p>
                        </div>
                    <?php else: ?>
                        <?php while ($message = mysqli_fetch_assoc($messages_result)): ?>
                            <div class="card mb-3 <?php echo $message['status'] === 'unread' ? 'border-warning shadow-sm' : ''; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <strong><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($message['sender_name']); ?></strong>
                                        <small class="text-muted ms-2">(<?php echo htmlspecialchars($message['sender_email']); ?>)</small>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <span class="badge bg-warning text-dark ms-2">جديدة</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i><?php echo date('Y/m/d h:i A', strtotime($message['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <strong>الموضوع:</strong> <?php echo htmlspecialchars($message['subject']); ?>
                                    </p>
                                    <div class="alert alert-light border">
                                        <strong>الرسالة:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($message['body'])); ?>
                                    </div>

                                    <div class="mt-3">
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="mark_read" value="1">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-info">
                                                    <i class="fas fa-check me-1"></i>وضع علامة مقروء
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-double me-1"></i>تم القراءة
                                            </span>
                                        <?php endif; ?>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('متأكد من حذف هاي الرسالة؟')">
                                            <input type="hidden" name="delete_message" value="1">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash me-1"></i>حذف
                                            </button>
                                        </form>

                                        <a href="mailto:<?php echo htmlspecialchars($message['sender_email']); ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-envelope me-1"></i>مراسلة الزبون
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>