<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = cleanInput($_POST['customer_name']);
    $customer_phone = cleanInput($_POST['customer_phone']);
    $message = cleanInput($_POST['message']);

    if (empty($customer_name) || empty($customer_phone) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'كل الحقول مطلوبة']);
        exit();
    }

    // Since frontend sends phone, we map it to subject, and generate a guest email.
    $sender_name = $customer_name;
    $sender_email = 'guest_' . uniqid() . '@example.com';
    $subject = 'رسالة من رقم: ' . $customer_phone;
    $body = $message;

    $insert_query = "INSERT INTO messages (sender_name, sender_email, subject, body) VALUES (?, ?, ?, ?)";

    try {
        executeQuery($conn, $insert_query, "ssss", [$sender_name, $sender_email, $subject, $body]);
        echo json_encode(['success' => true, 'message' => 'تم إرسال الرسالة بنجاح']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'فشل إرسال الرسالة: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
}
