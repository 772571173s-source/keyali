<?php
include '../config/db.php';

$message = "";
$msg_type = "";

/* ================= ADD / DELETE ================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['action']) && $_POST['action'] == 'add_challenge') {
        $stmt = $pdo->prepare("INSERT INTO grammar_challenges (category, text, hint) VALUES (?, ?, ?)");
        $stmt->execute([
            trim($_POST['category']),
            trim($_POST['text']),
            trim($_POST['hint'])
        ]);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete_challenge') {
        $stmt = $pdo->prepare("DELETE FROM grammar_challenges WHERE id = ?");
        $stmt->execute([intval($_POST['challenge_id'])]);
    }
}

/* ================= LAST 5 ================= */
$all_challenges = $pdo->query("
    SELECT * FROM grammar_challenges
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
:root{
    --bg:#0b1220;
    --card:#111827;
    --border:#1f2937;
    --primary:#7c3aed;
    --danger:#ef4444;
    --text:#e5e7eb;
    --muted:#9ca3af;
}

*{box-sizing:border-box}

body{
    margin:0;
    font-family:Tajawal;
    background:linear-gradient(135deg,#0b1220,#050816);
    color:var(--text);
}

/* container */
.container{
    max-width:900px;
    margin:auto;
    padding:15px;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.title{
    font-size:18px;
    font-weight:bold;
}

/* BACK BUTTON */
.back{
    background:#1e293b;
    color:#94a3b8;
    padding:8px 12px;
    border-radius:10px;
    text-decoration:none;
    font-size:13px;
    border:1px solid var(--border);
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

/* CARD */
.card{
    background:rgba(17,24,39,0.85);
    border:1px solid var(--border);
    border-radius:14px;
    padding:14px;
    backdrop-filter: blur(10px);
}

/* INPUTS */
input,textarea{
    width:100%;
    padding:10px;
    margin-top:6px;
    border-radius:10px;
    border:1px solid var(--border);
    background:#0f172a;
    color:white;
}

/* BUTTON */
button{
    width:100%;
    padding:10px;
    border:none;
    border-radius:10px;
    background:var(--primary);
    color:white;
    cursor:pointer;
}

/* ITEM */
.item{
    display:flex;
    justify-content:space-between;
    gap:10px;
    padding:10px 0;
    border-bottom:1px solid var(--border);
}

/* BADGE */
.badge{
    font-size:11px;
    background:rgba(124,58,237,0.15);
    color:#c4b5fd;
    padding:3px 8px;
    border-radius:8px;
    display:inline-block;
}

/* DELETE */
.del{
    background:var(--danger);
    width:auto;
    padding:6px 10px;
    font-size:12px;
}

/* MSG */
.msg{
    font-size:12px;
    margin-top:5px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:768px){
    .grid{
        grid-template-columns:1fr;
    }

    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }

    .item{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="header">
    <div class="title">🧪 مختبر القواعد</div>
    <a href="index.php" class="back">⬅️ لوحة التحكم</a>
</div>

<!-- GRID -->
<div class="grid">

    <!-- ADD -->
    <div class="card">
        <h3>➕ إضافة جملة</h3>

        <form method="POST">
            <input type="hidden" name="action" value="add_challenge">

            <input type="text" name="category" placeholder="التصنيف">
            <input type="text" name="text" id="sentenceInput" placeholder="الجملة الإنجليزية">
            <input type="text" name="hint" placeholder="الترجمة">

            <div id="checkResult" class="msg"></div>

            <button>إضافة</button>
        </form>
    </div>

    <!-- CHECK -->
    <div class="card">
        <h3>🔎 تحقق قبل الإضافة</h3>

        <input type="text" id="searchCheck" placeholder="اكتب الجملة للتأكد">
        <div id="liveResult" class="msg"></div>

        <p style="font-size:12px;color:#9ca3af;margin-top:10px;">
            يتم التحقق من وجود الجملة قبل الإضافة
        </p>
    </div>

</div>

<!-- LAST 5 -->
<div class="card">
    <h3>📚 آخر 5 جمل</h3>

    <?php foreach ($all_challenges as $row): ?>
    <div class="item">

        <div>
            <span class="badge"><?= htmlspecialchars($row['category']) ?></span>
            <div style="margin-top:5px"><?= htmlspecialchars($row['text']) ?></div>
            <div style="font-size:12px;color:#9ca3af">
                <?= htmlspecialchars($row['hint']) ?>
            </div>
        </div>

        <form method="POST" onsubmit="return confirm('حذف؟');">
            <input type="hidden" name="action" value="delete_challenge">
            <input type="hidden" name="challenge_id" value="<?= $row['id'] ?>">
            <button class="del">حذف</button>
        </form>

    </div>
    <?php endforeach; ?>

</div>

</div>

<!-- JS -->
<script>
const existing = <?= json_encode($existing_sentences) ?>;

/* check function */
function check(value, target){
    value = value.trim().toLowerCase();

    if(value === ""){
        target.innerHTML = "";
        return;
    }

    let found = existing.some(x => x.toLowerCase().trim() === value);

    if(found){
        target.innerHTML = "⚠️ الجملة موجودة مسبقاً";
        target.style.color = "#ef4444";
    } else {
        target.innerHTML = "✅ يمكن إضافتها";
        target.style.color = "#22c55e";
    }
}

/* live check */
document.getElementById("searchCheck").addEventListener("input", function(){
    check(this.value, document.getElementById("liveResult"));
});

document.getElementById("sentenceInput").addEventListener("input", function(){
    check(this.value, document.getElementById("checkResult"));
});
</script>

</body>
</html>