<?php
// includes/user-config-handler.php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/functions.php');

class UserConfigHandler
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * بررسی اینکه آیا کاربر تنظیمات اولیه را انجام داده است
     */
    public function isConfigured($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT is_configured 
            FROM user_config 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();

        return $result && $result['is_configured'] == 1;
    }

    /**
     * دریافت تنظیمات کاربر
     */
    public function getUserConfig($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM user_config 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    /**
     * ذخیره تنظیمات کاربر
     */
    public function saveConfig($user_id, $exam_date_type, $language)
    {
        try {
            // بررسی اعتبار ورودی‌ها
            if (!in_array($exam_date_type, ['before', 'after'])) {
                throw new Exception('نوع تاریخ امتحان نامعتبر است');
            }

            if (!in_array($language, ['DE', 'EN'])) {
                throw new Exception('زبان انتخابی نامعتبر است');
            }

            // بررسی وجود رکورد
            $exists = $this->getUserConfig($user_id);

            if ($exists) {
                // به‌روزرسانی
                $stmt = $this->pdo->prepare("
                    UPDATE user_config 
                    SET exam_date_type = ?, 
                        language = ?, 
                        is_configured = TRUE,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->execute([$exam_date_type, $language, $user_id]);
            } else {
                // درج جدید
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_config 
                    (user_id, exam_date_type, language, is_configured, reference_date) 
                    VALUES (?, ?, ?, TRUE, '2025-04-01')
                ");
                $stmt->execute([$user_id, $exam_date_type, $language]);
            }

            // ذخیره در SESSION
            $this->loadConfigToSession($user_id);

            return true;
        } catch (Exception $e) {
            error_log("Error saving user config: " . $e->getMessage());
            return false;
        }
    }

    /**
     * بارگذاری تنظیمات در SESSION
     */
    public function loadConfigToSession($user_id)
    {
        $config = $this->getUserConfig($user_id);

        if ($config) {
            $_SESSION['user_config'] = [
                'exam_date_type' => $config['exam_date_type'],
                'language' => $config['language'],
                'reference_date' => $config['reference_date'],
                'is_configured' => $config['is_configured']
            ];
        }
    }

    /**
     * دریافت تنظیمات از SESSION
     */
    public static function getConfigFromSession()
    {
        return isset($_SESSION['user_config']) ? $_SESSION['user_config'] : null;
    }

    /**
     * دریافت تاریخ مرجع
     */
    public function getReferenceDate()
    {
        $stmt = $this->pdo->prepare("
            SELECT reference_date 
            FROM user_config 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? $result['reference_date'] : '2025-04-01';
    }
}

/**
 * Middleware برای چک کردن تنظیمات کاربر
 */
function check_user_configuration()
{
    if (!is_logged_in()) {
        return;
    }

    global $pdo;
    $configHandler = new UserConfigHandler($pdo);

    // اگر تنظیمات در SESSION نیست، از دیتابیس بارگذاری کن
    if (!isset($_SESSION['user_config'])) {
        $configHandler->loadConfigToSession($_SESSION['user_id']);
    }

    // اگر تنظیمات نشده، نیاز به نمایش مودال داریم
    if (!$configHandler->isConfigured($_SESSION['user_id'])) {
        $_SESSION['show_config_modal'] = true;
    }
}