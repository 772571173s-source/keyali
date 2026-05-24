<?php
// 💻 إعدادات الاتصال بالسيرفر المحلي (Localhost) - بدون إنترنت
$host = 'localhost'; 
$db_name = 'keyali_db'; // ⚠️ اكتب هنا اسم قاعدة البيانات التي أنشأتها في phpMyAdmin المحلي لديك
$username = 'root';     // الاسم الافتراضي لجميع السيرفرات المحلية هو root
$password = '';         // كلمة المرور الافتراضية تكون فارغة تماماً في السيرفر المحلي

try {
    // إنشاء اتصال PDO الحديث والآمن
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // تفعيل وضع الأخطاء لإظهار التحذيرات أثناء البرمجة والتطوير
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // جلب البيانات على شكل مصفوفات ترابطية بشكل افتراضي
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // في حال حدوث خطأ في الاتصال المحلي
    die("فشل الاتصال بقاعدة البيانات المحلية يا شريكي: " . $e->getMessage());
}
?>