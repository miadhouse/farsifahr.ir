<?php
// incloud/priority_game_handler.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری شوید.']);
    exit;
}

if (!is_super_admin() && !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز. فقط مدیر سیستم مجاز به تغییر سناریوهاست.']);
    exit;
}

/**
 * ایجاد خودکار جدول priority_scenarios در صورت عدم وجود
 */
function ensurePriorityScenariosTable(PDO $pdo): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS `priority_scenarios` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `question_text` TEXT NOT NULL,
            `question_de` TEXT DEFAULT NULL,
            `explanation` TEXT DEFAULT NULL,
            `intersection_type` VARCHAR(50) NOT NULL DEFAULT 'cross4',
            `scenario_data` LONGTEXT NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
}

try {
    ensurePriorityScenariosTable($pdo);

    $action = $_POST['action'] ?? $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, title, question_text, question_de, explanation, intersection_type, scenario_data, is_active, created_at, updated_at FROM priority_scenarios ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['scenario_data'] = json_decode($row['scenario_data'], true) ?: [];
        }
        unset($row);

        echo json_encode([
            'success' => true,
            'scenarios' => $rows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // بررسی CSRF برای عملیات نوشتن
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است. صفحه را رفرش کنید.']);
        exit;
    }

    if ($action === 'save') {
        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $question_text = trim($_POST['question_text'] ?? '');
        $question_de = trim($_POST['question_de'] ?? '');
        $explanation = trim($_POST['explanation'] ?? '');
        $intersection_type = trim($_POST['intersection_type'] ?? 'cross4');
        $scenario_json = $_POST['scenario_data'] ?? '{}';

        if ($title === '') {
            $title = 'سناریو حق تقدم';
        }
        if ($question_text === '') {
            $question_text = 'به چه ترتیبی کاربران جاده حق تقدم عبور دارند؟ به ترتیب روی آن‌ها کلیک کنید.';
        }

        // اعتبارسنجی JSON
        $decoded = json_decode($scenario_json, true);
        if (!is_array($decoded)) {
            echo json_encode(['success' => false, 'message' => 'ساختار داده سناریو نامعتبر است.']);
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE priority_scenarios
                SET title = :title,
                    question_text = :question_text,
                    question_de = :question_de,
                    explanation = :explanation,
                    intersection_type = :intersection_type,
                    scenario_data = :scenario_data
                WHERE id = :id
            ");
            $stmt->execute([
                ':title' => $title,
                ':question_text' => $question_text,
                ':question_de' => $question_de,
                ':explanation' => $explanation,
                ':intersection_type' => $intersection_type,
                ':scenario_data' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ':id' => $id
            ]);
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'سناریو اولویت‌ها با موفقیت ویرایش و ذخیره شد.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO priority_scenarios (title, question_text, question_de, explanation, intersection_type, scenario_data, created_by)
                VALUES (:title, :question_text, :question_de, :explanation, :intersection_type, :scenario_data, :created_by)
            ");
            $stmt->execute([
                ':title' => $title,
                ':question_text' => $question_text,
                ':question_de' => $question_de,
                ':explanation' => $explanation,
                ':intersection_type' => $intersection_type,
                ':scenario_data' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            $newId = (int) $pdo->lastInsertId();
            echo json_encode([
                'success' => true,
                'id' => $newId,
                'message' => 'سناریو جدید اولویت‌ها با موفقیت ساخته و ذخیره شد.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'شناسه سناریو نامعتبر است.']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM priority_scenarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode([
            'success' => true,
            'message' => 'سناریو با موفقیت حذف شد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است.']);
} catch (Throwable $e) {
    error_log('priority_game_handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطای سرور: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
