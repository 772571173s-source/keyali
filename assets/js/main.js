// انتظر حتى يتم تحميل الصفحة بالكامل يا شريكي
document.addEventListener("DOMContentLoaded", () => {
    const wordInput = document.getElementById("word-input");
    const wordsContainer = document.getElementById("words-container");
    
    // التحقق من وجود الحقول في الصفحة الحالية لتجنب الأخطاء
    if (!wordInput || !wordsContainer) return;

    // جلب جميع الكلمات الموجودة في الصفحة كعناصر داخل مصفوفة
    const wordElements = Array.from(document.querySelectorAll(".word-item"));
    let currentWordIndex = 0;

    // تمييز الكلمة الأولى في البداية لتنبيه المستخدم
    if (wordElements.length > 0) {
        wordElements[currentWordIndex].classList.add("active-word");
        wordInput.focus();
    }

    // الاستماع لكل حرف يكتبه المستخدم داخل حقل الإدخال
    wordInput.addEventListener("input", () => {
        let currentWordElement = wordElements[currentWordIndex];
        if (!currentWordElement) return;

        let targetWord = currentWordElement.dataset.word.trim(); // الكلمة الصحيحة المطلوبة
        let typedValue = wordInput.value; // ما كتبه المستخدم حتى الآن

        // 1. الفحص الفوري أثناء الكتابة (حرف بحرف)
        // إذا كان ما كتبه يطابق بداية الكلمة تماماً
        if (targetWord.startsWith(typedValue)) {
            currentWordElement.classList.remove("word-error");
            currentWordElement.classList.add("word-typing");
        } else {
            // إذا أخطأ في حرف، تظهر علامة خطأ وتتغير الخلفية للأحمر النيون المريح
            currentWordElement.classList.remove("word-typing");
            currentWordElement.classList.add("word-error");
        }

        // 2. النزول التلقائي للسطر والانتقال للكلمة التالية عند الضغط على "المسافة Space" أو اكتمال الكلمة تماماً
        if (typedValue.endsWith(" ") || typedValue === targetWord) {
            // إزالة الفراغ من نهاية النص إذا ضغط مسافة
            let finalTyped = typedValue.trim();

            if (finalTyped === targetWord) {
                // الكلمة صحيحة تماماً -> تلوين بالأخضر النيون الجذاب وظهور علامة الصح
                currentWordElement.className = "word-item word-correct";
            } else {
                // الكلمة خاطئة -> تلوين بالأحمر النيون وظهور علامة الخطأ
                currentWordElement.className = "word-item word-wrong";
            }

            // الانتقال للكلمة التالية
            currentWordIndex++;
            wordInput.value = ""; // تفريغ حقل الإدخال تلقائياً للكلمة الجديدة

            // إذا كانت هناك كلمة تالية، قم بتنشيطها
            if (currentWordIndex < wordElements.length) {
                wordElements[currentWordIndex].classList.add("active-word");
                
                // سحب الشاشة تلقائياً للكلمة التالية ليكون التصميم انسيابي (Scroll)
                wordElements[currentWordIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                // إذا انتهت جميع الكلمات في الصفحة
                wordInput.disabled = true;
                wordInput.placeholder = "تهانينا يا شريكي! أكملت التحدي بنجاح 🏆";
                alert("عمل رائع! لقد أنهيت جميع الكلمات المتاحة بنجاح. 🚀");
            }
        }
    });

    // جعل المستخدم يركز فوراً على الكتابة إذا ضغط في أي مكان داخل الحاوية
    wordsContainer.addEventListener("click", () => {
        wordInput.focus();
    });
});