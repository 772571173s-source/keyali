<?php
// 1. 🔑 تشغيل الجلسة وفحص الصلاحيات بأعلى معايير الحماية (OWASP Architecture)
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_use_only_cookies', 1);
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// تجديد المعرف دورياً لمنع سرقة الجلسة عبر الجوال
session_regenerate_id(true);

// 2. 🔌 الاتصال بقاعدة البيانات
include '../config/db.php';

$message = "";

// 🌟 التقاط رسائل النجاح
if (isset($_GET['success']) && $_GET['success'] === 'update') {
    $message = "<div class='alert success'>تم تحديث البيانات بنجاح وتأمين الحفظ لايف! ✨</div>";
}

// 3. 🗑️ محرك الحذف الآمن والمطور
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $id = intval($_GET['delete']);
    $type = $_GET['type'];
    
    // التحقق الصارم من نوع المحتوى لحماية الاستعلامات
    $allowed_types = ['word', 'code', 'challenge'];
    if (in_array($type, $allowed_types) && $id > 0) {
        try {
            if ($type === 'word') {
                $del_stmt = $pdo->prepare("DELETE FROM words WHERE id = ?");
                $del_stmt->execute([$id]);
                $message = "<div class='alert success'>تم حذف الكلمة بنجاح! 🗑️</div>";
            } elseif ($type === 'code') {
                $del_stmt = $pdo->prepare("DELETE FROM code_terms WHERE id = ?");
                $del_stmt->execute([$id]);
                $message = "<div class='alert success'>تم حذف المصطلح البرمجي بنجاح! 🗑️</div>";
            } elseif ($type === 'challenge') { 
                $del_stmt = $pdo->prepare("DELETE FROM english_challenges WHERE id = ?");
                $del_stmt->execute([$id]);
                $message = "<div class='alert success'>تم حذف تحدي الجملة الإنجليزية بنجاح! 🗑️</div>";
            }
        } catch (PDOException $e) {
            error_log("Delete Error: " . $e->getMessage());
            $message = "<div class='alert error'>فشل الحذف، قد تكون البيانات مرتبطة بعناصر أخرى! ⚠️</div>";
        }
    }
}

// 4. 📊 جلب البيانات من الجداول
try {
    $words_stmt = $pdo->query("SELECT * FROM words ORDER BY id DESC");
    $all_words = $words_stmt->fetchAll(PDO::FETCH_ASSOC);

    $codes_stmt = $pdo->query("
        SELECT code_terms.*, languages.lang_name 
        FROM code_terms 
        LEFT JOIN languages ON code_terms.language_id = languages.id 
        ORDER BY code_terms.id DESC
    ");
    $all_codes = $codes_stmt->fetchAll(PDO::FETCH_ASSOC);

    $challenges_stmt = $pdo->query("SELECT * FROM english_challenges ORDER BY id DESC");
    $all_challenges = $challenges_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fetch Data Error: " . $e->getMessage());
    die("خطأ داخلي في جلب البيانات من قاعدة البيانات.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مركز إدارة وتعديل المحتوى 📝</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #020617; color: #fff; margin: 0; padding-bottom: 40px; }
        
        /* الـ Navbar المتجاوبة بالكامل */
        .navbar { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 15px 20px; }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .logo { font-size: 18px; font-weight: bold; color: #fff; text-decoration: none; }
        .nav-links { margin: 0; padding: 0; list-style: none; display: flex; gap: 15px; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; font-size: 14px; color: #94a3b8; font-weight: bold; }
        .nav-links a:hover { color: #fff; }

        .manage-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; box-sizing: border-box; }
        .page-header { margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 20px; }
        .page-header h2 { font-size: clamp(20px, 4vw, 28px); margin: 0; }
        
        /* ✨ محرك البحث الموحد والذكي المطور */
        .search-wrapper { position: relative; margin-bottom: 25px; max-width: 100%; }
        .main-search-input { width: 100%; padding: 16px 50px 16px 45px; background: #0f172a; border: 2px solid #334155; border-radius: 12px; color: #fff; font-size: 16px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; outline: none; transition: all 0.3s ease; }
        .main-search-input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.15); }
        .search-icon { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 18px; pointer-events: none; }
        .clear-search-btn { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #475569; font-size: 18px; cursor: pointer; display: none; transition: color 0.2s; }
        .clear-search-btn:hover { color: #f87171; }

        /* التبويبات المتجاوبة المريحة للأصابع */
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #1e293b; padding-bottom: 12px; flex-wrap: wrap; }
        .tab-btn { background: #1e293b; color: #94a3b8; padding: 12px 18px; border-radius: 8px; border: 1px solid #334155; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.3s; text-decoration: none; text-align: center; flex-grow: 1; }
        .tab-btn:hover { color: #fff; background: #334155; }
        .tab-btn.active { background: #38bdf8; color: #020617; border-color: #38bdf8; }
        .tab-btn.active-challenge { background: #f59e0b; color: #020617; border-color: #f59e0b; }

        .table-wrapper { background: #0f172a; border: 1px solid #334155; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.4); margin-bottom: 40px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: right; }
        .data-table th { background: #1e293b; padding: 15px; color: #38bdf8; font-weight: bold; font-size: 15px; border-bottom: 2px solid #334155; }
        .data-table td { padding: 15px; border-bottom: 1px solid #334155; font-size: 14px; color: #f8fafc; vertical-align: middle; }
        .data-table tr:hover { background: rgba(56, 189, 248, 0.02); }
        
        /* الأزرار والإجراءات مع مساحة ضغط مثالية للّمس */
        .action-link { display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none; transition: 0.2s; margin: 4px; min-width: 80px; }
        .action-link.edit { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; }
        .action-link.edit:hover { background: #38bdf8; color: #000; }
        .action-link.edit-challenge { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; }
        .action-link.edit-challenge:hover { background: #f59e0b; color: #000; }
        .action-link.delete { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid #ef4444; }
        .action-link.delete:hover { background: #ef4444; color: #fff; }

        .code-render { font-family: 'Fira Code', monospace; background: #020617; padding: 8px 12px; border-radius: 6px; color: #4ade80; border: 1px solid #1e293b; font-size: 13px; display: inline-block; direction: ltr; text-align: left; max-width: 100%; overflow-x: auto; white-space: pre-wrap; word-break: break-all; box-sizing: border-box; }
        .lang-badge { background: #a855f7; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert.success { background: rgba(74, 222, 128, 0.2); color: #4ade80; border: 1px solid #4ade80; }
        .alert.error { background: rgba(248, 113, 113, 0.2); color: #f87171; border: 1px solid #f87171; }

        /* رسالة عدم مطابقة البحث داخل التبويبات */
        .no-results-msg { display: none; padding: 30px; text-align: center; color: #64748b; font-size: 15px; font-weight: bold; background: #0f172a; border: 1px dashed #334155; border-radius: 12px; margin-bottom: 40px; }

        /* 📱 تفجير مرونة شاشات الجوال وكسر نظام الجداول المزعج */
        @media (max-width: 768px) {
            .nav-container { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; padding-top: 10px; }
            .manage-container { padding: 0 12px; margin: 20px auto; }
            
            /* تحويل الجدول بالكامل لنظام كروت عرضية فريدة */
            .data-table, .data-table thead, .data-table tbody, .data-table th, .data-table td, .data-table tr { 
                display: block; 
                width: 100%;
                box-sizing: border-box;
            }
            .data-table thead { display: none; } /* إخفاء الهيدر الرئيسي لأنه غير مفيد عمودياً */
            
            .data-table tr {
                background: #0f172a;
                border: 1px solid #334155;
                margin-bottom: 15px;
                border-radius: 12px;
                padding: 15px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            }
            
            .data-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #1e293b;
                text-align: left;
            }
            .data-table td:last-child { border-bottom: none; justify-content: center; padding-top: 15px; flex-wrap: wrap; }
            
            /* إضافة العناوين التوضيحية ديناميكياً قبل البيانات في الجوال */
            .data-table td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #38bdf8;
                margin-right: 5px;
                text-align: right;
                font-size: 13px;
            }
            
            #challenges-section .data-table td { text-align: left; }
            .code-render { max-width: 180px; }
            .action-link { width: 45%; } /* جعل الأزرار تأخذ نصف المساحة جنباً إلى جنب لتسهيل اللمس بجهازك */
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">🔑 Keyali Control Center</a>
        <ul class="nav-links">
            <li><a href="index.php">الرئيسية للوحة 🛠️</a></li>
            <li><a href="add-word.php">إضافة كلمة ➕</a></li>
            <li><a href="add-code.php">إضافة كود 💻</a></li>
            <li><a href="add-challenge.php" style="color: #f59e0b;">إضافة تحدي 🇬🇧</a></li>
        </ul>
    </div>
</nav>

<div class="manage-container">
    <div class="page-header">
        <h2>📝 مركز إدارة وتعديل المحتوى الشامل</h2>
        <p style="color: #94a3b8; margin-top: 5px;">من هنا يمكنك استعراض كافة الكلمات البرمجية والأكواد والتحديات المتاحة، مراجعتها، أو تصفيتها لايف.</p>
    </div>

    <?php echo $message; ?>

    <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="main-search" class="main-search-input" placeholder="اكتب الحروف الأولى للبحث السريع في التبويب المفتوح حالياً... ⚡" autocomplete="off">
        <button type="button" id="clear-search" class="clear-search-btn">❌</button>
    </div>

    <div class="tabs">
        <a href="#words-section" class="tab-btn active" id="btn-w" onclick="switchTab('words')">🔤 الكلمات (<span id="count-words"><?php echo count($all_words); ?></span>)</a>
        <a href="#codes-section" class="tab-btn" id="btn-c" onclick="switchTab('codes')">💻 الأكواد (<span id="count-codes"><?php echo count($all_codes); ?></span>)</a>
        <a href="#challenges-section" class="tab-btn" id="btn-ch" onclick="switchTab('challenges')">🇬🇧 التحديات (<span id="count-challenges"><?php echo count($all_challenges); ?></span>)</a>
    </div>

    <div id="words-section" class="tab-content">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>الكلمة </th>
                        <th>المعنى والشرح بالتفصيل</th>
                        <th style="width: 220px; text-align: center;">العمليات والإجراءات</th>
                    </tr>
                </thead>
                <tbody id="words-tbody">
                    <?php if(empty($all_words)): ?>
                        <tr><td colspan="4" style="text-align: center; color: #64748b;">لا توجد كلمات مضافة حتى الآن!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($all_words as $word): ?>
                        <tr class="item-row" data-search-text="<?php echo htmlspecialchars(strtolower($word['word_text'])); ?>">
                            <td data-label="المعرف"><strong>#<?php echo (int)$word['id']; ?></strong></td>
                            <td data-label="الكلمة" style="color: #38bdf8; font-weight: bold;"><?php echo htmlspecialchars($word['word_text']); ?></td>
                            <td data-label="الشرح"><?php echo htmlspecialchars($word['word_meaning']); ?></td>
                            <td>
                                <a href="edit-word.php?id=<?php echo (int)$word['id']; ?>" class="action-link edit">✏️ تعديل</a>
                                <a href="manage-all.php?delete=<?php echo (int)$word['id']; ?>&type=word" class="action-link delete" onclick="return confirm('هل أنت متأكد تماماً من حذف هذه الكلمة نهائياً؟ ⚠️')">🗑️ حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="words-no-results" class="no-results-msg">⚠️ لا توجد كلمات تبدأ بهذا الحرف في القاموس حالياً!</div>
    </div>

    <div id="codes-section" class="tab-content" style="display: none;">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 120px;">اللغة البرمجية</th>
                        <th>الكود / السطر البرمجي</th>
                        <th>اسم أو وصف المصطلح</th>
                        <th style="width: 220px; text-align: center;">العمليات والإجراءات</th>
                    </tr>
                </thead>
                <tbody id="codes-tbody">
                    <?php if(empty($all_codes)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">لا توجد أكواد برمجية مضافة حتى الآن!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($all_codes as $code): ?>
                        <tr class="item-row" data-search-text="<?php echo htmlspecialchars(strtolower($code['description'] . ' ' . $code['code_text'])); ?>">
                            <td data-label="المعرف"><strong>#<?php echo (int)$code['id']; ?></strong></td>
                            <td data-label="اللغة"><span class="lang-badge"><?php echo htmlspecialchars($code['lang_name'] ?? 'عامة'); ?></span></td>
                            <td data-label="الكود"><div class="code-render"><?php echo htmlspecialchars($code['code_text']); ?></div></td>
                            <td data-label="الوصف" style="font-weight: bold; color: #f59e0b;"><?php echo htmlspecialchars($code['description']); ?></td>
                            <td>
                                <a href="edit-code.php?id=<?php echo (int)$code['id']; ?>" class="action-link edit">✏️ تعديل</a>
                                <a href="manage-all.php?delete=<?php echo (int)$code['id']; ?>&type=code" class="action-link delete" onclick="return confirm('هل أنت متأكد من حذف هذا السطر البرمجي ومصطلحه؟ ⚠️')">🗑️ حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="codes-no-results" class="no-results-msg">⚠️ لا توجد مصطلحات برمجية تطابق بداية هذا البحث!</div>
    </div>

    <div id="challenges-section" class="tab-content" style="display: none;">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>الجملة الإنجليزية</th>
                        <th style="width: 150px;">الكلمة الصحيحة</th>
                        <th>الترجمة العربية</th>
                        <th style="width: 220px; text-align: center;">العمليات والإجراءات</th>
                    </tr>
                </thead>
                <tbody id="challenges-tbody">
                    <?php if(empty($all_challenges)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">لا توجد جمل إنجليزية مضافة حتى الآن!</td></tr>
                    <?php endif; ?>
                    <?php foreach ($all_challenges as $challenge): ?>
                        <tr class="item-row" data-search-text="<?php echo htmlspecialchars(strtolower($challenge['sentence'])); ?>">
                            <td data-label="المعرف"><strong>#<?php echo (int)$challenge['id']; ?></strong></td>
                            <td data-label="الجملة" style="direction: ltr; text-align: left; font-family: 'Fira Code', monospace; color: #e2e8f0;"><?php echo htmlspecialchars($challenge['sentence']); ?></td>
                            <td data-label="الإجابة" style="color: #4ade80; font-weight: bold; font-family: 'Fira Code', monospace;"><?php echo htmlspecialchars($challenge['correct_word']); ?></td>
                            <td data-label="الترجمة"><?php echo htmlspecialchars($challenge['translation']); ?></td>
                            <td>
                                <a href="edit-challenge.php?id=<?php echo (int)$challenge['id']; ?>" class="action-link edit edit-challenge">✏️ تعديل</a>
                                <a href="manage-all.php?delete=<?php echo (int)$challenge['id']; ?>&type=challenge" class="action-link delete" onclick="return confirm('هل أنت متأكد من حذف هذا التحدي الإنجليزي نهائياً؟ ⚠️')">🗑️ حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="challenges-no-results" class="no-results-msg">⚠️ لا توجد جمل أو تحديات تبدأ بهذا النص!</div>
    </div>

</div>

<script>
    // الاحتفاظ بالتبويب النشط حالياً لتوجيه محرك البحث إليه
    let currentActiveTab = 'words';

    // 🔄 محرك التبديل بين التبويبات
    function switchTab(type) {
        currentActiveTab = type;
        
        const wordsSec = document.getElementById('words-section');
        const codesSec = document.getElementById('codes-section');
        const challengesSec = document.getElementById('challenges-section');
        
        const btnW = document.getElementById('btn-w');
        const btnC = document.getElementById('btn-c');
        const btnCh = document.getElementById('btn-ch');

        wordsSec.style.display = 'none';
        codesSec.style.display = 'none';
        challengesSec.style.display = 'none';
        
        btnW.classList.remove('active', 'active-challenge');
        btnC.classList.remove('active', 'active-challenge');
        btnCh.classList.remove('active', 'active-challenge');

        if (type === 'words') {
            wordsSec.style.display = 'block';
            btnW.classList.add('active');
        } else if (type === 'codes') {
            codesSec.style.display = 'block';
            btnC.classList.add('active');
        } else if (type === 'challenges') {
            challengesSec.style.display = 'block';
            btnCh.classList.add('active-challenge');
        }

        // إعادة تشغيل الفلترة فوراً عند تبديل التبويب بناءً على النص المكتوب حالياً في السيرش
        runLiveSearch();
    }

    // 🔎 محرك الفحص والفلترة بالبدايات (Starts-With Engine) مع تحديث العدادات لايف
    const mainSearch = document.getElementById('main-search');
    const clearSearch = document.getElementById('clear-search');

    function runLiveSearch() {
        const query = mainSearch.value.trim().toLowerCase();
        
        // إظهار أو إخفاء زر المسح الفوري ❌
        if (query.length > 0) {
            clearSearch.style.display = 'block';
        } else {
            clearSearch.style.display = 'none';
        }

        // تحديد الحاوية النشطة الحالية فقط للفلترة داخلها لضمان السرعة الأسطورية
        const activeContainer = document.getElementById(`${currentActiveTab}-section`);
        const rows = activeContainer.querySelectorAll('.item-row');
        const noResultsMsg = document.getElementById(`${currentActiveTab}-no-results`);
        
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.getAttribute('data-search-text') || '';
            
            // التصفية بناءً على البداية (Starts With):
            // نتحقق مما إذا كان النص بأكمله يبدأ بالاستعلام، أو يحتوي على كلمة مفرده تبدأ بالاستعلام (حماية الجمل الطويلة)
            const isMatch = text.startsWith(query) || text.split(/\s+/).some(word => word.startsWith(query));

            if (isMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // إظهار رسالة "لا توجد نتائج" إذا كان العداد صفراً
        if (rows.length > 0 && visibleCount === 0) {
            noResultsMsg.style.display = 'block';
            activeContainer.querySelector('.table-wrapper').style.display = 'none';
        } else {
            noResultsMsg.style.display = 'none';
            if(rows.length > 0) {
                activeContainer.querySelector('.table-wrapper').style.display = 'block';
            }
        }
    }

    // الاستماع لحدث الكتابة داخل شريط البحث
    mainSearch.addEventListener('input', runLiveSearch);

    // زر الحذف والمسح الفوري لنص البحث ❌
    clearSearch.addEventListener('click', function() {
        mainSearch.value = '';
        runLiveSearch();
        mainSearch.focus();
    });
</script>

</body>
</html>
<?php ob_end_flush(); ?>