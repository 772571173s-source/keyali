<?php
session_start();
// التأكد من أن المخرج دائماً JSON حتى في حالة الخطأ
header('Content-Type: application/json');

include 'config/db.php';

$source = isset($_GET['source']) ? $_GET['source'] : 'global';
$game_mode = isset($_GET['mode']) ? $_GET['mode'] : 'words';
$language = isset($_GET['lang']) ? $_GET['lang'] : '';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    if ($game_mode === 'ranked') $source = 'global';

    // --- 🟢 طور القواعد ---
    if ($game_mode === 'grammar') {
        $category = isset($_GET['category']) ? $_GET['category'] : "";
        if (!empty($category) && $category !== 'all') {
            $stmt = $pdo->prepare("SELECT text, hint, category FROM grammar_challenges WHERE category = ? ORDER BY id ASC");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->query("SELECT text, hint, category FROM grammar_challenges ORDER BY id ASC");
        }
    }
    // --- 🎯 الكلمات الشخصية ---
    elseif ($source === 'personal') {
        if (!$user_id) throw new Exception('يجب تسجيل الدخول أولاً!');

        if ($game_mode === 'codes') {
            if (!empty($language)) {
                $stmt = $pdo->prepare("SELECT word_text AS text, word_hint AS hint FROM user_words WHERE user_id = ? AND type = 'code' AND language_name = ? ORDER BY id ASC");
                $stmt->execute([$user_id, $language]);
            } else {
                $stmt = $pdo->prepare("SELECT word_text AS text, word_hint AS hint FROM user_words WHERE user_id = ? AND type = 'code' ORDER BY id ASC");
                $stmt->execute([$user_id]);
            }
        } else {
            $stmt = $pdo->prepare("SELECT word_text AS text, word_hint AS hint FROM user_words WHERE user_id = ? AND type = 'word' ORDER BY id ASC");
            $stmt->execute([$user_id]);
        }
    }
    // --- 🌐 الكلمات العامة ---
    else {
        if ($game_mode === 'codes') {
            if (!empty($language)) {
                // ملاحظة: إذا كان الخطأ هنا، تأكد من وجود جدول languages وعمود language_id
                $stmt = $pdo->prepare("SELECT c.code_text AS text, c.description AS hint 
                                      FROM code_terms c
                                      JOIN languages l ON c.language_id = l.id 
                                      WHERE l.lang_name = ? 
                                      ORDER BY c.id ASC");
                $stmt->execute([$language]);
            } else {
                $stmt = $pdo->query("SELECT code_text AS text, description AS hint FROM code_terms ORDER BY id ASC");
            }
        } else {
            $stmt = $pdo->query("SELECT word_text AS text, word_meaning AS hint FROM words ORDER BY id ASC");
        }
    }

    $words_output = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'words' => $words_output]);
} catch (Exception $e) {
    // إرجاع رسالة خطأ بتنسيق JSON نظيف
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
