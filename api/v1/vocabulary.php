<?php
use Api\V1\Common\Response;
use Api\V1\Common\AuthMiddleware;

$user = AuthMiddleware::authenticate();
$userId = $user['id'];

$action = $action ?: '';

switch ($action) {
    case 'categories':
        handle_vocabulary_categories($pdo, $userId);
        break;
    case 'words':
        handle_vocabulary_words($pdo, $userId);
        break;
    case 'save':
        handle_save_vocabulary($pdo, $userId);
        break;
    case 'remove':
        handle_remove_vocabulary($pdo, $userId);
        break;
    default:
        Response::error('Action not found', 404);
}

function handle_vocabulary_categories($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                uv.word_id,
                uv.question_id,
                q.category_id
            FROM user_vocabulary uv
            INNER JOIN questions q ON uv.question_id = q.id
            WHERE uv.user_id = ?
            AND uv.question_id IS NOT NULL
        ");
        $stmt->execute([$userId]);
        $questionData = $stmt->fetchAll();

        if (empty($questionData)) {
            Response::success([]);
            return;
        }

        $categoryWords = [];
        foreach ($questionData as $row) {
            $categoryIds = trim($row['category_id'], ',');
            $categoryArray = explode(',', $categoryIds);
            foreach ($categoryArray as $catId) {
                if (!empty($catId)) {
                    if (!isset($categoryWords[$catId])) {
                        $categoryWords[$catId] = [];
                    }
                    if (!in_array($row['word_id'], $categoryWords[$catId])) {
                        $categoryWords[$catId][] = $row['word_id'];
                    }
                }
            }
        }

        if (empty($categoryWords)) {
            Response::success([]);
            return;
        }

        $categoryIds = array_keys($categoryWords);
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT id, title, index_code, category_type, parent_id
            FROM categories
            WHERE id IN ($placeholders)
            ORDER BY index_code
        ");
        $stmt->execute($categoryIds);
        $categories = $stmt->fetchAll();

        foreach ($categories as &$category) {
            $category['word_count'] = count($categoryWords[$category['id']] ?? []);
        }

        Response::success($categories);
    } catch (Exception $e) {
        Response::error('Failed to fetch vocabulary categories: ' . $e->getMessage());
    }
}

function handle_vocabulary_words($pdo, $userId) {
    $categoryId = $_GET['category_id'] ?? '';
    if (empty($categoryId)) {
        Response::error('Category ID is required');
    }

    try {
        if ($categoryId === 'all' || $categoryId === '0') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    vw.id, vw.word, vw.translation, COALESCE(uv.language, vw.language, 'de') as language, uv.question_id, q.text as question_text
                FROM user_vocabulary uv
                INNER JOIN vocabulary_words vw ON uv.word_id = vw.id
                LEFT JOIN questions q ON uv.question_id = q.id
                WHERE uv.user_id = ?
                ORDER BY uv.created_at DESC
            ");
            $stmt->execute([$userId]);
            $words = $stmt->fetchAll();
        } else {
            $pattern = "%," . $categoryId . ",%";
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    vw.id, vw.word, vw.translation, COALESCE(uv.language, vw.language, 'de') as language, uv.question_id, q.text as question_text
                FROM user_vocabulary uv
                INNER JOIN vocabulary_words vw ON uv.word_id = vw.id
                INNER JOIN questions q ON uv.question_id = q.id
                WHERE uv.user_id = ?
                AND q.category_id LIKE ?
                ORDER BY uv.created_at DESC
            ");
            $stmt->execute([$userId, $pattern]);
            $words = $stmt->fetchAll();
        }

        Response::success($words);
    } catch (Exception $e) {
        Response::error('Failed to fetch vocabulary words: ' . $e->getMessage());
    }
}

function handle_save_vocabulary($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $word = trim($input['word'] ?? '');
    $translation = trim($input['translation'] ?? '');
    $questionId = $input['question_id'] ?? null;
    $categoryId = $input['category_id'] ?? null;
    $language = isset($input['language']) ? strtolower(trim($input['language'])) : '';

    if (empty($word) || empty($translation)) {
        Response::error('Word and translation are required');
    }

    try {
        $pdo->beginTransaction();

        if (!in_array($language, ['de', 'en'])) {
            $stmtUserLang = $pdo->prepare("SELECT language FROM user_config WHERE user_id = ?");
            $stmtUserLang->execute([$userId]);
            $userLangRow = $stmtUserLang->fetch();
            $language = ($userLangRow && strtolower($userLangRow['language']) === 'en') ? 'en' : 'de';
        }
        if (preg_match('/[äöüßÄÖÜ]/u', $word)) {
            $language = 'de';
        }

        $stmt = $pdo->prepare("SELECT id, translation, language FROM vocabulary_words WHERE word = ?");
        $stmt->execute([$word]);
        $word_row = $stmt->fetch();

        if (!$word_row) {
            $stmt = $pdo->prepare("INSERT INTO vocabulary_words (word, translation, language) VALUES (?, ?, ?)");
            $stmt->execute([$word, $translation, $language]);
            $word_id = $pdo->lastInsertId();
        } else {
            $word_id = $word_row['id'];
            if ($word_row['translation'] !== $translation) {
                $stmt = $pdo->prepare("UPDATE vocabulary_words SET translation = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$translation, $word_id]);
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
        $stmt->execute([$userId, $word_id]);
        $user_vocab_row = $stmt->fetch();

        if ($user_vocab_row) {
            $stmt = $pdo->prepare("UPDATE user_vocabulary SET question_id = ?, category_id = ?, language = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$questionId, $categoryId, $language, $user_vocab_row['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_vocabulary (word_id, user_id, question_id, category_id, language) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$word_id, $userId, $questionId, $categoryId, $language]);
        }

        $pdo->commit();
        Response::success(['word_id' => $word_id], 'Vocabulary saved');
    } catch (Exception $e) {
        $pdo->rollBack();
        Response::error('Failed to save vocabulary: ' . $e->getMessage());
    }
}

function handle_remove_vocabulary($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        Response::error('Method not allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $wordId = $input['word_id'] ?? '';

    if (empty($wordId)) {
        Response::error('Word ID is required');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM user_vocabulary WHERE user_id = ? AND word_id = ?");
        $stmt->execute([$userId, $wordId]);
        Response::success(null, 'Vocabulary removed');
    } catch (Exception $e) {
        Response::error('Failed to remove vocabulary: ' . $e->getMessage());
    }
}
