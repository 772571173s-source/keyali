<?php
// 1. 🔑 تفعيل التخزين المؤقت وتأمين الجلسة
ob_start();

// ضبط إعدادات الجلسة لتكون محمية قبل بدئها (حماية من الـ XSS وسرقة الكوكيز)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1); // منع الجافاسكربت من الوصول للـ Cookie
    ini_set('session.cookie_use_only_cookies', 1);
    // إذا كان موقعك يدعم HTTPS فك تفعيل السطر التالي:
    // ini_set('session.cookie_secure', 1); 

    session_start();
}

// 🛡️ جدار الحماية: منع زوار الجوال العشوائيين وغير المصرح لهم فوراً
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 🔄 تجديد معرف الجلسة لحماية المشرف من ثغرة Session Hijacking أثناء التصفح من شبكات مختلفة
session_regenerate_id(true);

// 2. 🔌 الاتصال بقاعدة البيانات لجلب الأعداد المضافة لايف
include '../config/db.php';

try {
    $count_words = $pdo->query("SELECT COUNT(*) FROM words")->fetchColumn();
    $count_codes = $pdo->query("SELECT COUNT(*) FROM code_terms")->fetchColumn();
    $count_challenges = $pdo->query("SELECT COUNT(*) FROM english_challenges")->fetchColumn();

    // حساب الإجمالي العام للمدخلات البرمجية والتعليمية
    $total_items = $count_words + $count_codes + $count_challenges;
} catch (PDOException $e) {
    // تسجيل الخطأ في السيرفر بصمت دون عرضه للمستخدم (حماية من تسريب مسارات الملفات Info Disclosure)
    error_log("Dashboard Error: " . $e->getMessage());
    $count_words = 0;
    $count_codes = 0;
    $count_challenges = 0;
    $total_items = 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية 🛠️</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #020617;
            color: #fff;
            margin: 0;
            padding-bottom: 40px;
        }

        /* 📱 هندسة الـ Navbar لتكون مرنة ومتجاوبة على الجوال */
        .navbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 15px 20px;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        .nav-links {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* صندوق الترحيب المتجاوب */
        .welcome-box {
            margin-bottom: 25px;
            border-bottom: 1px solid #334155;
            padding-bottom: 20px;
            text-align: right;
        }

        .welcome-box h2 {
            font-size: clamp(20px, 4vw, 28px);
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .welcome-box p {
            font-size: clamp(14px, 3vw, 16px);
            margin: 0;
        }

        .role-badge {
            display: inline-block;
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
        }

        /* ✨ الإضافة 1: قسم صناديق الإحصائيات الشاملة السريعة */
        .mini-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }

        .stat-mini-box {
            background: #0f172a;
            border: 1px solid #1e293b;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-mini-box .stat-num {
            font-size: 20px;
            font-weight: 900;
            color: #38bdf8;
            block-size: auto;
        }

        .stat-mini-box .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
            font-weight: bold;
        }

        .stat-mini-box.total-box {
            border-color: rgba(74, 222, 128, 0.3);
            background: rgba(74, 222, 128, 0.02);
        }

        .stat-mini-box.total-box .stat-num {
            color: #4ade80;
        }

        /* ✨ الإضافة 2: شريط البحث والفلترة الذكي */
        .search-wrapper {
            position: relative;
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        .search-input {
            flex-grow: 1;
            padding: 14px 20px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .search-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.15);
        }

        .clear-search-btn {
            background: #1e293b;
            border: 1px solid #334155;
            color: #94a3b8;
            padding: 0 15px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .clear-search-btn:hover {
            background: #334155;
            color: #fff;
        }

        /* شبكة التحكم (Grid) الموزعة بأناقة تامة لكل الشاشات */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 20px;
            box-sizing: border-box;
        }

        .control-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .control-card:hover {
            border-color: #38bdf8;
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(56, 189, 248, 0.08);
        }

        .card-icon {
            font-size: 30px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 19px;
            font-weight: bold;
            color: #38bdf8;
            margin: 0 0 12px 0;
        }

        .card-desc {
            color: #94a3b8;
            font-size: 14px;
            margin: 0 0 25px 0;
            line-height: 1.6;
            flex-grow: 1;
        }

        /* تحسين مظهر وحجم أزرار التحكم لتناسب نقرات الأصابع الكبيرة من شاشة الهاتف */
        .card-btn {
            background: #0f172a;
            border: 1px solid #475569;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #fff;
            transition: 0.2s;
            width: 100%;
            box-sizing: border-box;
        }

        .control-card:hover .card-btn {
            background: #38bdf8;
            color: #020617;
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.2);
        }

        .card-challenges:hover {
            border-color: #f59e0b;
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.1);
        }

        .card-challenges:hover .card-btn {
            background: #f59e0b;
            color: #020617;
            border-color: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
        }

        .live-counter {
            font-size: 13px;
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: bold;
        }

        /* رسالة عدم وجود نتائج عند الفلترة */
        #no-results-msg {
            display: none;
            text-align: center;
            color: #64748b;
            padding: 40px 20px;
            font-weight: bold;
            font-size: 16px;
            background: #0f172a;
            border-radius: 16px;
            border: 1px dashed #334155;
        }

        /* 📱 شاشات الجوال والأجهزة الصغيرة جداً */
        @media (max-width: 768px) {
            .navbar {
                padding: 12px 15px;
            }

            .nav-container {
                justify-content: center;
                text-align: center;
                flex-direction: column;
                gap: 8px;
            }

            .dashboard-container {
                margin: 25px auto;
                padding: 0 15px;
            }

            .welcome-box {
                text-align: center;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }

            .mini-stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 25px;
            }

            .search-wrapper {
                margin-bottom: 25px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .control-card {
                padding: 20px 15px;
                border-radius: 14px;
            }

            .card-btn {
                padding: 14px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🔑 Keyali لوحة المشرفين</a>
            <ul class="nav-links">
                <li><a href="../index.php" style="color: #f87171; font-weight: bold;">🏠 مغادرة اللوحة للموقع</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-box">
            <h2>أهلاً بك مجدداً في مركز القيادة والتحكم</h2>
            <p style="color: #94a3b8;">
                أنت مسجل الآن برتبة: <span class="role-badge"><?php echo (htmlspecialchars($_SESSION['role']) === 'root') ? '👑 المالك الحصري (Root)' : '🛠️ مشرف (Admin)'; ?></span>
            </p>
        </div>

        <div class="mini-stats-container">
            <div class="stat-mini-box">
                <div class="stat-num"><?php echo (int)$count_words; ?></div>
                <div class="stat-label">الكلمات العامة</div>
            </div>
            <div class="stat-mini-box">
                <div class="stat-num"><?php echo (int)$count_codes; ?></div>
                <div class="stat-label">الأكواد البرمجية</div>
            </div>
            <div class="stat-mini-box">
                <div class="stat-num"><?php echo (int)$count_challenges; ?></div>
                <div class="stat-label">التحديات الإنجليزية</div>
            </div>
            <div class="stat-mini-box total-box">
                <div class="stat-num"><?php echo (int)$total_items; ?></div>
                <div class="stat-label">إجمالي بنك البيانات</div>
            </div>
        </div>

        <div class="search-wrapper">
            <input type="text" id="dashboard-search" class="search-input" placeholder="ابحث سريعاً عن الأداة التي تريدها بلمح البصر... 🔍" autocomplete="off">
            <button id="clear-search" class="clear-search-btn" title="تفريغ البحث">🧹</button>
        </div>

        <div id="no-results-msg">عذراً يا شريكي، لا توجد أداة تحكم تطابق هذا الاسم! 🛑</div>

        <div class="cards-grid" id="tools-grid">

            <a href="add-word.php" class="control-card" data-title="إضافة كلمات جديدة عامة مصطلحات ترجمة">
                <div>
                    <div class="card-icon">
                        <span>🔤</span>
                        <span class="live-counter">المتوفر: <?php echo (int)$count_words; ?></span>
                    </div>
                    <h3 class="card-title">إضافة كلمات جديدة</h3>
                    <p class="card-desc">قم بتغذية النظام بكلمات ومصطلحات عامة جديدة مع ترجمتها لتظهر للاعبين في المنصة.</p>
                </div>
                <div class="card-btn">فتح الأداة ➕</div>
            </a>

            <a href="add-code.php" class="control-card" data-title="إضافة أكواد برمجية سطور مصطلحات تقنية بايثون جافا">
                <div>
                    <div class="card-icon">
                        <span>💻</span>
                        <span class="live-counter">المتوفر: <?php echo (int)$count_codes; ?></span>
                    </div>
                    <h3 class="card-title">إضافة أكواد برمجية</h3>
                    <p class="card-desc">أضف سطور كودية، مصطلحات تقنية، واربطها باللغات المتاحة (بايثون، جافا، إلخ).</p>
                </div>
                <div class="card-btn">فتح الأداة 🚀</div>
            </a>

            <a href="add-challenge.php" class="control-card card-challenges" style="border-color: rgba(245, 158, 11, 0.4);" data-title="إضافة تحدي إنجليزي جمل بنك الأسئلة الفراغ السري">
                <div>
                    <div class="card-icon">
                        <span>💡</span>
                        <span class="live-counter" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.2);">المتوفر: <?php echo (int)$count_challenges; ?></span>
                    </div>
                    <h3 class="card-title" style="color: #f59e0b;">إضافة تحدي إنجليزي</h3>
                    <p class="card-desc">قم بإضافة جمل وتحديات إنجليزية جديدة لبنك الأسئلة، مع تحديد مكان الفراغ السري والترجمة الحماسية.</p>
                </div>
                <div class="card-btn" style="border-color: #f59e0b; color: #f59e0b;">فتح الأداة 🇬🇧</div>
            </a>

            <a href="manage-all.php" class="control-card" data-title="تعديل وحذف البيانات استعراض تنقيح الأخطاء الإملائية جمل قديمة">
                <div>
                    <div class="card-icon">📝</div>
                    <h3 class="card-title">تعديل وحذف البيانات</h3>
                    <p class="card-desc">استعرض كل ما قمت بإضافته مسبقاً، واعمل على تنقيح الأخطاء الإملائية أو التخلص من الجمل القديمة.</p>
                </div>
                <div class="card-btn">إدارة المحتوى ⚙️</div>
            </a>
            <a href="admin_grammar.php" class="control-card" data-title="إدارة معمل القواعد حذف تعديل الجمل تحديات قواعد إنجليزية" style="border-color: rgba(168, 85, 247, 0.4);">
                <div>
                    <div class="card-icon">
                        <span>🧪</span>
                        <span class="live-counter" style="color: #a855f7; border-color: rgba(168, 85, 247, 0.2);">المتوفر: <?php echo (int)$count_challenges; ?></span>
                    </div>
                    <h3 class="card-title" style="color: #a855f7;">إدارة معمل القواعد</h3>
                    <p class="card-desc">تحكم بشكل كامل في جمل "معمل القواعد"، يمكنك استعراضها، تنقيح الأخطاء، أو حذف الجمل غير المرغوبة.</p>
                </div>
                <div class="card-btn" style="border-color: #a855f7; color: #a855f7;">إدارة المعمل 🧪</div>
            </a>
            <a href="admin-users.php" class="control-card" data-title="إدارة الصلاحيات والحسابات مراقبة المسجلين الهاشات ترقية الأعضاء روت">
                <div>
                    <div class="card-icon">👥</div>
                    <h3 class="card-title">إدارة الصلاحيات والحسابات</h3>
                    <p class="card-desc">مراقبة المسجلين في الموقع، رؤية الهاشات المشفرة، وترقية الأعضاء إلى مشرفين (للروت فقط).</p>
                </div>
                <div class="card-btn">استعراض الحسابات 👥</div>
            </a>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('dashboard-search');
            const clearBtn = document.getElementById('clear-search');
            const cards = document.querySelectorAll('.control-card');
            const noResultsMsg = document.getElementById('no-results-msg');

            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                let matchedCount = 0;

                cards.forEach(card => {
                    const searchData = card.getAttribute('data-title').toLowerCase();
                    if (searchData.includes(query)) {
                        card.style.display = 'flex';
                        matchedCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // إظهار أو إخفاء رسالة عدم وجود نتائج
                if (matchedCount === 0 && query !== '') {
                    noResultsMsg.style.display = 'block';
                } else {
                    noResultsMsg.style.display = 'none';
                }
            });

            // ميزة تفريغ حقل البحث دفعة واحدة بلمسة زر لتسهيل الاستخدام من الموبايل
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                cards.forEach(card => card.style.display = 'flex');
                noResultsMsg.style.display = 'none';
                searchInput.focus();
            });
        });
    </script>

</body>

</html>
<?php
ob_end_flush();
?>