<?php
// 1. 🔑 تشغيل الجلسة وحجز المخرجات فوراً لمنع أخطاء التحويل والهيدر
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_use_only_cookies', 1);
    session_start();
}

// 2. 🛡️ جدار حماية مدمج: فحص صلاحيات الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['root', 'admin'])) {
    header("Location: ../login.php?error=forbidden");
    exit();
}

// 🔄 تجديد معرف الجلسة للأمان الرقمي
session_regenerate_id(true);

// 3. 🔌 تضمين ملف الاتصال بقاعدة البيانات
include '../config/db.php';

$message = "";

// التحقق من أن الأدمن قام بضغط زر الإرسال
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $word    = trim($_POST['word_text']);
    $meaning = trim($_POST['word_meaning']);

    if (!empty($word) && !empty($meaning)) {
        try {
            // تجهيز استعلام الإدخال الآمن لعدم حدوث اختراقات SQL Injection
            $stmt = $pdo->prepare("INSERT INTO words (word_text, word_meaning) VALUES (:word, :meaning)");
            if ($stmt->execute(['word' => $word, 'meaning' => $meaning])) {
                $message = "<div class='alert success'>تم إضافة الكلمة ومعناها بنجاح يا شريكي! ✅</div>";
            } else {
                $message = "<div class='alert error'>حدث خطأ أثناء الإضافة! ❌</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>خطأ في النظام: فشل إدخال البيانات بأمان. ⚠️</div>";
        }
    } else {
        $message = "<div class='alert error'>الرجاء كتابة الكلمة ومعناها أولاً! ⚠️</div>";
    }
}

// 📊 جلب جميع الكلمات المضافة في قاعدة البيانات بالكامل لفلترتها والتحقق منها عن طريق الجافاسكريبت
$all_words = [];
try {
    $all_words = $pdo->query("SELECT word_text, word_meaning FROM words ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch all words: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة كلمات - لوحة التحكم</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: #020617;
            color: #fff;
            margin: 0;
            padding-bottom: 40px;
            font-family: 'Tajawal', sans-serif;
        }

        .navbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            padding: 15px 20px;
            text-align: right;
        }

        .logo {
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }

        .form-container {
            max-width: 600px;
            margin: 30px auto;
            background: #0f172a;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #1e293b;
            box-sizing: border-box;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.4);
            width: calc(100% - 30px);
        }

        .form-title {
            font-size: clamp(20px, 5vw, 24px);
            color: #fff;
            margin-top: 0;
            font-weight: 700;
        }

        .form-desc {
            color: #94a3b8;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
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

        .input-speech-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
            width: 100%;
        }

        .form-control {
            flex-grow: 1;
            padding: 14px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
            font-family: 'Tajawal', sans-serif;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #38bdf8;
            outline: none;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.15);
        }

        .listen-btn {
            background: #1e293b;
            border: 1px solid #334155;
            color: #38bdf8;
            padding: 12px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .listen-btn:hover {
            background: #334155;
            border-color: #38bdf8;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            background-color: #4ade80;
            color: #020617;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            font-family: 'Tajawal', sans-serif;
            margin-top: 5px;
        }

        .submit-btn:hover {
            background-color: #22c55e;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        /* ✨ قسم الجدول المشترك (5 كلمات عند الاستعداد / وبحث كامل عند الكتابة) */
        .recent-section {
            margin-top: 35px;
            border-top: 1px solid #1e293b;
            padding-top: 25px;
        }

        .recent-title {
            font-size: 16px;
            font-weight: bold;
            color: #38bdf8;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            max-height: 320px;
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
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .recent-table tr:last-child td {
            border-bottom: none;
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
            margin-top: 25px;
        }

        .back-link {
            display: inline-block;
            color: #94a3b8;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #38bdf8;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px 15px;
                margin: 15px auto;
            }

            .form-control {
                padding: 12px;
                font-size: 14px;
            }

            .listen-btn {
                padding: 11px 13px;
                font-size: 15px;
            }

            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }

            .recent-table th,
            .recent-table td {
                padding: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <a href="../index.php" class="logo">🔑 Keyali Admin</a>
    </nav>

    <div class="form-container">
        <h2 class="form-title">➕ إضافة كلمة جديدة ومعناها</h2>
        <p class="form-desc">أضف الكلمة مع ترجمتها أو معناها لرفع جودة التعليم والترجمات في منصتنا.</p>

        <?php echo $message; ?>

        <form id="word-form" action="" method="POST">
            <div class="form-group">
                <label for="word_text">الكلمة المُراد إضافتها (مثل: apple):</label>
                <div class="input-speech-wrapper">
                    <input type="text" id="word_text" name="word_text" class="form-control" placeholder="اكتب الكلمة العامة هنا..." autocomplete="off" required>
                    <button type="button" id="speak-btn" class="listen-btn" title="استمع لنطق الكلمة">🔊</button>
                </div>
            </div>

            <div class="form-group">
                <label for="word_meaning">معنى الكلمة / شرحها (مثل: تفاحة):</label>
                <input type="text" id="word_meaning" name="word_meaning" class="form-control" placeholder="اكتب معنى الكلمة ليتعلمها المستخدم..." autocomplete="off" required>
            </div>

            <button type="submit" class="submit-btn">إضافة الكلمة فوراً 🚀</button>
        </form>

        <div class="recent-section">
            <div class="recent-title">
                <span id="title-text">📝 آخر 5 كلمات تم إدخالها مؤخراً:</span>
            </div>

            <?php if (!empty($all_words)): ?>
                <input type="text" id="table-search" class="mini-search" placeholder="ابحث هنا لتصفح وفحص كامل الكلمات المضافة مسبقاً... 🔎" autocomplete="off">

                <div class="table-responsive">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>الكلمة</th>
                                <th>المعنى / الشرح</th>
                            </tr>
                        </thead>
                        <tbody id="recent-tbody">
                            <?php
                            // نمرر الاندكس للجافاسكريبت ليعرف أول 5 عناصر
                            foreach ($all_words as $index => $rw):
                            ?>
                                <tr class="word-row" data-index="<?php echo $index; ?>">
                                    <td class="target-word" style="font-weight: bold; color: #38bdf8; font-family: sans-serif;"><?php echo htmlspecialchars($rw['word_text']); ?></td>
                                    <td class="target-meaning"><?php echo htmlspecialchars($rw['word_meaning']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-match-msg">الكلمة غير موجودة مسبقاً، يمكنك إضافتها بأمان! 🟢</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">لم يتم إضافة أي كلمات في قاعدة البيانات بعد.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="back-link-container">
            <a href="index.php" class="back-link">⬅️ العودة للوحة التحكم</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wordInput = document.getElementById('word_text');
            const speakBtn = document.getElementById('speak-btn');
            const tableSearch = document.getElementById('table-search');
            const titleText = document.getElementById('title-text');
            const rows = document.querySelectorAll('.word-row');
            const noMatchMsg = document.getElementById('no-match-msg');

            // 💡 الحماية والتهيؤ الافتراضي: إظهار أول 5 صفوف فقط عند تحميل الصفحة
            function showDefaultFive() {
                rows.forEach(row => {
                    const index = parseInt(row.getAttribute('data-index'));
                    if (index < 5) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (titleText) titleText.textContent = "📝 آخر 5 كلمات تم إدخالها مؤخراً:";
            }

            // تشغيل الحالة الافتراضية
            showDefaultFive();

            // 1. ✨ ميزة النطق الصوتي للكلمة
            speakBtn.addEventListener('click', function() {
                const textToSpeak = wordInput.value.trim();
                if (textToSpeak !== '') {
                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(textToSpeak);
                    utterance.lang = 'en-US';
                    utterance.rate = 0.9;
                    window.speechSynthesis.speak(utterance);
                } else {
                    alert('الرجاء كتابة الكلمة الإنجليزية في الحقل أولاً لسماع نطقها! ⚠️');
                }
            });

            // 2. ✨ هندسة البحث الذكي: فلترة الكل عند الكتابة، والعودة لـ 5 عند فراغ الحقل
            if (tableSearch) {
                tableSearch.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();

                    // إذا كان حقل البحث فارغاً، نرجع لعرض أول 5 كلمات فقط
                    if (query === '') {
                        showDefaultFive();
                        noMatchMsg.style.display = 'none';
                        return;
                    }

                    // إذا بدأ المستخدم في الكتابة، نفتح الفلترة على كامل الكلمات بلا استثناء
                    let visibleRows = 0;
                    titleText.textContent = "🔍 نتائج الفحص والبحث في كامل قاعدة البيانات:";

                    rows.forEach(row => {
                        const text = row.querySelector('.target-word').textContent.toLowerCase();
                        const meaning = row.querySelector('.target-meaning').textContent.toLowerCase();

                        if (text.includes(query) || meaning.includes(query)) {
                            row.style.display = '';
                            visibleRows++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // التحكم في رسالة عدم وجود نتائج (الكلمة آمنة وجديدة)
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