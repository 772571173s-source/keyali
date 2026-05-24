<?php
// 1. تشغيل الجلسة فوراً
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. فحص الأمان: منع أي شخص ليس روت أو أدمين من الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    // طرده خارج مجلد الأدمين إلى صفحة تسجيل الدخول الرئيسية
    header("Location: ../login.php?error=forbidden");
    exit();
}
?>