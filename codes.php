<?php 
// 1. تضمين ملف الاتصال بقاعدة البيانات وملف الناف بار المشترك
include 'config/db.php';
include 'includes/header.php'; 

// 2. جلب جميع اللغات البرمجية المتوفرة في قاعدة البيانات لتغذية قائمة الاختيار
try {
    $lang_stmt = $pdo->query("SELECT * FROM languages ORDER BY lang_name ASC");
    $languages = $lang_stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب اللغات: " . $e->getMessage());
}
?>

<style>
    .code-game-box { max-width: 850px; margin: 40px auto; text-align: center; font-family: 'Tajawal', sans-serif; padding: 0 15px; box-sizing: border-box; }
    
    /* صندوق التحكم بمصدر الكلمات والأكواد */
    .source-selector-box { background: #0f172a; border: 1px solid #1e293b; padding: 15px 25px; border-radius: 12px; margin: 20px auto 25px auto; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; text-align: right; }
    .selector-title { font-weight: bold; color: #94a3b8; font-size: 15px; }
    .selector-options { display: flex; gap: 10px; flex-wrap: wrap; }
    .source-btn { background: #1e293b; color: #fff; border: 1px solid #475569; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 14px; text-decoration: none; display: inline-block; }
    .source-btn:hover { border-color: #38bdf8; }
    .source-btn.active { background: rgba(56, 189, 248, 0.15); border-color: #38bdf8; color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.1); }
    .source-btn.disabled-btn { background: #090d16; color: #475569; border-color: #1e293b; cursor: not-allowed; }

    /* 🔍 شريط البحث الذكي المدمج للأكواد */
    .user-search-wrapper { width: 100%; max-width: 450px; margin: 0 auto 20px auto; position: relative; }
    .user-search-input { width: 100%; padding: 12px 40px 12px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 14px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; outline: none; transition: 0.3s; text-align: right; }
    .user-search-input:focus { border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.1); }
    .u-search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 15px; pointer-events: none; }

    /* قائمة اختيار اللغة الأنيقة */
    .filter-section { margin-bottom: 30px; display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap; }
    .filter-label { font-size: 18px; color: #38bdf8; font-weight: bold; }
    .lang-selector { padding: 12px 20px; font-size: 16px; background-color: #1e293b; border: 2px solid #334155; border-radius: 10px; color: #fff; cursor: pointer; outline: none; transition: border-color 0.3s; width: 100%; max-width: 300px; }
    .lang-selector:focus { border-color: #38bdf8; }

    /* كارت عرض الكود البرمجي (بشكل شاشة تيرمينال Terminal) */
    .terminal-card {
        background-color: #020617; 
        border: 2px solid #334155;
        border-radius: 16px;
        padding: 35px 20px 25px 20px;
        margin-bottom: 25px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.5);
        text-align: left; 
        direction: ltr;
        position: relative;
        width: 100%;
        box-sizing: border-box;
        overflow-x: auto;
    }
    .terminal-dots { display: flex; gap: 8px; position: absolute; top: 15px; left: 15px; }
    .dot { width: 12px; height: 12px; border-radius: 50%; }
    .dot-r { background-color: #ef4444; }
    .dot-y { background-color: #f59e0b; }
    .dot-g { background-color: #22c55e; }

    .display-code-text {
        font-family: 'Fira Code', 'Courier New', monospace;
        font-size: clamp(16px, 4.5vw, 26px);
        font-weight: 600;
        color: #e2e8f0;
        white-space: pre-wrap;
        word-break: break-word;
        margin-top: 15px;
        text-shadow: 0 0 10px rgba(226, 232, 240, 0.1);
    }

    /* وصف الكود */
    .code-description-tag {
        text-align: right;
        direction: rtl;
        font-size: 15px;
        color: #94a3b8;
        background: rgba(56, 189, 248, 0.1);
        padding: 6px 15px;
        border-radius: 8px;
        display: inline-block;
        margin-bottom: 20px;
        border: 1px solid rgba(56, 189, 248, 0.2);
        max-width: 100%;
        box-sizing: border-box;
    }

    /* حقل إدخال الكود الكبير والمناسب */
    .code-input-field {
        width: 100%;
        max-width: 650px;
        padding: 15px 20px;
        font-family: 'Fira Code', monospace;
        font-size: clamp(15px, 4vw, 20px);
        background-color: #0f172a;
        border: 2px solid #334155;
        border-radius: 12px;
        color: #fff;
        text-align: left;
        direction: ltr;
        outline: none;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    .code-input-field:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
    }
    .code-input-field.input-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 15px rgba(248, 113, 113, 0.25);
    }

    /* شارات وعداد الحماس */
    .streak-badge {
        font-size: 16px;
        color: #4ade80;
        background: rgba(74, 222, 128, 0.1);
        padding: 10px 20px;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 25px;
        border: 1px solid rgba(74, 222, 128, 0.3);
        font-weight: bold;
        transition: transform 0.2s ease;
    }

    .feedback-zone { height: auto; min-height: 40px; font-size: clamp(15px, 4.5vw, 20px); font-weight: bold; margin-bottom: 20px; padding: 0 10px; box-sizing: border-box; }
    .text-success { color: #4ade80; text-shadow: 0 0 10px rgba(74, 222, 128, 0.4); }
    .text-error { color: #ef4444; }

    /* صف أزرار التحكم بالتنقل المتجاوب */
    .controls-row { display: flex; justify-content: center; gap: 15px; margin-top: 25px; width: 100%; flex-wrap: wrap; }
    .action-btn { padding: 12px 25px; font-size: 16px; font-weight: bold; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; flex: 1; min-width: 140px; max-width: 220px; }
    .btn-prev { background-color: #1e293b; color: #94a3b8; border: 1px solid #334155; }
    .btn-prev:hover:not(:disabled) { background-color: #334155; color: #fff; transform: translateY(-2px); }
    .btn-prev:disabled { opacity: 0.4; cursor: not-allowed; }
    
    .btn-next { background-color: #38bdf8; color: #020617; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.3); }
    .btn-next:hover:not(:disabled) { background-color: #7dd3fc; transform: translateY(-2px); }
    .btn-next:disabled { opacity: 0.4; cursor: not-allowed; }

    /* 📱 تحديثات الهواتف الذكية */
    @media (max-width: 768px) {
        .code-game-box h2 { font-size: clamp(18px, 5.5vw, 24px); }
        .source-selector-box { flex-direction: column !important; text-align: center !important; padding: 15px; align-items: center; }
        .selector-options { width: 100%; justify-content: center; }
        .source-btn { width: 100%; text-align: center; }
        .filter-section { flex-direction: column; gap: 8px; }
        .filter-label { font-size: 15px; }
        .terminal-card { padding: 35px 15px 20px 15px; }
        .action-btn { max-width: 100%; }
    }
</style>

<div class="code-game-box">
    <h2 style="margin-bottom: 10px;">💻 قـسم إتقـان الأكـواد والمصطلحات البرمجية</h2>
    <p style="color: #94a3b8; margin-bottom: 20px; font-size: 14px;">اختر لغتك المفضلة، واكتب السطر البرمجي بدقة وبشكل مكترر للحفظ،ملاحضة الاكواد حساسه للأحرف ، واضغط <span class="highlight">Enter</span> للتكرار</p>

    <div class="source-selector-box">
        <span class="selector-title">🎯 مصدر تحدي الأكواد:</span>
        <div class="selector-options">
            <button type="button" class="source-btn active" id="btn-global-src" onclick="changeCodeSource('global')">🌐 أكواد الموقع العامة</button>
            <?php if(isset($_SESSION['user_id'])): ?>
                <button type="button" class="source-btn" id="btn-personal-src" onclick="changeCodeSource('personal')">🥷 بنك أكوادي الشخصي</button>
            <?php else: ?>
                <a href="login.php" class="source-btn disabled-btn" title="سجل دخولك لتفعيل هذا الخيار">🔒 بنك أكوادي الشخصي (يتطلب دخول)</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-search-wrapper" id="search-block">
        <span class="u-search-icon">🔍</span>
        <input type="text" id="user-code-search" class="user-search-input" placeholder="ابحث عن مفهوم أو كود معين للانتقال إليه فوراً... ⚡" autocomplete="off">
    </div>

    <div class="filter-section">
        <label class="filter-label" for="lang-select">اختر اللغة البرمجية للتحدي:</label>
        <select id="lang-select" class="lang-selector">
            <option value="all">-- كل اللغات المتاحة --</option>
            <?php foreach ($languages as $lang): ?>
                <option value="<?php echo htmlspecialchars($lang['lang_name']); ?>"><?php echo htmlspecialchars($lang['lang_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="streak-badge">🔥 تكرار الكود الناجح: <span id="code-streak-count">0</span></div>

    <div style="text-align: right; width: 100%;"><div class="code-description-tag" id="code-desc">جاري جلب الأكواد...</div></div>

    <div class="terminal-card">
        <div class="terminal-dots">
            <div class="dot dot-r"></div>
            <div class="dot dot-y"></div>
            <div class="dot dot-g"></div>
        </div>
        <div class="display-code-text" id="target-code-display">// جاري تحميل لوحة التحكم البرمجية...</div>
    </div>

    <div class="feedback-zone" id="code-feedback"></div>

    <div>
        <input type="text" id="code-practice-field" class="code-input-field" placeholder="ابدأ بكتابة الكود البرمجي بدقة ثم اضغط Enter..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled>
    </div>

    <div class="controls-row">
        <button id="prev-code-btn" class="action-btn btn-prev" disabled>⬅️ السابق</button>
        <button id="next-code-btn" class="action-btn btn-next" disabled>التالي ➡️</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const langSelect = document.getElementById("lang-select");
    const targetCodeDisplay = document.getElementById("target-code-display");
    const codeDesc = document.getElementById("code-desc");
    const codePracticeField = document.getElementById("code-practice-field");
    const codeFeedback = document.getElementById("code-feedback");
    const codeStreakCount = document.getElementById("code-streak-count");
    const prevCodeBtn = document.getElementById("prev-code-btn");
    const nextCodeBtn = document.getElementById("next-code-btn");
    const userCodeSearch = document.getElementById("user-code-search");

    let fetchedCodes = []; 
    let currentIndex = 0; // مؤشر تتبع الكود الحالي بدقة للترتيب التصاعدي
    let currentSource = 'global'; 
    let currentGameMode = 'codes'; 
    let currentCodeObj = {};
    let codeStreak = 0;

    const normalKeySound = new Audio("assets/sounds/click.mp3");
    const spaceKeySound = new Audio("assets/sounds/space.mp3");

    normalKeySound.preload = "auto";
    spaceKeySound.preload = "auto";
    normalKeySound.volume = 0.5;
    spaceKeySound.volume = 0.6;

    function playKeySound(isSpecial = false) {
        try {
            if (isSpecial) {
                spaceKeySound.currentTime = 0;
                spaceKeySound.play().catch(e => {});
            } else {
                normalKeySound.currentTime = 0;
                normalKeySound.play().catch(e => {});
            }
        } catch (e) {}
    }

    window.changeCodeSource = function(source) {
        if(currentSource === source) return;
        currentSource = source;
        
        document.getElementById('btn-global-src').classList.toggle('active', source === 'global');
        const personalBtn = document.getElementById('btn-personal-src');
        if(personalBtn) personalBtn.classList.toggle('active', source === 'personal');
        
        document.getElementById('search-block').style.display = (source === 'global') ? 'block' : 'none';
        
        codeStreak = 0; 
        codeStreakCount.textContent = codeStreak;
        
        loadCodesFromAPI();
    }

    langSelect.addEventListener("change", () => {
        codeStreak = 0; 
        codeStreakCount.textContent = codeStreak;
        loadCodesFromAPI(); 
    });

    function loadCodesFromAPI() {
        targetCodeDisplay.textContent = "// جاري الاتصال بخادم الأكواد...";
        codeDesc.textContent = "يتم سحب البيانات المحددة حالياً...";
        codePracticeField.disabled = true;
        prevCodeBtn.disabled = true;
        nextCodeBtn.disabled = true;

        let selectedLang = langSelect.value;
        let langQuery = (selectedLang === "all") ? "" : selectedLang;

        fetch(`get-game-words.php?source=${currentSource}&mode=${currentGameMode}&lang=${encodeURIComponent(langQuery)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.words && data.words.length > 0) {
                    fetchedCodes = data.words;
                    codePracticeField.disabled = false;
                    currentIndex = 0; // البدء المرتب من أول كود دائماً
                    renderCurrentCode();
                } else {
                    fetchedCodes = [];
                    targetCodeDisplay.textContent = "// لا توجد أكواد مضافة لهذا الفحص حالياً!";
                    codeDesc.textContent = "يرجى إضافة أكواد متوافقة ⚠️";
                    codePracticeField.disabled = true;
                    prevCodeBtn.disabled = true;
                    nextCodeBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('خطأ أثناء جلب الأكواد:', error);
                targetCodeDisplay.textContent = "// خطأ في جلب البيانات من السيرفر.";
            });
    }

    function renderCurrentCode(shouldFocus = true) {
        if (fetchedCodes.length === 0) return;

        // حماية مصفوفة الأكواد من الخروج عن الحدود
        if (currentIndex < 0) currentIndex = 0;
        if (currentIndex >= fetchedCodes.length) currentIndex = fetchedCodes.length - 1;

        currentCodeObj = fetchedCodes[currentIndex];

        targetCodeDisplay.textContent = currentCodeObj.text;
        codeDesc.innerHTML = `💡 الهدف والمفهوم: ${currentCodeObj.hint ? currentCodeObj.hint : 'ممارسة وتكرار برمجى مفتوح'}`;
        
        codePracticeField.value = "";
        codePracticeField.className = "code-input-field";
        codeFeedback.textContent = "";
        
        // تحديث فورى وحصين لحالة أزرار السابق والتالي
        prevWordBtn = prevCodeBtn.disabled = (currentIndex === 0);
        nextCodeBtn.disabled = (currentIndex === fetchedCodes.length - 1);

        if (shouldFocus && window.innerWidth > 768 && document.activeElement !== userCodeSearch) {
            codePracticeField.focus();
        }
    }

    // 🔍 معالج البحث الذكي الفوري المبني على الأكواد والتلميحات
    userCodeSearch.addEventListener('input', () => {
        let query = userCodeSearch.value.trim().toLowerCase();
        if(query.length === 0) return;

        let foundIndex = fetchedCodes.findIndex(c => {
            let codeText = (c.text || "").trim().toLowerCase();
            let codeHint = (c.hint || "").trim().toLowerCase();
            return codeText.includes(query) || codeHint.includes(query);
        });

        if (foundIndex !== -1) {
            currentIndex = foundIndex;
            renderCurrentCode(false); 
        }
    });

    codePracticeField.addEventListener("input", () => {
        let typed = codePracticeField.value;
        let originalCode = currentCodeObj.text;

        if (originalCode.startsWith(typed)) {
            codePracticeField.classList.remove("input-error");
        } else {
            codePracticeField.classList.add("input-error");
        }
    });

    codePracticeField.addEventListener("keydown", (e) => {
        const isSpecial = (e.key === " " || e.key === "Enter");
        playKeySound(isSpecial);

        if (e.key === "Enter") {
            e.preventDefault();
            
            let typedTrimmed = codePracticeField.value.trim();
            let originalTrimmed = currentCodeObj.text.trim();

            if (typedTrimmed === originalTrimmed && typedTrimmed !== "") {
                codeStreak++;
                codeStreakCount.textContent = codeStreak;

                codeFeedback.className = "feedback-zone text-success";
                codeFeedback.innerHTML = "✓ كود برمجي سليم 100%! استمر بالتكرار لتثبيته في عقلك 💻🔥";
                
                codePracticeField.value = "";
                codePracticeField.className = "code-input-field";
                
                codeStreakCount.parentElement.style.transform = "scale(1.1)";
                setTimeout(() => codeStreakCount.parentElement.style.transform = "scale(1)", 200);
            } else if (typedTrimmed !== "") {
                codeFeedback.className = "feedback-zone text-error";
                codeFeedback.innerHTML = "✗ هناك خطأ في كتابة الكود أو الرموز! ركز يا شريكي ورتبها 🛠️";
                codePracticeField.classList.add("input-error");
            }
        }
    });

    // أحداث أزرار التنقل المرتب
    nextCodeBtn.addEventListener("click", () => {
        if (currentIndex < fetchedCodes.length - 1) {
            currentIndex++;
            renderCurrentCode(true);
        }
    });

    prevCodeBtn.addEventListener("click", () => {
        if (currentIndex > 0) {
            currentIndex--;
            renderCurrentCode(true);
        }
    });

    loadCodesFromAPI();
});
</script>

<?php 
// تضمين ملف الفوتر لإغلاق الأوسمة
include 'includes/footer.php'; 
?>