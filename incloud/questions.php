<?php
// include/questions.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/subscription-functions.php';

// تابع کمکی برای دریافت exam_date_type کاربر
function getUserExamDateType($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT exam_date_type FROM user_config WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['exam_date_type'] : null;
}

/**
 * دریافت سوالات با اعمال محدودیت پلن
 */
function getQuestions($pdo, $cat2id = null, $user_id = null)
{
    if ($cat2id !== null) {
        $pattern = "%," . $cat2id . ",%";

        // دریافت exam_date_type کاربر
        $examDateType = getUserExamDateType($pdo, $user_id);

        // ساخت شرط فیلتر available
        $availableCondition = "";
        if ($examDateType === 'before') {
            // فقط 0 و 1
            $availableCondition = " AND (available = 0 OR available = 1)";
        } elseif ($examDateType === 'after') {
            // فقط 0 و 2
            $availableCondition = " AND (available = 0 OR available = 2)";
        }

        // دریافت محدودیت سوالات کاربر
        $question_limit = get_user_question_limit($user_id, $pdo);
        $limitClause = "";
        if ($question_limit !== null) {
            $limitClause = " LIMIT " . intval($question_limit);
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM questions
            WHERE category_id LIKE :pattern" . $availableCondition . "
            ORDER BY id ASC" . $limitClause
        );

        $stmt->bindValue(':pattern', $pattern, PDO::PARAM_STR);
        $stmt->execute();

        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $questions;
    } else {
        error_log("لطفاً category_id را وارد کنید.");
        return [];
    }
}

/**
 * دریافت سوالات دسته‌بندی ریشه با اعمال محدودیت پلن
 */
function getRootCategoryQuestions($pdo, $rootCatId = null, $user_id = null)
{
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE parent_id = :rootCatId
    ");
    $stmt->bindValue(':rootCatId', $rootCatId, PDO::PARAM_INT);
    $stmt->execute();

    $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $questionArr = [];

    if (count($categoryIds) > 0) {
        // دریافت exam_date_type کاربر
        $examDateType = getUserExamDateType($pdo, $user_id);

        // ساخت شرط فیلتر available
        $availableCondition = "";
        if ($examDateType === 'before') {
            $availableCondition = " AND (available = 0 OR available = 1)";
        } elseif ($examDateType === 'after') {
            $availableCondition = " AND (available = 0 OR available = 2)";
        }

        foreach ($categoryIds as $catId) {
            $pattern = "%," . $catId . ",%";

            $stmt2 = $pdo->prepare("
                SELECT *
                FROM questions
                WHERE category_id LIKE :pattern" . $availableCondition . "
                ORDER BY id ASC
            ");

            $stmt2->bindValue(':pattern', $pattern, PDO::PARAM_STR);
            $stmt2->execute();

            $results = $stmt2->fetchAll();
            if ($results) {
                $questionArr = array_merge($questionArr, $results);
            }
        }

        // اعمال محدودیت بر روی کل سوالات
        $question_limit = get_user_question_limit($user_id, $pdo);
        if ($question_limit !== null && count($questionArr) > $question_limit) {
            $questionArr = array_slice($questionArr, 0, $question_limit);
        }
    }
    
    return $questionArr;
}

/**
 * دریافت تعداد کل سوالات موجود برای کاربر
 */
function getTotalAvailableQuestions($pdo, $user_id = null)
{
    // دریافت exam_date_type کاربر
    $examDateType = getUserExamDateType($pdo, $user_id);

    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " WHERE (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " WHERE (available = 0 OR available = 2)";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions" . $availableCondition);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'] ?? 0;
}

/**
 * دریافت تعداد سوالات قابل دسترس برای کاربر
 */
function getUserAccessibleQuestions($pdo, $user_id = null)
{
    $total = getTotalAvailableQuestions($pdo, $user_id);
    $limit = get_user_question_limit($user_id, $pdo);
    
    if ($limit === null) {
        return $total; // VIP - همه سوالات
    }
    
    return min($total, $limit); // Free - حداکثر 500
}

/**
 * دریافت سوالات دسته‌های خاص با اعمال محدودیت پلن
 */
function getSpecialQuestions($pdo, $special_type, $user_id = null, $tag_id = null)
{
    // دریافت exam_date_type کاربر
    $examDateType = getUserExamDateType($pdo, $user_id);

    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (q.available = 0 OR q.available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (q.available = 0 OR q.available = 2)";
    }

    $query = "";
    $params = [];

    if ($special_type === 'bookmarks') {
        $query = "SELECT q.* FROM questions q JOIN question_bookmarks b ON q.id = b.question_id WHERE b.user_id = ?" . $availableCondition;
        $params[] = $user_id;
    } elseif ($special_type === '5points') {
        $query = "SELECT q.* FROM questions q WHERE q.points = 5" . $availableCondition;
    } elseif ($special_type === 'video') {
        $query = "SELECT q.* FROM questions q WHERE q.picture LIKE '%.m4v'" . $availableCondition;
    } elseif ($special_type === 'picture') {
        $query = "SELECT q.* FROM questions q WHERE q.picture IS NOT NULL AND q.picture != '' AND q.picture NOT LIKE '%.m4v'" . $availableCondition;
    } elseif ($special_type === 'tag' && $tag_id !== null) {
        $query = "SELECT q.* FROM questions q JOIN question_question_tag qt ON q.id = qt.question_id WHERE qt.question_tag_id = ?" . $availableCondition;
        $params[] = $tag_id;
    } else {
        return [];
    }

    $query .= " ORDER BY q.id ASC";

    // دریافت محدودیت سوالات کاربر
    $question_limit = get_user_question_limit($user_id, $pdo);
    if ($question_limit !== null) {
        $query .= " LIMIT " . intval($question_limit);
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}