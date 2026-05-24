<?php
include 'config/db.php';
include 'includes/header.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// 📥 1. معالجة الإضافة (كلمة، كود، لغة جديدة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type = $_POST['type'];
    $word_text = trim($_POST['word_text']);
    $word_hint = trim($_POST['word_hint']);

    $language_name = null;
    if ($type === 'code') {
        // إذا اختار لغة جديدة من القائمة
        if ($_POST['language_select'] === 'NEW_LANG') {
            $language_name = trim($_POST['new_language_name']);
        } else {
            $language_name = $_POST['language_select'];
        }
    }

    if (empty($word_text) || ($type === 'code' && empty($language_name))) {
        $error = "برجاء ملء الحقول المطلوبة واختيار اللغة! ❌";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO user_words (user_id, type, word_text, word_hint, language_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $word_text, $word_hint, $language_name]);
            $success = "تم حفظ مفرادتك البرمجية بنجاح في بنكك الخاص! 🎯";
        } catch (PDOException $e) {
            $error = "خطأ أثناء الحفظ: " . $e->getMessage();
        }
    }
}

// 🗑️ 2. معالجة الحذف
if (isset($_GET['delete'])) {
    $word_id = (int)$_GET['delete'];
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM user_words WHERE id = ? AND user_id = ?");
        $delete_stmt->execute([$word_id, $user_id]);
        $success = "تم حذف المفردة بنجاح من قاموسك الشخصي.";
    } catch (PDOException $e) {
        $error = "خطأ أثناء الحذف: " . $e->getMessage();
    }
}

// 🔍 3. جلب كلمات اللغات المضافة مسبقاً للمستخدم ليعيد استخدامها في القائمة المنسدلة
try {
    $lang_stmt = $pdo->prepare("SELECT DISTINCT language_name FROM user_words WHERE user_id = ? AND language_name IS NOT NULL");
    $lang_stmt->execute([$user_id]);
    $user_languages = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $user_languages = [];
}

// 🔍 4. جلب كلمات المستخدم الحالية بالكامل للعرض
try {
    $fetch_stmt = $pdo->prepare("SELECT * FROM user_words WHERE user_id = ? ORDER BY id DESC");
    $fetch_stmt->execute([$user_id]);
    $my_words = $fetch_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_words = [];
}
?>

<style>
    .bank-container {
        max-width: 900px;
        margin: 40px auto;
        font-family: 'Tajawal', sans-serif;
        padding: 0 15px;
    }

    .bank-card {
        background: #0f172a;
        border: 2px solid #1e293b;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
    }

    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
        text-align: right;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        color: #94a3b8;
        font-weight: bold;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-control {
        padding: 12px;
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 10px;
        color: #fff;
        font-size: 16px;
        outline: none;
    }

    .form-control:focus {
        border-color: #38bdf8;
    }

    /* أزرار اختيار النوع */
    .type-switcher {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .type-btn {
        flex: 1;
        padding: 12px;
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 10px;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        text-align: center;
        transition: 0.2s;
    }

    .type-switcher input[type="radio"] {
        display: none;
    }

    .type-switcher input[type="radio"]:checked+.type-btn {
        background: rgba(56, 189, 248, 0.15);
        border-color: #38bdf8;
        color: #38bdf8;
    }

    .btn-add {
        background: #38bdf8;
        color: #000;
        font-weight: bold;
        padding: 14px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: 0.2s;
        font-size: 16px;
        width: 100%;
        margin-top: 10px;
    }

    .btn-add:hover {
        background: #7dd3fc;
    }

    .badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-word {
        background: rgba(56, 189, 248, 0.2);
        color: #38bdf8;
    }

    .badge-code {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }

    .words-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        text-align: right;
    }

    .words-table th {
        background: #1e293b;
        color: #94a3b8;
        padding: 12px;
        border-bottom: 2px solid #334155;
    }

    .words-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #1e293b;
        color: #fff;
    }
</style>

<div class="bank-container">
    <div class="bank-card">
        <h2 style="text-align: center; margin-top: 0;">🎯 بنك المفردات والمصطلحات الذكي</h2>
        <p style="text-align: center; color: #64748b; font-size: 14px; margin-bottom: 30px;">قم ببناء وتخصيص قاموسك التعليمي الخاص، أضف كلماتك، أكوادك البرمجية، وصنفها حسب لغات البرمجة المفضلة لديك.</p>

        <?php if (!empty($error)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align:center; border: 1px solid #ef4444;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: rgba(74, 222, 128, 0.15); color: #4ade80; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align:center; border: 1px solid #4ade80;"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="type-switcher">
                <input type="radio" name="type" id="t-word" value="word" checked onclick="toggleType('word')">
                <label for="t-word" class="type-btn">🔤 كلمة عامة / إنجليزية</label>

                <input type="radio" name="type" id="t-code" value="code" onclick="toggleType('code')">
                <label for="t-code" class="type-btn">💻 مصطلح / كود برمجى</label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label id="input-label">الكلمة بالإنجليزي:</label>
                    <input type="text" name="word_text" class="form-control" placeholder="مثال: asynchronous" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>التلميح أو الترجمة العربية (اختياري):</label>
                    <input type="text" name="word_hint" class="form-control" placeholder="مثال: غير متزامن" autocomplete="off">
                </div>
            </div>

            <div class="form-row" id="language-area" style="display: none;">
                <div class="form-group">
                    <label>اختر لغة البرمجة:</label>
                    <select name="language_select" id="language_select" class="form-control" onchange="checkNewLanguage(this.value)">
                        <option value="PHP">PHP</option>
                        <option value="JavaScript">JavaScript</option>
                        <option value="Python">Python</option>
                        <option value="HTML/CSS">HTML/CSS</option>
                        <?php foreach ($user_languages as $user_lang): ?>
                            <?php if (!in_array($user_lang, ['PHP', 'JavaScript', 'Python', 'HTML/CSS'])): ?>
                                <option value="<?php echo htmlspecialchars($user_lang); ?>"><?php echo htmlspecialchars($user_lang); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <option value="NEW_LANG" style="color: #38bdf8; font-weight: bold;">➕ إضافة لغة جديدة...</option>
                    </select>
                </div>

                <div class="form-group" id="new-lang-group" style="display: none;">
                    <label style="color: #38bdf8;">اسم اللغة الجديدة:</label>
                    <input type="text" name="new_language_name" id="new_language_name" class="form-control" placeholder="مثال: C++ أو Flutter" autocomplete="off">
                </div>
            </div>

            <button type="submit" class="btn-add">🚀 حفظ في قاموسي الشخصي</button>
        </form>

        <hr style="border-color: #1e293b; margin: 30px 0;">

        <h3 style="color: #38bdf8; text-align: right;">📋 محتويات بنكك التعليمي</h3>
        <?php if (empty($my_words)): ?>
            <div style="text-align: center; color: #64748b; padding: 30px 0;">بنك كلماتك فارغ حالياً. أضف كلماتك أو أكوادك بالرقم المفضل لديك لتراها هنا!</div>
        <?php else: ?>
            <table class="words-table">
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>المفردة / السطر البرمجي</th>
                        <th>التصنيف / اللغة</th>
                        <th>التلميح المساعد</th>
                        <th style="text-align: center;">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_words as $word): ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo $word['type'] === 'word' ? 'badge-word' : 'badge-code'; ?>">
                                    <?php echo $word['type'] === 'word' ? '🔤 كلمة' : '💻 كود'; ?>
                                </span>
                            </td>
                            <td style="font-family: 'Fira Code', monospace; color: #4ade80;"><?php echo htmlspecialchars($word['word_text']); ?></td>
                            <td style="color: #fbbf24; font-weight: bold;"><?php echo htmlspecialchars($word['language_name'] ?: 'عامة'); ?></td>
                            <td style="color: #94a3b8;"><?php echo htmlspecialchars($word['word_hint'] ?: '---'); ?></td>
                            <td style="text-align: center;"><a href="my-words.php?delete=<?php echo $word['id']; ?>" style="color: #f87171; text-decoration: none;" onclick="return confirm('حذف؟')">❌ حذف</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleType(type) {
        const langArea = document.getElementById('language-area');
        const inputLabel = document.getElementById('input-label');

        if (type === 'word') {
            langArea.style.display = 'none';
            inputLabel.textContent = 'الكلمة بالإنجليزي:';
        } else {
            langArea.style.display = 'flex';
            inputLabel.textContent = 'الرمز أو السطر البرمجي:';
        }
    }

    function checkNewLanguage(val) {
        const newLangGroup = document.getElementById('new-lang-group');
        const newLangInput = document.getElementById('new-language_name');

        if (val === 'NEW_LANG') {
            newLangGroup.style.display = 'flex';
            newLangInput.setAttribute('required', 'required');
            newLangInput.focus();
        } else {
            newLangGroup.style.display = 'none';
            newLangInput.removeAttribute('required');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>