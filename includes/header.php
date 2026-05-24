<?php
// نتحقق أولاً إن كانت الجلسة لم تبدأ بعد في الصفحة المستدعية، لكي لا يحدث تكرار
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Keyali - منصة إتقان الكتابة البرمجية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* شارة التنبيه الحماسية الحمراء للطور الجديد */
        .badge-hot { 
            background-color: #ef4444; 
            color: #fff; 
            font-size: 11px; 
            padding: 2px 6px; 
            border-radius: 20px; 
            font-weight: bold; 
            margin-right: 5px; 
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.6); 
            display: inline-block;
        }
        /* تنسيق مخصص لاسم المستخدم المسجل ليميزه في القائمة */
        .user-logged-in {
            color: #38bdf8 !important;
            font-weight: bold;
        }
        .logout-btn {
            color: #f87171 !important;
        }

        /* 📱 التطوير السحري لمنع تغطية اللعبة وجعل القائمة قابلة للسحب أفقياً على الموبايل */
        @media (max-width: 992px) {
            .navbar {
                padding: 10px 8px !important;
                height: auto !important;
                position: relative !important; /* التأكد أنه لا يطفو فوق المحتوى */
                box-sizing: border-box;
            }

            .nav-container {
                flex-direction: column !important; /* اللوجو فوق والأزرار تحت */
                gap: 8px !important;
                align-items: center !important;
                width: 100% !important;
            }

            .logo {
                font-size: 20px !important; /* تصغير حجم اللوجو قليلاً لتوفير مساحة */
                margin: 0 !important;
                padding-bottom: 2px;
            }

            /* ✨ تحويل القائمة إلى شريط أفقي ميكانيكي ذكي وسلس التصفح */
            .nav-links {
                flex-direction: row !important; 
                flex-wrap: nowrap !important; /* 🚫 منع النزول لسطر جديد نهائياً لكي لا يرتفع الناف بار */
                justify-content: flex-start !important; /* البدء من اليمين */
                align-items: center !important;
                gap: 8px !important;
                width: 100% !important;
                overflow-x: auto !important; /* 📜 تفعيل السحب الأفقي بالإصبع */
                padding: 5px 10px !important;
                margin: 0 !important;
                list-style: none !important;
                
                /* تحسين نعومة السحب على شاشات اللمس للأندرويد والآيفون */
                -webkit-overflow-scrolling: touch; 
                scroll-snap-type: x mandatory;
            }

            /* 🔥 إخفاء شريط التمرير (Scrollbar) تماماً لتبدو القائمة مودرن ونظيفة */
            .nav-links::-webkit-scrollbar {
                display: none !important;
            }
            .nav-links {
                -ms-overflow-style: none !important;  /* متصفح إيدج القديم */
                scrollbar-width: none !important;  /* متصفح فايرفوكس */
            }

            .nav-links li, .nav-item {
                margin: 0 !important;
                scroll-snap-align: start; /* جعل السحب يقف بشكل منسق عند كل زر */
                flex-shrink: 0 !important; /* منع انضغاط الأزرار أو تشوه أحجامها */
            }

            /* ستايل الأزرار الرشيقة على الجوال */
            .nav-links a {
                font-size: 13px !important; 
                padding: 6px 12px !important; /* تقليل البادينج العمودي ليكون الناف بار نحيفاً */
                background: rgba(30, 41, 59, 0.7) !important; 
                border: 1px solid rgba(255, 255, 255, 0.08) !important;
                border-radius: 8px !important;
                display: block !important;
                white-space: nowrap !important; 
            }

            .nav-links a:active {
                background: rgba(56, 189, 248, 0.15) !important;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🔑 Keyali</a>
            <ul class="nav-links">
                <li><a href="index.php">الرئيسية</a></li>
                <li><a href="words.php">قسم الكلمات</a></li>
                <li><a href="codes.php">أكواد ومصطلحات</a></li>
                <li><a href="speed-test.php">تحدي السرعة</a></li>
                
                <li class="nav-item">
                    <a class="nav-link challenge-link" href="english-challenge.php">
                        🧠 تحدي الجمل
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link challenge-link" href="ranked-challenge.php" style="color: #f59e0b; font-weight: bold;">
                        🏆 الطور التنافسي <span class="badge-hot">🔥 حماسي</span>
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="profile.php" class="user-logged-in">
                            🥷 <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                    </li>
                    <li><a href="logout.php" class="logout-btn">🚪 خروج</a></li>
                <?php else: ?>
                    <li><a href="login.php" style="color: #a7f3d0;">🔑 دخول</a></li>
                    <li><a href="register.php" style="color: #38bdf8;">⚔️ انضمام</a></li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['root', 'admin'])): ?>
                    <li><a href="admin/index.php" class="admin-btn">لوحة التحكم</a></li>
                <?php endif; ?>
                
            </ul>
        </div>
    </nav>
    
    <main class="main-content">