<?php
// 1. 🔑 تشغيل الجلسة وحجز المخرجات فوراً لمنع أخطاء التحويل والهيدر
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_use_only_cookies', 1);
    session_start();
}

// 2. 🛡️ جدار حماية مدمج: فحص صلاحيات الدخول (OWASP: Broken Access Control)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 🔄 تجديد معرف الجلسة للأمان الرقمي
session_regenerate_id(true);

// 3. 🔌 تضمين ملف الاتصال بقاعدة البيانات
include '../config/db.php';

$lang_message = "";
$code_message = "";

// 1. معالجة إضافة لغة جديدة إذا تم إرسال نموذج اللغات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lang_submit'])) {
    $new_lang = trim($_POST['lang_name']);

    if (!empty($new_lang)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO languages (lang_name) VALUES (:lang_name)");
            if ($stmt->execute(['lang_name' => $new_lang])) {
                $lang_message = "<div class='alert success'>تم إضافة اللغة البرمجية بنجاح! 🎯</div>";
            }
        } catch (PDOException $e) {
            $lang_message = "<div class='alert error'>هذه اللغة مضافة بالفعل أو حدث خطأ! ⚠️</div>";
        }
    } else {
        $lang_message = "<div class='alert error'>الرجاء كتابة اسم اللغة! ⚠️</div>";
    }
}

// 2. معالجة إضافة المصطلح البرمجي إذا تم إرسال نموذج الأكواد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_code_submit'])) {
    $language_id = $_POST['language_id'];
    $code_text   = trim($_POST['code_text']);
    $description = trim($_POST['description']);

    if (!empty($language_id) && !empty($code_text)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO code_terms (language_id, code_text, description) VALUES (:lang_id, :code_text, :desc)");
            if ($stmt->execute(['lang_id' => $language_id, 'code_text' => $code_text, 'desc' => $description])) {
                $code_message = "<div class='alert success'>تم إضافة المصطلح البرمجي بنجاح! 💻</div>";
            } else {
                $code_message = "<div class='alert error'>حدث خطأ أثناء الحفظ! ❌</div>";
            }
        } catch (PDOException $e) {
            $code_message = "<div class='alert error'>خطأ أمني في معالجة البيانات الخارجية. ⚠️</div>";
        }
    } else {
        $code_message = "<div class='alert error'>الرجاء ملء الحقول المطلوبة! ⚠️</div>";
    }
}

// جلب اللغات البرمجية المتوفرة لتغذية القائمة المنسدلة
try {
    $lang_stmt = $pdo->query("SELECT * FROM languages ORDER BY lang_name ASC");
    $languages = $lang_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب تصنيفات اللغات البرمجية.");
}

// 📊 جلب جميع المصطلحات البرمجية مع اسم لغتها لفلترتها والتحقق منها لايف
$all_codes = [];
try {
    $all_codes = $pdo->query("SELECT ct.*, l.lang_name FROM code_terms ct JOIN languages l ON ct.language_id = l.id ORDER BY ct.id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch code terms: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة اللغات والأكواد - لوحة التحكم</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #020617;
            color: #fff;
            margin: 0;
            font-family: 'Tajawal', sans-serif;
            padding-bottom: 40px;
        }

        .navbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 15px 20px;
        }

        .nav-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin: 30px auto 15px auto;
            max-width: 1100px;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .form-container {
            background: #0f172a;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #1e293b;
            height: fit-content;
            box-sizing: border-box;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .form-title {
            color: #fff;
            margin-top: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .form-desc {
            color: #94a3b8;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #38bdf8;
            font-weight: bold;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
        }

        .form-control:focus {
            border-color: #38bdf8;
            outline: none;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            direction: ltr;
            text-align: left;
            resize: vertical;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        .alert.success {
            background: rgba(74, 222, 128, 0.1);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .alert.error {
            background: rgba(248, 113, 113, 0.1);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .submit-btn-green {
            background-color: #4ade80;
            color: #020617;
        }

        .submit-btn-green:hover {
            background-color: #22c55e;
        }

        .submit-btn-blue {
            background-color: #38bdf8;
            color: #020617;
        }

        .submit-btn-blue:hover {
            background-color: #0ea5e9;
        }

        /* ✨ قسم فحص الأكواد المدمج أسفل الشبكة */
        .recent-section-container {
            max-width: 1100px;
            margin: 0 auto 30px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .recent-section {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .recent-title {
            font-size: 16px;
            font-weight: bold;
            color: #38bdf8;
            margin-bottom: 15px;
        }

        .mini-search {
            width: 100%;
            padding: 14px 15px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            margin-bottom: 15px;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
            transition: 0.2s;
        }

        .mini-search:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #1e293b;
            background: #0f172a;
            max-height: 350px;
            overflow-y: auto;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 14px;
        }

        .recent-table th {
            background: #1e293b;
            color: #94a3b8;
            padding: 12px;
            font-weight: bold;
            border-bottom: 1px solid #1e293b;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .recent-table td {
            padding: 12px;
            border-bottom: 1px solid #1e293b;
            color: #e2e8f0;
        }

        .recent-table tr:last-child td {
            border-bottom: none;
        }

        .badge-lang {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .code-snippet {
            font-family: 'Fira Code', monospace;
            color: #4ade80;
            background: #1e293b;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
            direction: ltr;
        }

        #no-match-msg {
            display: none;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            font-weight: bold;
        }

        .back-link-container {
            text-align: center;
            margin: 20px auto 40px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-block;
            color: #94a3b8;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #38bdf8;
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .admin-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                margin: 15px auto;
                padding: 0 15px;
            }

            .recent-section-container {
                padding: 0 15px;
            }

            .form-container,
            .recent-section {
                padding: 20px;
                border-radius: 14px;
            }

            .form-title {
                font-size: 18px;
            }

            .form-control {
                padding: 12px;
                font-size: 14px;
            }

            .submit-btn {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🔑 Keyali Admin</a>
        </div>
    </nav>

    <div class="admin-grid">
        <div class="form-container">
            <h3 class="form-title">🌐 إضافة لغة برمجية جديدة</h3>
            <p class="form-desc">مثل: C++, Ruby, Go</p>

            <?php echo $lang_message; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="lang_name">اسم اللغة:</label>
                    <input type="text" id="lang_name" name="lang_name" class="form-control" placeholder="اكتب اسم اللغة هنا..." autocomplete="off" required>
                </div>
                <button type="submit" name="add_lang_submit" class="submit-btn submit-btn-green">حفظ اللغة ➕</button>
            </form>
        </div>

        <div class="form-container">
            <h3 class="form-title">💻 إضافة مصطلح أو كود برمجي جديد</h3>
            <p class="form-desc">اربط المصطلحات باللغات المتوفرة لتصنيفها تلقائياً بالمنصة.</p>

            <?php echo $code_message; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="language_id">اختر اللغة البرمجية:</label>
                    <select id="language_id" name="language_id" class="form-control" required>
                        <option value="">-- اختر اللغة من القائمة --</option>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['lang_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="code_text">الكود أو السطر البرمجي:</label>
                    <textarea id="code_text" name="code_text" class="form-control" rows="5" placeholder="مثال:&#10;print('Hello World')" required></textarea>
                </div>

                <div class="form-group">
                    <label for="description">وصف أو اسم المصطلح:</label>
                    <input type="text" id="description" name="description" class="form-control" placeholder="مثال: دالة الطباعة في بايثون">
                </div>

                <button type="submit" name="add_code_submit" class="submit-btn submit-btn-blue">حفظ الكود في النظام 🚀</button>
            </form>
        </div>
    </div>

    <div class="recent-section-container">
        <div class="recent-section">
            <div class="recent-title">
                <span id="title-text">📝 آخر 5 مصطلحات برمجية تم إدخالها مؤخراً:</span>
            </div>

            <?php if (!empty($all_codes)): ?>
                <input type="text" id="table-search" class="mini-search" placeholder="ابحث هنا لتصفح وفحص كامل الأكواد المضافة مسبقاً (اسم المصطلح، الكود، أو اللغة)... 🔎" autocomplete="off">

                <div class="table-responsive">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>اللغة</th>
                                <th>المصطلح / الوصف</th>
                                <th>السطر البرمجي</th>
                            </tr>
                        </thead>
                        <tbody id="recent-tbody">
                            <?php foreach ($all_codes as $index => $rc): ?>
                                <tr class="code-row" data-index="<?php echo $index; ?>">
                                    <td><span class="badge-lang target-lang"><?php echo htmlspecialchars($rc['lang_name']); ?></span></td>
                                    <td class="target-desc" style="font-weight: bold; color: #e2e8f0;"><?php echo htmlspecialchars($rc['description']); ?></td>
                                    <td><code class="code-snippet target-text"><?php echo htmlspecialchars($rc['code_text']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-match-msg">المصطلح أو الكود غير مضاف مسبقاً، يمكنك إضافته بأمان! 🟢</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">لم يتم إضافة أي أكواد برمجية بعد.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-link-container">
        <a href="index.php" class="back-link">⬅️ العودة للوحة التحكم الرئيسية</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableSearch = document.getElementById('table-search');
            const titleText = document.getElementById('title-text');
            const rows = document.querySelectorAll('.code-row');
            const noMatchMsg = document.getElementById('no-match-msg');

            // تهيئة العرض الافتراضي: أول 5 صفوف فقط
            function showDefaultFive() {
                rows.forEach(row => {
                    const index = parseInt(row.getAttribute('data-index'));
                    if (index < 5) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (titleText) titleText.textContent = "📝 آخر 5 مصطلحات برمجية تم إدخالها مؤخراً:";
            }

            showDefaultFive();

            // هندسة الفلترة الذكية والبحث في كامل قاعدة البيانات بقناة جدول واحدة
            if (tableSearch) {
                tableSearch.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();

                    if (query === '') {
                        showDefaultFive();
                        noMatchMsg.style.display = 'none';
                        return;
                    }

                    let visibleRows = 0;
                    titleText.textContent = "🔍 نتائج الفحص والبحث في كامل بنك الأكواد والمصطلحات:";

                    rows.forEach(row => {
                        const lang = row.querySelector('.target-lang').textContent.toLowerCase();
                        const desc = row.querySelector('.target-desc').textContent.toLowerCase();
                        const code = row.querySelector('.target-text').textContent.toLowerCase();

                        if (lang.includes(query) || desc.includes(query) || code.includes(query)) {
                            row.style.display = '';
                            visibleRows++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    if (visibleRows === 0) {
                        noMatchMsg.style.display = 'block';
                    } else {
                        noMatchMsg.style.display = 'none';
                    }
                });
            }
        });
    </script>

</body>

</html>
<?php
ob_end_flush();
?>