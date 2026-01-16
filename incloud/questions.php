<?php
//include/questions.php
require_once __DIR__ . '/../config/config.php';

// تابع کمکی برای دریافت exam_date_type کاربر
function getUserExamDateType($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT exam_date_type FROM user_config WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['exam_date_type'] : null;
}

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

        $stmt = $pdo->prepare("
            SELECT *
            FROM questions
            WHERE category_id LIKE :pattern" . $availableCondition
        );

        $stmt->bindValue(':pattern', $pattern, PDO::PARAM_STR);
        $stmt->execute();

        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $questions;
    } else {
        echo "لطفاً category_id را وارد کنید.";
    }
}

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
                WHERE category_id LIKE :pattern" . $availableCondition
            );

            $stmt2->bindValue(':pattern', $pattern, PDO::PARAM_STR);
            $stmt2->execute();

            $results = $stmt2->fetchAll();
            if ($results) {
                $questionArr = array_merge($questionArr, $results);
            }
        }
    }
    return $questionArr;
}