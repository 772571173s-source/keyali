<?php
// save_ghost_score.php
session_start();
include 'config/db.php';

// التأكد من أن الطلب قادم عبر POST وأن المستخدم مسجل دخوله
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {

    // استقبال البيانات القادمة من الجافاسكربت
    $input = json_decode(file_get_contents('php://input'), true);
    $score = isset($input['score']) ? intval($input['score']) : 0;
    $user_id = $_SESSION['user_id'];

    try {
        // 1. جلب السكور القديم للمستخدم لتجنب تخفيض سكور أعلى حققه سابقاً
        $stmt = $pdo->prepare("SELECT ghost_words_streak FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $old_score = intval($user['ghost_words_streak']);

            // 2. تحديث السكور فقط إذا كان السكور الحالي أعلى من سكور المستخدم السابق
            if ($score > $old_score) {
                $update_stmt = $pdo->prepare("UPDATE users SET ghost_words_streak = ? WHERE id = ?");
                $update_stmt->execute([$score, $user_id]);

                echo json_encode(['status' => 'success', 'message' => '🔥 رقم قياسي جديد تم حفظه بنجاح!']);
                exit;
            } else {
                echo json_encode(['status' => 'no_change', 'message' => 'النتيجة أقل من رقمك القياسي السابق.']);
                exit;
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في السيرفر: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'unauthorized', 'message' => 'يجب تسجيل الدخول لحفظ النقاط']);
    exit;
}
