<?php
include '../config/db.php';

$message = "";
$msg_type = "";

/* ================= GET CATEGORIES ================= */
$categories = $pdo->query("
    SELECT TRIM(category) AS category
    FROM grammar_challenges
    WHERE category IS NOT NULL
      AND TRIM(category) != ''
    GROUP BY TRIM(category)
    ORDER BY TRIM(category) ASC
")->fetchAll(PDO::FETCH_COLUMN);

/* ================= ADD / DELETE ================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['action']) && $_POST['action'] == 'add_challenge') {

        $category_type = $_POST['category_type'] ?? 'old';
        $category = "";

        if ($category_type === 'new') {
            $category = trim($_POST['new_category'] ?? '');
        } else {
            $category = trim($_POST['category'] ?? '');
        }

        $text = trim($_POST['text'] ?? '');
        $hint = trim($_POST['hint'] ?? '');

        if ($category == "" || $text == "" || $hint == "") {
            $message = "⚠️ الرجاء تعبئة جميع الحقول";
            $msg_type = "error";
        } else {
            $check = $pdo->prepare("
                SELECT id FROM grammar_challenges
                WHERE LOWER(TRIM(text)) = LOWER(TRIM(?))
                LIMIT 1
            ");
            $check->execute([$text]);

            if ($check->fetch()) {
                $message = "⚠️ هذه الجملة موجودة مسبقاً";
                $msg_type = "error";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO grammar_challenges (category, text, hint)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$category, $text, $hint]);

                $message = "✅ تمت إضافة الجملة بنجاح";
                $msg_type = "success";

                $categories = $pdo->query("
                    SELECT TRIM(category) AS category
                    FROM grammar_challenges
                    WHERE category IS NOT NULL
                      AND TRIM(category) != ''
                    GROUP BY TRIM(category)
                    ORDER BY TRIM(category) ASC
                ")->fetchAll(PDO::FETCH_COLUMN);
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete_challenge') {
        $stmt = $pdo->prepare("DELETE FROM grammar_challenges WHERE id = ?");
        $stmt->execute([intval($_POST['challenge_id'])]);

        $message = "🗑️ تم حذف الجملة";
        $msg_type = "success";
    }
}

/* ================= LAST 5 ================= */
$all_challenges = $pdo->query("
    SELECT *
    FROM grammar_challenges
    ORDER BY id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= FOR CHECK ================= */
$existing_sentences = $pdo->query("
    SELECT text FROM grammar_challenges
")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مختبر القواعد</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #0b1220;
            --card: #111827;
            --border: #1f2937;
            --primary: #7c3aed;
            --danger: #ef4444;
            --success: #22c55e;
            --text: #e5e7eb;
            --muted: #9ca3af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tajawal, sans-serif;
            background: linear-gradient(135deg, #0b1220, #050816);
            color: var(--text);
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .back {
            background: #1e293b;
            color: #94a3b8;
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            border: 1px solid var(--border);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .card {
            background: rgba(17, 24, 39, 0.85);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            backdrop-filter: blur(10px);
            margin-bottom: 12px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 8px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0f172a;
            color: white;
            font-family: Tajawal, sans-serif;
        }

        select {
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-family: Tajawal, sans-serif;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .badge {
            font-size: 11px;
            background: rgba(124, 58, 237, 0.15);
            color: #c4b5fd;
            padding: 3px 8px;
            border-radius: 8px;
            display: inline-block;
        }

        .del {
            background: var(--danger);
            width: auto;
            padding: 6px 10px;
            font-size: 12px;
        }

        .msg {
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 8px;
        }

        .alert {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .alert.success {
            background: rgba(34, 197, 94, .15);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, .3);
        }

        .alert.error {
            background: rgba(239, 68, 68, .15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, .3);
        }

        .new-category-box {
            display: none;
        }

        @media(max-width:768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .item {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div class="title">🧪 مختبر القواعد</div>
        <a href="index.php" class="back">⬅️ لوحة التحكم</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert <?= htmlspecialchars($msg_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="grid">

        <div class="card">
            <h3>➕ إضافة جملة</h3>

            <form method="POST">
                <input type="hidden" name="action" value="add_challenge">

                <select name="category_type" id="categoryType">
                    <option value="old">اختيار تصنيف موجود</option>
                    <option value="new">إضافة تصنيف جديد</option>
                </select>

                <div id="oldCategoryBox">
                    <select name="category">
                        <option value="">اختر التصنيف</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="newCategoryBox" class="new-category-box">
                    <input type="text" name="new_category" placeholder="اكتب التصنيف الجديد">
                </div>

                <input type="text" name="text" id="sentenceInput" placeholder="الجملة الإنجليزية">
                <input type="text" name="hint" placeholder="الترجمة">

                <div id="checkResult" class="msg"></div>

                <button type="submit">إضافة</button>
            </form>
        </div>

        <div class="card">
            <h3>🔎 تحقق قبل الإضافة</h3>

            <input type="text" id="searchCheck" placeholder="اكتب الجملة للتأكد">
            <div id="liveResult" class="msg"></div>

            <p style="font-size:12px;color:#9ca3af;margin-top:10px;">
                يتم التحقق من وجود الجملة قبل الإضافة
            </p>
        </div>

    </div>

    <div class="card">
        <h3>📚 آخر 5 جمل</h3>

        <?php if (count($all_challenges) === 0): ?>
            <p style="font-size:13px;color:#9ca3af;">لا توجد جمل حتى الآن</p>
        <?php endif; ?>

        <?php foreach ($all_challenges as $row): ?>
            <div class="item">

                <div>
                    <span class="badge"><?= htmlspecialchars($row['category']) ?></span>

                    <div style="margin-top:5px">
                        <?= htmlspecialchars($row['text']) ?>
                    </div>

                    <div style="font-size:12px;color:#9ca3af">
                        <?= htmlspecialchars($row['hint']) ?>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirm('حذف؟');">
                    <input type="hidden" name="action" value="delete_challenge">
                    <input type="hidden" name="challenge_id" value="<?= intval($row['id']) ?>">
                    <button class="del" type="submit">حذف</button>
                </form>

            </div>
        <?php endforeach; ?>

    </div>

</div>

<script>
    const existing = <?= json_encode($existing_sentences, JSON_UNESCAPED_UNICODE) ?>;

    function check(value, target) {
        value = value.trim().toLowerCase();

        if (value === "") {
            target.innerHTML = "";
            return;
        }

        let found = existing.some(x => String(x).toLowerCase().trim() === value);

        if (found) {
            target.innerHTML = "⚠️ الجملة موجودة مسبقاً";
            target.style.color = "#ef4444";
        } else {
            target.innerHTML = "✅ يمكن إضافتها";
            target.style.color = "#22c55e";
        }
    }

    document.getElementById("searchCheck").addEventListener("input", function () {
        check(this.value, document.getElementById("liveResult"));
    });

    document.getElementById("sentenceInput").addEventListener("input", function () {
        check(this.value, document.getElementById("checkResult"));
    });

    document.getElementById("categoryType").addEventListener("change", function () {
        let oldBox = document.getElementById("oldCategoryBox");
        let newBox = document.getElementById("newCategoryBox");

        if (this.value === "new") {
            oldBox.style.display = "none";
            newBox.style.display = "block";
        } else {
            oldBox.style.display = "block";
            newBox.style.display = "none";
        }
    });
</script>

</body>
</html>