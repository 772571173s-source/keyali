<?php 
// 1. بدء الجلسة وتضمين ملفات الاتصال والهيدر بأمان
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db.php';
include 'includes/header.php'; 

// تحديد معرف المستخدم الحالي من الجلسة
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
?>

<style>
    .game-box { max-width: 750px; margin: 40px auto; text-align: center; font-family: 'Tajawal', sans-serif; padding: 0 15px; box-sizing: border-box; }
    
    /* صندوق التحكم بمصدر الكلمات */
    .source-selector-box { background: #0f172a; border: 1px solid #1e293b; padding: 15px 25px; border-radius: 12px; margin: 20px auto 20px auto; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
    .selector-title { font-weight: bold; color: #94a3b8; font-size: 15px; }
    .selector-options { display: flex; gap: 10px; flex-wrap: wrap; }
    .source-btn { background: #1e293b; color: #fff; border: 1px solid #475569; padding: 8px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 14px; text-decoration: none; display: inline-block; }
    .source-btn:hover { border-color: #38bdf8; }
    .source-btn.active { background: rgba(56, 189, 248, 0.15); border-color: #38bdf8; color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.1); }
    .source-btn.disabled-btn { background: #090d16; color: #475569; border-color: #1e293b; cursor: not-allowed; }

    /* 🔍 شريط البحث الذكي المدمج في الأعلى */
    .user-search-wrapper { width: 100%; max-width: 450px; margin: 0 auto 20px auto; position: relative; }
    .user-search-input { width: 100%; padding: 12px 40px 12px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #fff; font-size: 14px; font-family: 'Tajawal', sans-serif; box-sizing: border-box; outline: none; transition: 0.3s; text-align: right; }
    .user-search-input:focus { border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.1); }
    .u-search-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 15px; pointer-events: none; }

    /* كارت الكلمة المستهدفة الكبيرة */
    .target-word-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 2px solid #334155;
        border-radius: 20px;
        padding: 45px 20px 35px 20px;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        position: relative;
        overflow: hidden;
        width: 100%;
        box-sizing: border-box;
    }
    
    .display-word {
        font-family: 'Fira Code', monospace;
        font-size: clamp(28px, 8vw, 48px);
        font-weight: bold;
        color: #38bdf8;
        letter-spacing: 2px;
        text-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
        margin: 10px auto 10px auto;
        display: block;
        word-break: break-all;
    }

    /* زر السماعة الطائر */
    .speaker-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(56, 189, 248, 0.1);
        border: 1px solid rgba(56, 189, 248, 0.3);
        color: #38bdf8;
        font-size: 18px;
        padding: 6px 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    .speaker-btn:hover {
        background: #38bdf8;
        color: #0f172a;
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
    }
    
    .display-meaning {
        font-size: clamp(14px, 4vw, 18px);
        color: #94a3b8;
        background: rgba(255,255,255,0.05);
        display: inline-block;
        padding: 5px 20px;
        border-radius: 30px;
        margin-top: 5px;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* حقل الكتابة والتكرار */
    .input-container { margin: 25px 0; position: relative; width: 100%; }
    .practice-input {
        width: 100%;
        max-width: 450px;
        padding: 15px 20px;
        font-size: clamp(18px, 5vw, 24px);
        background-color: #0f172a;
        border: 2px solid #334155;
        border-radius: 15px;
        color: #fff;
        text-align: center;
        outline: none;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    .practice-input:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
    }

    .streak-badge {
        font-size: 16px;
        color: #4ade80;
        background: rgba(74, 222, 128, 0.1);
        padding: 10px 20px;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 20px;
        border: 1px solid rgba(74, 222, 128, 0.3);
        font-weight: bold;
        transition: transform 0.2s ease;
    }

    .practice-input.input-error {
        border-color: #ef4444 !important;
        background-color: rgba(248, 113, 113, 0.05);
        box-shadow: 0 0 15px rgba(248, 113, 113, 0.3);
    }

    .feedback-zone {
        height: auto;
        min-height: 40px;
        font-size: clamp(16px, 4.5vw, 22px);
        font-weight: bold;
        margin-bottom: 20px;
        transition: all 0.2s ease;
        padding: 0 10px;
        box-sizing: border-box;
    }
    .text-success { color: #4ade80; text-shadow: 0 0 10px rgba(74, 222, 128, 0.4); }
    .text-error { color: #ef4444; }

    /* صف أزرار التحكم بالتنقل المتجاوب */
    .controls-row { display: flex; justify-content: center; gap: 15px; margin-top: 20px; width: 100%; flex-wrap: wrap; }
    .action-btn {
        padding: 12px 25px;
        font-size: 16px;
        font-weight: bold;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        flex: 1;
        min-width: 140px;
        max-width: 220px;
    }
    .btn-prev { background-color: #1e293b; color: #94a3b8; border: 1px solid #334155; }
    .btn-prev:hover:not(:disabled) { background-color: #334155; color: #fff; transform: translateY(-2px); }
    .btn-prev:disabled { opacity: 0.4; cursor: not-allowed; }
    
    .btn-next { background-color: #38bdf8; color: #0f172a; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.3); }
    .btn-next:hover:not(:disabled) { background-color: #7dd3fc; transform: translateY(-2px); }
    .btn-next:disabled { opacity: 0.4; cursor: not-allowed; }

    @media (max-width: 768px) {
        .game-box h2 { font-size: clamp(18px, 5.5vw, 24px); }
        .source-selector-box { flex-direction: column !important; text-align: center !important; padding: 15px; gap: 10px; }
        .selector-options { width: 100%; justify-content: center; }
        .source-btn { width: 100%; text-align: center; }
        .target-word-card { padding: 55px 15px 25px 15px; }
        .action-btn { max-width: 100%; }
    }
</style>

<div class="game-box">
    <h2 style="margin-bottom: 10px;">🔁 طور التكرار المرتب والإتقان الصارم</h2>
    <p style="color: #94a3b8; margin-bottom: 20px; font-size: 14px;">اكتب الكلمة بشكل متكرر للحفظ  ،  <span class="highlight"></span> </p>

    <div class="source-selector-box">
        <span class="selector-title">🎯 مصدر الكلمات للتكرار:</span>
        <div class="selector-options">
            <button type="button" class="source-btn active" id="btn-global-src" onclick="changeWordSource('global')">🌐 كلمات الموقع العامة</button>
            <?php if($current_user_id > 0): ?>
                <button type="button" class="source-btn" id="btn-personal-src" onclick="changeWordSource('personal')">🥷 بنك كلماتي الشخصي</button>
            <?php else: ?>
                <a href="login.php" class="source-btn disabled-btn" title="سجل دخولك لتفعيل هذا الخيار">🔒 بنك كلماتي الشخصي (يتطلب دخول)</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-search-wrapper" id="search-block">
        <span class="u-search-icon">🔍</span>
        <input type="text" id="user-word-search" class="user-search-input" placeholder="ابحث عن كلمة معينة للانتقال إليها فوراً... ⚡" autocomplete="off">
    </div>
        
    <div class="streak-badge">🔥 عدد مرات التكرار الناجح: <span id="streak-count">0</span></div>

    <div class="target-word-card">
        <button type="button" id="speak-btn" class="speaker-btn" title="اسمع نطق الكلمة">🔊</button>
        
        <div class="display-word" id="target-word-text">جاري جلب الكلمات...</div>
        <div class="display-meaning" id="target-word-meaning">يرجى الانتظار</div>
    </div>

    <div class="feedback-zone" id="feedback-text"></div>

    <div class="input-container">
        <input type="text" id="practice-field" class="practice-input" placeholder="اكتب الكلمة هنا then .." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" disabled>
    </div>

    <div class="controls-row">
        <button id="prev-word-btn" class="action-btn btn-prev" disabled>⬅️ السابقة</button>
        <button id="next-word-btn" class="action-btn btn-next" disabled>التالية ➡️</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const targetWordText = document.getElementById("target-word-text");
    const targetWordMeaning = document.getElementById("target-word-meaning");
    const practiceField = document.getElementById("practice-field");
    const feedbackText = document.getElementById("feedback-text");
    const streakCount = document.getElementById("streak-count");
    const prevWordBtn = document.getElementById("prev-word-btn");
    const nextWordBtn = document.getElementById("next-word-btn");
    const speakBtn = document.getElementById("speak-btn");
    const userWordSearch = document.getElementById("user-word-search");

    let wordsData = [];
    let currentIndex = 0; 
    let currentSource = 'global'; 
    let currentGameMode = 'words'; 
    let currentWordObj = {};
    let successStreak = 0;

    const normalKeySound = new Audio("assets/sounds/click.mp3");
    const spaceKeySound = new Audio("assets/sounds/space.mp3");
    normalKeySound.volume = 0.5; spaceKeySound.volume = 0.6;

    function playKeySound(isSpecial = false) {
        try {
            if (isSpecial) { spaceKeySound.currentTime = 0; spaceKeySound.play().catch(e => {}); }
            else { normalKeySound.currentTime = 0; normalKeySound.play().catch(e => {}); }
        } catch (e) {}
    }

    function speakWord(text) {
        if (!text || text.includes("...")) return;
        window.speechSynthesis.cancel();
        let utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US'; utterance.rate = 0.9;
        window.speechSynthesis.speak(utterance);
    }

    speakBtn.addEventListener("click", () => {
        let textToSpeak = currentWordObj.word_text || currentWordObj.text;
        if (textToSpeak) speakWord(textToSpeak);
    });

    window.changeWordSource = function(source) {
        if(currentSource === source) return;
        currentSource = source;
        document.getElementById('btn-global-src').classList.toggle('active', source === 'global');
        const personalBtn = document.getElementById('btn-personal-src');
        if(personalBtn) personalBtn.classList.toggle('active', source === 'personal');
        
        document.getElementById('search-block').style.display = (source === 'global') ? 'block' : 'none';
        loadWordsFromAPI();
    }

    function loadWordsFromAPI() {
        targetWordText.textContent = "جاري التحميل...";
        targetWordMeaning.textContent = "يتم تحميل الكلمات الحالية...";
        practiceField.disabled = true;
        prevWordBtn.disabled = true;
        nextWordBtn.disabled = true;

        fetch(`get-game-words.php?source=${currentSource}&mode=${currentGameMode}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.words && data.words.length > 0) {
                    wordsData = data.words;
                    practiceField.disabled = false;
                    
                    // نبدأ دائماً من الكلمة الأولى بشكل نقي دون استدعاء ملفات الحفظ القديمة
                    currentIndex = 0;
                    renderCurrentWord(false); 
                } else {
                    wordsData = [];
                    targetWordText.textContent = "لا توجد كلمات حالياً";
                    targetWordMeaning.textContent = "";
                    practiceField.disabled = true;
                    prevWordBtn.disabled = true;
                    nextWordBtn.disabled = true;
                }
            })
            .catch(() => {
                targetWordText.textContent = "خطأ في الاتصال بالسيرفر";
            });
    }

    function renderCurrentWord(shouldFocus = true) {
        if (!wordsData || wordsData.length === 0) {
            prevWordBtn.disabled = true;
            nextWordBtn.disabled = true;
            return;
        }

        if (currentIndex < 0) currentIndex = 0;
        if (currentIndex >= wordsData.length) currentIndex = wordsData.length - 1;

        currentWordObj = wordsData[currentIndex];
        
        let wordDisplayStr = currentWordObj.word_text || currentWordObj.text || "بدون اسم";
        let wordMeaningStr = currentWordObj.word_meaning || currentWordObj.hint || 'لا يوجد تلميح متوفر';

        targetWordText.textContent = wordDisplayStr;
        targetWordMeaning.textContent = "💡 المعنى: " + wordMeaningStr;
        
        practiceField.value = "";
        practiceField.className = "practice-input";
        feedbackText.textContent = "";

        // تحديث حالة الأزرار بناءً على الترتيب الحالي
        prevWordBtn.disabled = (currentIndex === 0);
        nextWordBtn.disabled = (currentIndex === wordsData.length - 1);

        if (shouldFocus && window.innerWidth > 768 && document.activeElement !== userWordSearch) {
            practiceField.focus();
        }

        setTimeout(() => { speakWord(wordDisplayStr); }, 300);
    }

    userWordSearch.addEventListener('input', () => {
        let query = userWordSearch.value.trim().toLowerCase();
        if(query.length === 0) return;

        let foundIndex = wordsData.findIndex(w => {
            let txt = (w.word_text || w.text || "").trim().toLowerCase();
            return txt.startsWith(query);
        });

        if (foundIndex !== -1) {
            currentIndex = foundIndex;
            renderCurrentWord(false); 
        }
    });

    practiceField.addEventListener("input", () => {
        let typedValue = practiceField.value.trim().toLowerCase();
        let correctWord = (currentWordObj.word_text || currentWordObj.text || "").trim().toLowerCase();
        if (correctWord.startsWith(typedValue)) {
            practiceField.classList.remove("input-error");
        } else {
            practiceField.classList.add("input-error");
        }
    });

    practiceField.addEventListener("keydown", (e) => {
    const isSpecial = (e.key === "Enter"); 
    playKeySound(isSpecial);

        if (isSpecial) {
            e.preventDefault(); 
            let finalTyped = practiceField.value.trim().toLowerCase();
            let correctWord = (currentWordObj.word_text || currentWordObj.text || "").trim().toLowerCase();

            if (finalTyped === correctWord && finalTyped !== "") {
                successStreak++;
                streakCount.textContent = successStreak;
                
                feedbackText.className = "feedback-zone text-success";
                feedbackText.innerHTML = "✓ ممتاز! كتابة صحيحة 💯 (+1 تكرار)";
                
                practiceField.value = "";
                practiceField.className = "practice-input";
                
                streakCount.parentElement.style.transform = "scale(1.1)";
                setTimeout(() => streakCount.parentElement.style.transform = "scale(1)", 200);
            } else if (finalTyped !== "") {
                feedbackText.className = "feedback-zone text-error";
                feedbackText.innerHTML = "✗ خطأ! حاول مجدداً شريكي 🔥";
                practiceField.classList.add("input-error");
            }
        }
    });

    nextWordBtn.addEventListener("click", () => {
        if (currentIndex < wordsData.length - 1) {
            currentIndex++;
            renderCurrentWord(true);
        }
    });

    prevWordBtn.addEventListener("click", () => {
        if (currentIndex > 0) {
            currentIndex--;
            renderCurrentWord(true);
        }
    });

    loadWordsFromAPI();
});
</script>

<?php 
include 'includes/footer.php'; 
?>