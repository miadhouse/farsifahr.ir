<?php
//categories.php
require_once __DIR__ . '/../config/config.php';

// تابع کمکی برای دریافت exam_date_type کاربر
function getUserExamDateType($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT exam_date_type FROM user_config WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['exam_date_type'] : null;
}

// Function to get categories by type and parent
function getCategories($pdo, $category_type, $parent_id = null, $user_id = null)
{
    $sql = "SELECT * FROM categories 
        WHERE category_type = ? AND parent_id ";
    $params = [$category_type];

    if ($parent_id === null) {
        $sql .= "IS NULL";
    } else {
        $sql .= "= ?";
        $params[] = $parent_id;
    }

    $sql .= " ORDER BY 
    CAST(SUBSTRING_INDEX(index_code, '.', 1) AS UNSIGNED),
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(index_code, '.', 2), '.', -1) AS UNSIGNED),
    CAST(SUBSTRING_INDEX(index_code, '.', -1) AS UNSIGNED)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // محاسبه تعداد واقعی سوالات برای هر دسته‌بندی
    foreach ($categories as &$category) {
        $category['question_count'] = getActualQuestionCount($pdo, $category['id'], $user_id);
    }

    return $categories;
}

// Function to get subcategories by parent ID
function getSubcategories($pdo, $parent_id, $user_id = null)
{
    $sql = "SELECT * FROM categories 
            WHERE parent_id = ? 
            ORDER BY 
                CAST(SUBSTRING_INDEX(index_code, '.', 1) AS UNSIGNED),
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(index_code, '.', 2), '.', -1) AS UNSIGNED),
                CAST(SUBSTRING_INDEX(index_code, '.', -1) AS UNSIGNED)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$parent_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // محاسبه تعداد واقعی سوالات برای هر زیردسته
    foreach ($subcategories as &$subcategory) {
        $subcategory['question_count'] = getActualQuestionCount($pdo, $subcategory['id'], $user_id);
    }

    return $subcategories;
}

// تابع جدید برای محاسبه تعداد واقعی سوالات یک دسته‌بندی
function getActualQuestionCount($pdo, $category_id, $user_id = null)
{
    // چک می‌کنیم که آیا این دسته زیردسته دارد یا نه
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $hasSubcategories = $stmt->fetchColumn() > 0;

    if ($hasSubcategories) {
        // اگر زیردسته دارد، سوالات همه زیردسته‌ها را می‌شماریم
        return countQuestionsForRootCategory($pdo, $category_id, $user_id);
    } else {
        // اگر زیردسته ندارد، مستقیماً سوالات خودش را می‌شماریم
        return countQuestionsForCategory($pdo, $category_id, $user_id);
    }
}

// شمارش سوالات برای یک دسته بدون زیردسته
function countQuestionsForCategory($pdo, $category_id, $user_id = null)
{
    $pattern = "%," . $category_id . ",%";

    // دریافت exam_date_type کاربر
    $examDateType = getUserExamDateType($pdo, $user_id);

    // ساخت شرط فیلتر available
    $availableCondition = "";
    if ($examDateType === 'before') {
        $availableCondition = " AND (available = 0 OR available = 1)";
    } elseif ($examDateType === 'after') {
        $availableCondition = " AND (available = 0 OR available = 2)";
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM questions 
        WHERE category_id LIKE :pattern" . $availableCondition
    );

    $stmt->bindValue(':pattern', $pattern, PDO::PARAM_STR);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

// شمارش سوالات برای یک دسته که زیردسته دارد
function countQuestionsForRootCategory($pdo, $root_category_id, $user_id = null)
{
    // دریافت همه زیردسته‌ها
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE parent_id = :rootCatId
    ");
    $stmt->bindValue(':rootCatId', $root_category_id, PDO::PARAM_INT);
    $stmt->execute();

    $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $totalCount = 0;

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
                SELECT COUNT(*) 
                FROM questions 
                WHERE category_id LIKE :pattern" . $availableCondition
            );

            $stmt2->bindValue(':pattern', $pattern, PDO::PARAM_STR);
            $stmt2->execute();

            $totalCount += (int) $stmt2->fetchColumn();
        }
    }

    return $totalCount;
}
