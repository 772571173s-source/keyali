<?php
session_start();
include 'config/db.php';

header('Content-Type: application/json');

// 🛑 خط الدفاع الأول: إذا لم يكن هناك جلسة نشطة، نرفض الطلب فوراً
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'عذراً يا شريكي، يجب عليك تسجيل الدخول أولاً لتسجيل نقاطك!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['score']) && isset($_POST['streak'])) {
    
    $new_score = (int)$_POST['score'];
    $new_streak = (int)$_POST['streak'];
    $user_id = $_SESSION['user_id']; // الـ ID الحقيقي القادم من الجلسة الآمنة

    try {
        // جلب الأرقام القياسية القديمة للاعب
        $stmt = $pdo->prepare("SELECT highest_score, highest_streak FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_stats) {
            // مقارنة النتيجة وتحديثها فقط إن كانت أفضل من السابق (Personal Best)
            $update_score = ($new_score > $user_stats['highest_score']) ? $new_score : $user_stats['highest_score'];
            $update_streak = ($new_streak > $user_stats['highest_streak']) ? $new_streak : $user_stats['highest_streak'];

            $update_stmt = $pdo->prepare("UPDATE users SET highest_score = ?, highest_streak = ? WHERE id = ?");
            $update_stmt->execute([$update_score, $update_streak, $user_id]);

            $is_new_pb = ($new_score > $user_stats['highest_score']);

            echo json_encode([
                'status' => 'success',
                'message' => 'تم فحص نتيجتك بنجاح وتحديث لوحة الصدارة! 🏆',
                'is_new_pb' => $is_new_pb
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على بيانات المستخدم في النظام.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في السيرفر: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير مصرح به.']);
}