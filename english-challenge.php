<?php 
// 1. تضمين ملف الاتصال بقاعدة البيانات وملف الناف بار المشترك
include 'config/db.php';
include 'includes/header.php'; 

// 2. جلب جميع الجمل الجاهزة من بنك التحديات
try {
    $stmt = $pdo->query("SELECT * FROM english_challenges ORDER BY RAND()"); // جلبها بترتيب عشوائي للحماس
    $all_challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب بنك الجمل: " . $e->getMessage());
}
?>

<style>
    .challenge-box { max-width: 800px; margin: 40px auto; text-align: center; padding: 0 15px; box-sizing: border-box; }
    
    /* كارت التحدي السينمائي المظلم المطور */
    .sentence-card {
        background: linear-gradient(135deg, #0f172a, #020617);
        border: 2px solid #334155;
        border-radius: 20px;
        padding: 50px 30px;
        margin-bottom: 25px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        position: relative;
        box-sizing: border-box;
        width: 100%;
        transition: all 0.3s ease; /* تأثير ناعم عند تقلص الحجم على الموبايل */
    }
    
    /* عرض الجملة الإنجليزية بخط كبير ومتجاوب */
    .sentence-display {
        font-family: 'Fira Code', 'Courier New', monospace;
        font-size: clamp(22px, 5vw, 34px);
        color: #e2e8f0;
        line-height: 1.6;
        word-break: break-word;
        margin-bottom: 15px;
        direction: ltr; /* من اليسار لليمين دائماً لأنها إنجليزي */
    }

    /* مظهر الفراغ الملون المشع */
    .blank-space {
        color: var(--accent);
        font-weight: bold;
        text-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
        border-bottom: 3px dashed var(--accent);
        padding: 0 5px;
    }

    /* ترجمة ومساعدة الجملة تظهر بجمالية بالأسفل */
    .hint-translation {
        display: inline-block;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        padding: 8px 25px;
        border-radius: 30px;
        font-size: clamp(14px, 4vw, 17px);
        margin-top: 10px;
    }

    /* 💡 منطقة التحكم العلوية: المصباح والسماعة والحروف المساعدة */
    .hint-container {
        position: absolute;
        top: 15px;
        right: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        direction: rtl; /* لتبدأ العناصر من اليمين لليسار */
    }

    .hint-bulb-btn, .speaker-btn {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: #f59e0b;
        font-size: 18px;
        padding: 0;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
    }
    
    .speaker-btn {
        background: rgba(56, 189, 248, 0.1);
        border-color: rgba(56, 189, 248, 0.3);
        color: #38bdf8;
        opacity: 0.3; /* تكون شبه شفافة قبل الحل كإشارة أنها غير مفعلة */
        cursor: not-allowed;
    }

    .speaker-btn.active {
        opacity: 1;
        cursor: pointer;
        background: rgba(34, 197, 94, 0.15);
        border-color: #22c55e;
        color: #22c55e;
        animation: pulseSpeaker 1.5s infinite;
    }

    @keyframes pulseSpeaker {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .hint-bulb-btn:hover {
        background: #f59e0b;
        color: #000;
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.5);
        transform: scale(1.05);
    }

    /* 💡 ستايل الحروف المساعدة */
    .hint-display-box {
        display: none;
        background: rgba(30, 41, 59, 0.7);
        border: 1px solid #334155;
        color: #38bdf8;
        padding: 6px 14px;
        border-radius: 8px;
        font-family: 'Fira Code', monospace;
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 2px;
        direction: ltr;
    }

    /* حقل الكتابة والتكرار الاحترافي المتجاوب */
    .challenge-input-container { width: 100%; display: flex; justify-content: center; }
    .challenge-input {
        width: 100%;
        max-width: 450px;
        padding: 15px 20px;
        font-size: clamp(18px, 5vw, 24px);
        font-family: 'Fira Code', monospace;
        background-color: #0f172a;
        border: 2px solid #334155;
        border-radius: 15px;
        color: #fff;
        text-align: center;
        outline: none;
        transition: all 0.3s ease;
        direction: ltr;
        box-sizing: border-box;
    }
    .challenge-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
    }
    .challenge-input.input-error {
        border-color: var(--error) !important;
        box-shadow: 0 0 15px rgba(248, 113, 113, 0.3);
    }

    /* شارة عداد النقاط */
    .score-badge {
        font-size: 16px;
        color: var(--success);
        background: rgba(74, 222, 128, 0.1);
        padding: 8px 20px;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 25px;
        border: 1px solid rgba(74, 222, 128, 0.3);
        font-weight: bold;
        transition: transform 0.2s ease;
    }

    .feedback-zone { min-height: 40px; font-size: clamp(16px, 4.5vw, 22px); font-weight: bold; margin-bottom: 20px; padding: 0 10px; }
    .text-success { color: var(--success); text-shadow: 0 0 10px rgba(74, 222, 128, 0.4); }
    .text-error { color: var(--error); }

    .controls-row { margin-top: 25px; width: 100%; }
    .btn-skip { background-color: #475569; color: #fff; padding: 12px 30px; font-size: 16px; font-weight: bold; border: none; border-radius: 10px; cursor: pointer; transition: all 0.2s; width: 100%; max-width: 250px; }
    .btn-skip:hover { background-color: #64748b; }

    /* 📱 التحديث الذكي للشاشات الصغيرة لتوفير مساحة عند ظهور الكيبورد */
    @media (max-width: 768px) {
        .challenge-box { margin: 15px auto; }
        .score-badge { margin-bottom: 15px; padding: 5px 15px; font-size: 14px; }
        
        /* تقليص حجم الكارت لضمان بقائه مرئياً بالكامل فوق لوحة مفاتيح الجوال */
        .sentence-card { 
            padding: 65px 15px 20px 15px; 
            margin-bottom: 15px; 
            border-radius: 12px;
        }
        
        .hint-container { right: 50%; transform: translateX(50%); top: 12px; width: 95%; justify-content: center; gap: 8px; }
        .hint-bulb-btn, .speaker-btn { width: 36px; height: 36px; font-size: 15px; }
        .hint-display-box { padding: 4px 10px; font-size: 13px; }
        
        .sentence-display { margin-bottom: 10px; line-height: 1.4; }
        .hint-translation { padding: 5px 15px; margin-top: 5px; }
        
        .feedback-zone { min-height: 30px; margin-bottom: 10px; }
        .challenge-input { padding: 12px 15px; border-radius: 10px; }
        .controls-row { margin-top: 15px; }
        .btn-skip { max-width: 100%; padding: 10px; }
    }
</style>

<div class="challenge-box">
    <h2>🇬🇧 طور إتقان الإنجليزية: تحدي بنك الجمل الجاهزة</h2>
    <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 14px;">شريكي الخارق، اقرأ الجملة بتمعن، واعرف الكلمة الناقصة المترجمة بالأسفل، ثم اكتبها واضغط <span class="highlight">Enter</span> أو <span class="highlight">Space</span>!</p>

    <?php if (count($all_challenges) > 0): ?>
        
        <div class="score-badge">🏆 النقاط المحققة: <span id="score-count">0</span></div>

        <div class="sentence-card">
            <div style="display: flex; gap: 8px; position: absolute; top: 18px; left: 15px;">
                <div style="width:10px; height:10px; border-radius:50%; background:#ef4444;"></div>
                <div style="width:10px; height:10px; border-radius:50%; background:#f59e0b;"></div>
                <div style="width:10px; height:10px; border-radius:50%; background:#22c55e;"></div>
            </div>

            <div class="hint-container">
                <button type="button" id="bulb-hint-btn" class="hint-bulb-btn" title="اضغط لكشف حرف إضافي! 💡">💡</button>
                <button type="button" id="speak-sentence-btn" class="speaker-btn" title="استمع للجملة كاملة بعد حلها! 🔊" disabled>🔊</button>
                <div id="hint-display" class="hint-display-box"></div>
            </div>
            
            <div class="sentence-display" id="sentence-text">Loading challenge...</div>
            <div class="hint-translation" id="translation-text">الترجمة: جاري التحميل...</div>
        </div>

        <div class="feedback-zone" id="challenge-feedback"></div>

        <div class="challenge-input-container">
            <input type="text" id="challenge-field" class="challenge-input" placeholder="Type the missing word here..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        </div>

        <div class="controls-row">
            <button id="skip-btn" class="btn-skip">تخطي الجملة الحالية ⏭️</button>
        </div>

    <?php else: ?>
        <div class="card" style="text-align: center; padding: 40px; background-color: var(--bg-card); border-radius:15px; border:2px solid #334155;">
            <h3>بنك الجمل فارغ تماماً في السيرفر! 🧐</h3>
            <p style="margin: 15px 0; color: var(--text-muted);">يرجى زرع بعض الجمل وتغذية جدول `english_challenges` في قاعدة البيانات أولاً لكي يستمتع بها المستخدمون.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    const bankChallenges = <?php echo json_encode($all_challenges); ?>;
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const sentenceText = document.getElementById("sentence-text");
    const translationText = document.getElementById("translation-text");
    const challengeField = document.getElementById("challenge-field");
    const challengeFeedback = document.getElementById("challenge-feedback");
    const scoreCount = document.getElementById("score-count");
    const skipBtn = document.getElementById("skip-btn");
    
    const bulbHintBtn = document.getElementById("bulb-hint-btn");
    const speakSentenceBtn = document.getElementById("speak-sentence-btn");
    const hintDisplay = document.getElementById("hint-display");

    if (bankChallenges.length === 0) return;

    let currentIdx = 0;
    let score = 0;
    let currentChallengeObj = {};
    let revealedCharactersCount = 0;
    let isCurrentChallengeSolved = false; 

    const normalKeySound = new Audio("assets/sounds/click.mp3");
    const spaceKeySound = new Audio("assets/sounds/space.mp3");
    normalKeySound.preload = "auto";
    spaceKeySound.preload = "auto";

    function playSound(isSpecial = false) {
        try {
            if (isSpecial) {
                spaceKeySound.currentTime = 0;
                spaceKeySound.play().catch(e => {});
            } else {
                normalKeySound.currentTime = 0;
                normalKeySound.play().catch(e => {});
            }
        } catch(e) {}
    }

    function speakFullSolvedSentence() {
        if (!currentChallengeObj.sentence) return;
        window.speechSynthesis.cancel(); 
        
        let fullCorrectSentence = currentChallengeObj.sentence.replace("___", currentChallengeObj.correct_word.trim());

        let utterance = new SpeechSynthesisUtterance(fullCorrectSentence);
        utterance.lang = 'en-US'; 
        utterance.rate = 0.85; 
        utterance.pitch = 1.0; 
        
        window.speechSynthesis.speak(utterance);
    }

    if (speakSentenceBtn) {
        speakSentenceBtn.addEventListener("click", () => {
            if (isCurrentChallengeSolved) {
                speakFullSolvedSentence();
            }
        });
    }

    function loadNextChallenge() {
        if (currentIdx >= bankChallenges.length) {
            currentIdx = 0;
            bankChallenges.sort(() => Math.random() - 0.5);
        }

        currentChallengeObj = bankChallenges[currentIdx];
        isCurrentChallengeSolved = false; 

        let rawSentence = currentChallengeObj.sentence;
        let finalSentenceHtml = rawSentence.replace("___", `<span class="blank-space">_______</span>`);

        sentenceText.innerHTML = finalSentenceHtml;
        translationText.innerHTML = `🎯 الترجمة المساعدة: <strong>${currentChallengeObj.translation}</strong>`;

        challengeField.value = "";
        challengeField.className = "challenge-input";
        challengeField.disabled = false; 
        challengeFeedback.textContent = "";
        
        revealedCharactersCount = 0;
        hintDisplay.style.display = "none";
        hintDisplay.textContent = "";
        
        speakSentenceBtn.disabled = true;
        speakSentenceBtn.classList.remove("active");
        
        // منع الفوكس التلقائي على الموبايل لتفادي قفز الشاشة بشكل مفاجئ للاعب
        if (window.innerWidth > 768) {
            challengeField.focus();
        }
    }

    loadNextChallenge();

    bulbHintBtn.addEventListener("click", () => {
        if (isCurrentChallengeSolved) return; 
        let correct = currentChallengeObj.correct_word.trim();
        let maxLen = correct.length;

        if (maxLen > 0) {
            if (revealedCharactersCount < maxLen) {
                revealedCharactersCount++;
            }
            let visiblePart = correct.substring(0, revealedCharactersCount);
            let hiddenPart = "•".repeat(maxLen - revealedCharactersCount);
            
            hintDisplay.textContent = `${visiblePart}${hiddenPart}`;
            hintDisplay.style.display = "inline-block";
        }
        challengeField.focus();
    });

    challengeField.addEventListener("input", () => {
        let typed = challengeField.value;
        let correct = currentChallengeObj.correct_word.trim();

        if (correct.toLowerCase().startsWith(typed.toLowerCase())) {
            challengeField.classList.remove("input-error");
        } else {
            challengeField.classList.add("input-error");
        }
    });

    challengeField.addEventListener("keydown", (e) => {
        if (isCurrentChallengeSolved) return; 

        const isSpecial = (e.key === " " || e.key === "Enter");
        playSound(isSpecial);

        if (isSpecial) {
            e.preventDefault();

            let typedValue = challengeField.value.trim();
            let correctValue = currentChallengeObj.correct_word.trim();

            if (typedValue.toLowerCase() === correctValue.toLowerCase() && typedValue !== "") {
                score++;
                scoreCount.textContent = score;
                isCurrentChallengeSolved = true; 

                sentenceText.innerHTML = currentChallengeObj.sentence.replace("___", `<span style="color: #4ade80; font-weight: bold;">${correctValue}</span>`);

                challengeFeedback.className = "feedback-zone text-success";
                challengeFeedback.innerHTML = "✓ رائـع جداً! استمع للجملة الكاملة الآن 🎧🌟";
                challengeField.disabled = true; 

                speakSentenceBtn.disabled = false;
                speakSentenceBtn.classList.add("active");

                scoreCount.parentElement.style.transform = "scale(1.1)";
                setTimeout(() => scoreCount.parentElement.style.transform = "scale(1)", 200);

                speakFullSolvedSentence();

                currentIdx++;
                setTimeout(loadNextChallenge, 2550);
            } else if (typedValue !== "") {
                challengeFeedback.className = "feedback-zone text-error";
                challengeFeedback.innerHTML = "✗ الكلمة غير صحيحة لتكملة الجملة! ركز وحاول ثانية 🛠️";
                challengeField.classList.add("input-error");
            }
        }
    });

    skipBtn.addEventListener("click", () => {
        window.speechSynthesis.cancel(); 
        currentIdx++;
        loadNextChallenge();
    });
});
</script>

<?php 
// 3. تضمين ملف الفوتر لإغلاق الأوسمة
include 'includes/footer.php'; 
?>