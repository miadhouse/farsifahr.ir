<?php
// admin/priority-game.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../incloud/functions.php';
require_once __DIR__ . '/../incloud/user-config-handler.php';

if (!is_logged_in()) {
    header('Location: /register.php');
    exit;
}

check_user_configuration();

$user_id = $_SESSION['user_id'];
$isAdminUser = is_super_admin() || is_admin();

/**
 * ایجاد جدول priority_scenarios و درج مراحل پیش‌فرض در صورت خالی بودن جدول
 */
function ensureAndFetchPriorityScenarios(PDO $pdo): array
{
    $pdo->exec("
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
    ");

    $count = (int) $pdo->query("SELECT COUNT(*) FROM priority_scenarios WHERE is_active = 1")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            [
                'title' => 'سوال ۱: چهارراه با تابلو رعایت حق تقدم و عبور همزمان',
                'question_text' => 'به چه ترتیبی وسایل نقلیه حق تقدم عبور دارند؟ (شما در ضلع پایین با تابلوی رعایت حق تقدم مستقیم می‌روید، ماشین قرمز در سمت راست به راست می‌پیچد و کامیون آبی در سمت چپ مستقیم می‌رود)',
                'question_de' => 'In welcher Reihenfolge dürfen die Fahrzeuge diese Kreuzung überqueren?',
                'explanation' => 'کامیون آبی و ماشین قرمز می‌توانند همزمان عبور کنند (اولویت ۱) و من باید به هر دوی آن‌ها حق تقدم بدهم (اولویت ۲)؛ چون تابلوی من (مثلث قرمز برعکس - رعایت حق تقدم) ایجاب می‌کند به سمت چپ و راست حق تقدم داده شود. دو وسیله دیگر چون تداخل مسیر ندارند می‌توانند همزمان عبور کنند.',
                'intersection_type' => 'cross4',
                'scenario_data' => [
                    'intersectionType' => 'cross4',
                    'hasSidewalks' => false,
                    'hasRoadMarkings' => false,
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_red', 'direction' => 'right', 'priority' => 1], 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's205_yield', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_green', 'direction' => 'straight', 'priority' => 2], 'pedestrian' => null],
                        'left'   => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => ['type' => 'truck', 'direction' => 'straight', 'priority' => 1], 'pedestrian' => null]
                    ]
                ]
            ],
            [
                'title' => 'سوال ۲: چهارراه با تابلو حق تقدم یکباره (۳۰۱) و گردش به چپ',
                'question_text' => 'به چه ترتیبی وسایل نقلیه حق تقدم عبور دارند؟ (شما در ضلع پایین با تابلوی ۳۰۱ «حق تقدم یکباره» قصد گردش به چپ دارید، در ضلع بالا موتورسیکلت مستقیم می‌رود و در سمت راست اتوبوس زرد مستقیم می‌رود)',
                'question_de' => 'In welcher Reihenfolge dürfen die Fahrzeuge bei Zeichen 301 (einmalige Vorfahrt) fahren?',
                'explanation' => 'ترتیب صحیح: ۱. موتورسیکلت، ۲. من (خودروی ضلع پایین)، ۳. اتوبوس زرد. دلیل: تابلوی شماره ۳۰۱ به معنای «حق تقدم یکباره» در تقاطع بعدی است. هر زمان شما این تابلو را دارید، مسیر روبروی شما (موتورسیکلت) نیز همین شرایط را دارد؛ بنابراین هر دوی شما نسبت به اتوبوس زرد در سمت راست حق تقدم دارید. از آنجا که شرایط شما و موتورسیکلت روبرو یکسان است، طبق قانون بین دو وسیله با شرایط یکسان، وسیله‌ای که مستقیم می‌رود بر وسیله‌ای که قصد گردش به چپ دارد مقدم است؛ پس ابتدا موتورسیکلت (۱)، سپس شما (۲) و در پایان اتوبوس زرد (۳) عبور می‌کنند.',
                'intersection_type' => 'cross4',
                'scenario_data' => [
                    'intersectionType' => 'cross4',
                    'hasSidewalks' => false,
                    'hasRoadMarkings' => false,
                    'branches' => [
                        'top'    => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => ['type' => 'moto', 'direction' => 'straight', 'priority' => 1], 'pedestrian' => null],
                        'right'  => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => ['type' => 'bus', 'direction' => 'straight', 'priority' => 3], 'pedestrian' => null],
                        'bottom' => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 's301_priority_next', 'secondarySign' => 'none', 'vehicle' => ['type' => 'car_green', 'direction' => 'left', 'priority' => 2], 'pedestrian' => null],
                        'left'   => ['hasCrosswalk' => false, 'trafficLight' => 'none', 'primarySign' => 'none', 'secondarySign' => 'none', 'vehicle' => null, 'pedestrian' => null]
                    ]
                ]
            ]
        ];

        $stmtIns = $pdo->prepare("
            INSERT INTO priority_scenarios (title, question_text, question_de, explanation, intersection_type, scenario_data, is_active)
            VALUES (:title, :question_text, :question_de, :explanation, :intersection_type, :scenario_data, 1)
        ");
        foreach ($defaults as $d) {
            $stmtIns->execute([
                ':title' => $d['title'],
                ':question_text' => $d['question_text'],
                ':question_de' => $d['question_de'],
                ':explanation' => $d['explanation'],
                ':intersection_type' => $d['intersection_type'],
                ':scenario_data' => json_encode($d['scenario_data'], JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    $stmt = $pdo->query("SELECT id, title, question_text, question_de, explanation, intersection_type, scenario_data FROM priority_scenarios WHERE is_active = 1 ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['scenario_data'] = json_decode($r['scenario_data'], true) ?: [];
    }
    unset($r);
    return $rows;
}

$scenarios = ensureAndFetchPriorityScenarios($pdo);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>بازی حق تقدم‌ها | FarsiFahr</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="assets/css/demo.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('farsifahr_theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>
    <style>
        /* =========================================================
           2D ARCADE GAME THEME FOR RIGHT-OF-WAY (PRIORITY) GAME
           ========================================================= */
        :root {
            --sky-top: #38bdf8;
            --sky-bottom: #bae6fd;
            --ink-outline: #1e1b4b;
            --board-bg: #fffbeb;
            --board-inner: #ffffff;
            --board-shadow: #d97706;
            --text-main: #0f172a;
            --text-sub: #475569;
        }

        html[data-bs-theme="dark"] {
            --sky-top: #0f172a;
            --sky-bottom: #1e1b4b;
            --ink-outline: #020617;
            --board-bg: #1e293b;
            --board-inner: #0f172a;
            --board-shadow: #020617;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        body {
            background: linear-gradient(180deg, var(--sky-top) 0%, var(--sky-bottom) 100%);
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Vazir', 'Tahoma', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding-bottom: 50px;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(rgba(255, 255, 255, 0.28) 2px, transparent 2px),
                linear-gradient(to bottom, transparent 88%, rgba(34, 197, 94, 0.18) 88%, rgba(22, 163, 74, 0.25) 100%);
            background-size: 32px 32px, 100% 100%;
            pointer-events: none;
            z-index: 0;
        }

        .game-world-clouds {
            position: fixed;
            top: 55px;
            left: 0;
            right: 0;
            height: 160px;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            opacity: 0.55;
        }

        html[data-bs-theme="dark"] .game-world-clouds {
            opacity: 0.15;
        }

        .cloud-svg {
            position: absolute;
            fill: #ffffff;
            filter: drop-shadow(0 4px 0 rgba(30, 27, 75, 0.12));
            animation: floatCloud 28s linear infinite;
        }

        .cloud-1 { top: 10px; right: 8%; width: 120px; animation-duration: 34s; }
        .cloud-2 { top: 60px; left: 12%; width: 95px; animation-duration: 26s; animation-direction: reverse; }
        .cloud-3 { top: 25px; left: 45%; width: 140px; animation-duration: 40s; }

        @keyframes floatCloud {
            0% { transform: translateX(0); }
            50% { transform: translateX(35px); }
            100% { transform: translateX(0); }
        }

        /* 2D Top Arcade HUD */
        .arcade-hud {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: linear-gradient(180deg, #0f766e 0%, #115e59 100%);
            border-bottom: 4px solid #042f2e;
            box-shadow: 0 6px 0 rgba(15, 23, 42, 0.28);
            padding: 10px 14px;
            color: #ffffff;
        }

        .game-container {
            position: relative;
            z-index: 2;
            max-width: 780px;
        }

        .btn-2d {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 800;
            border: 3px solid var(--ink-outline);
            border-radius: 12px;
            padding: 6px 14px;
            font-size: 0.82rem;
            text-decoration: none;
            cursor: pointer;
            user-select: none;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .btn-2d:active {
            transform: translateY(3px) !important;
            box-shadow: 0 1px 0 var(--ink-outline) !important;
        }

        .btn-2d-yellow {
            background: linear-gradient(180deg, #fde047 0%, #facc15 100%);
            color: #1e1b4b;
            box-shadow: 0 4px 0 #a16207, inset 0 2px 0 rgba(255, 255, 255, 0.65);
        }

        .btn-2d-yellow:hover {
            color: #1e1b4b;
            filter: brightness(1.04);
        }

        .btn-2d-cyan {
            background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%);
            color: #ffffff;
            box-shadow: 0 4px 0 #075985, inset 0 2px 0 rgba(255, 255, 255, 0.45);
        }

        .btn-2d-cyan:hover {
            color: #ffffff;
            filter: brightness(1.06);
        }

        .hud-stat-pill {
            background: #0f172a;
            border: 2.5px solid #475569;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6), 0 3px 0 #020617;
            border-radius: 12px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 800;
            color: #f8fafc;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hud-progress-shell {
            height: 14px;
            background: #042f2e;
            border: 2.5px solid #2dd4bf;
            border-radius: 999px;
            padding: 2px;
            margin-top: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.7);
        }

        .hud-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #22c55e 0%, #4ade80 50%, #facc15 100%);
            border-radius: 999px;
            transition: width 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* 2D Quest Board */
        .quest-board {
            background: var(--board-bg);
            border: 4px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 7px 0 var(--board-shadow), 0 12px 20px rgba(15, 23, 42, 0.15);
            padding: 14px;
            margin-bottom: 14px;
            position: relative;
        }

        .quest-board-inner {
            background: var(--board-inner);
            border: 3px solid var(--ink-outline);
            border-radius: 16px;
            padding: 14px;
        }

        /* 3D Intersection Stage Frame */
        .intersection-frame {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            aspect-ratio: 6 / 5;
            min-height: 360px;
            border: 4.5px solid var(--ink-outline);
            border-radius: 22px;
            box-shadow: 0 8px 0 var(--ink-outline), 0 16px 32px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            background: linear-gradient(180deg, #38bdf8 0%, #bae6fd 55%, #4ade80 100%);
            user-select: none;
            position: relative;
        }

        @media (max-width: 576px) {
            .intersection-frame {
                aspect-ratio: 1 / 1;
                min-height: 330px;
            }
        }

        /* Clicked Sequence Bar */
        .sequence-bar {
            background: var(--board-inner);
            border: 3.5px solid var(--ink-outline);
            border-radius: 18px;
            box-shadow: 0 5px 0 var(--ink-outline);
            padding: 12px 14px;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .step-order-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border: 2.5px solid #1e1b4b;
            box-shadow: 0 3px 0 #1e1b4b;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.82rem;
            font-weight: 800;
        }

        /* Confirm Solve Button */
        .btn-solve-game {
            background: linear-gradient(180deg, #facc15 0%, #eab308 100%);
            color: #1e1b4b;
            border: 4px solid #1e1b4b;
            border-radius: 18px;
            padding: 12px 36px;
            font-size: 1.05rem;
            font-weight: 900;
            box-shadow: 0 7px 0 #854d0e, inset 0 3px 0 rgba(255, 255, 255, 0.7);
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-solve-game:active {
            transform: translateY(5px);
            box-shadow: 0 2px 0 #854d0e;
        }

        /* Evaluation Panel */
        .feedback-panel {
            display: none;
            margin-top: 16px;
            animation: popInPanel 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes popInPanel {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .overall-result-banner {
            border-radius: 18px;
            border: 4px solid var(--ink-outline);
            padding: 14px 18px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 800;
        }

        .overall-result-banner.result-win {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: #052e16;
            box-shadow: 0 6px 0 #14532d;
        }

        .overall-result-banner.result-lose {
            background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
            color: #450a0a;
            box-shadow: 0 6px 0 #7f1d1d;
        }

        .actor-eval-row {
            background: var(--board-inner);
            border: 3px solid var(--ink-outline);
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            box-shadow: 0 4px 0 rgba(15, 23, 42, 0.15);
        }

        .actor-eval-row.eval-ok {
            border-right: 8px solid #16a34a;
        }

        .actor-eval-row.eval-err {
            border-right: 8px solid #dc2626;
        }

        .btn-repeat-q {
            background: linear-gradient(180deg, #fde047 0%, #f59e0b 100%);
            color: #1e1b4b;
            border: 3.5px solid #1e1b4b;
            box-shadow: 0 5px 0 #92400e;
            border-radius: 14px;
            padding: 10px 24px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-next-q {
            background: linear-gradient(180deg, #4ade80 0%, #16a34a 100%);
            color: #052e16;
            border: 3.5px solid #1e1b4b;
            box-shadow: 0 5px 0 #14532d;
            border-radius: 14px;
            padding: 10px 28px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    <!-- 2D Clouds Background -->
    <div class="game-world-clouds" aria-hidden="true">
        <svg class="cloud-svg cloud-1" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
        <svg class="cloud-svg cloud-2" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
        <svg class="cloud-svg cloud-3" viewBox="0 0 120 60"><path d="M25,50 h70 a18,18 0 0,0 0,-36 a24,24 0 0,0 -44,-8 a20,20 0 0,0 -26,44 z"/></svg>
    </div>

    <!-- Top 2D Arcade HUD -->
    <header class="arcade-hud">
        <div class="container game-container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="index.php" class="btn-2d btn-2d-yellow">
                        <i class="bx bx-home-alt"></i>
                        <span>داشبورد</span>
                    </a>
                    <a href="mistakes-game.php" class="btn-2d btn-2d-cyan">
                        <i class="fas fa-gamepad"></i>
                        <span>بازی سوالات</span>
                    </a>
                    <?php if ($isAdminUser): ?>
                    <a href="priority-game-builder.php" class="btn-2d btn-2d-cyan" style="background: linear-gradient(180deg, #a855f7 0%, #7e22ce 100%); box-shadow: 0 4px 0 #4c1d95;">
                        <i class="fas fa-drafting-compass"></i>
                        <span>طراحی سوال اولویت‌ها (ادمین)</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($scenarios)): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="hud-stat-pill">
                        <i class="fas fa-star text-warning"></i>
                        <span>امتیاز: <strong id="hudScoreVal">0</strong></span>
                    </div>
                    <div class="hud-stat-pill">
                        <i class="fas fa-traffic-light text-info"></i>
                        <span>مرحله <strong id="currentStageLabel">1</strong> / <strong><?= count($scenarios) ?></strong></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($scenarios)): ?>
            <div class="hud-progress-shell">
                <div class="hud-progress-bar" id="stageProgressBar" style="width: <?= round(100 / count($scenarios), 1) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container game-container mt-3">
        <?php if (empty($scenarios)): ?>
            <div class="quest-board text-center py-5 mt-3">
                <div class="quest-board-inner py-4">
                    <h4 class="fw-black mb-2">هنوز مرحله‌ای ثبت نشده است</h4>
                    <p class="text-muted mb-3">به زودی مراحل جدید بازی حق تقدم‌ها اضافه خواهد شد.</p>
                    <a href="index.php" class="btn-2d btn-2d-yellow px-4 py-2">بازگشت به داشبورد</a>
                </div>
            </div>
        <?php else: ?>

            <div id="activePriorityGameArea">
                <!-- 1. Question Header Board -->
                <div class="quest-board">
                    <div class="quest-board-inner">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                            <span class="badge bg-primary fs-6 fw-bold" id="stageTitleBadge"></span>
                            <span class="badge bg-warning text-dark border border-dark fw-bold">
                                <i class="fas fa-cube me-1"></i> نمای ۳ بعدی سه‌رخ: به ترتیب حق تقدم روی خودروها/عابرین کلیک کنید!
                            </span>
                        </div>
                        <div class="fw-black mb-1" style="font-weight: 900; font-size: 1rem;" id="stageQuestionFa"></div>
                        <div class="text-muted small" dir="ltr" id="stageQuestionDe" style="display: none; text-align: left; font-weight: 700;"></div>
                    </div>
                </div>

                <!-- 2. 3D Interactive Three.js Intersection Stage (3/4 Elevated View) -->
                <div class="intersection-frame" id="priority3DStage"></div>

                <!-- 3. User Clicked Sequence Bar & Reset Action -->
                <div class="sequence-bar">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <span class="fw-black small" style="font-weight: 900;">
                            <i class="fas fa-route text-primary me-1"></i>
                            ترتیب حرکت انتخاب‌شده توسط شما:
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold border-2 border-dark" onclick="resetCurrentStageMoves()">
                            <i class="fas fa-undo me-1"></i> بازنشانی حرکت
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="userSequencePills"></div>
                </div>

                <!-- 4. Confirm / Check Answer Button ("نمایش پاسخ") -->
                <div class="text-center my-3" id="solveButtonWrapper">
                    <button type="button" class="btn-solve-game" onclick="checkUserPriorityAnswer()">
                        <i class="fas fa-check-double"></i>
                        <span>نمایش پاسخ و بررسی اولویت‌ها</span>
                    </button>
                </div>

                <!-- 5. Evaluation & Educational Explanation Panel -->
                <div class="feedback-panel" id="priorityFeedbackPanel">
                    <div class="overall-result-banner" id="priorityResultBanner"></div>

                    <div class="quest-board">
                        <div class="quest-board-inner">
                            <h6 class="fw-black mb-2" style="font-weight: 900;">
                                <i class="fas fa-list-ol text-primary me-1"></i> بررسی ترتیب اولویت‌های شما:
                            </h6>
                            <div id="priorityActorsBreakdown"></div>

                            <div class="p-3 rounded-4 mt-3" id="priorityExplanationBox" style="background: rgba(37, 99, 235, 0.1); border-right: 4px solid #2563eb;">
                                <div class="fw-bold text-primary small mb-1">
                                    <i class="fas fa-lightbulb me-1"></i> قانون حق تقدم و توضیح آموزشی:
                                </div>
                                <div id="priorityExplanationText" style="font-size: 0.92rem; line-height: 1.7;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3 mb-4">
                        <button type="button" class="btn-2d btn-2d-cyan px-3 py-2" onclick="if(scene3D) scene3D.playSimultaneousSolution()">
                            <i class="fas fa-play"></i>
                            <span>پخش حرکت صحیح و همزمان</span>
                        </button>
                        <button type="button" class="btn-repeat-q" onclick="repeatCurrentStage()">
                            <i class="fas fa-redo"></i>
                            <span>تکرار این تقاطع</span>
                        </button>
                        <button type="button" class="btn-next-q" onclick="goToNextStage()">
                            <span id="nextStageBtnText">تقاطع بعدی</span>
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Final Summary Screen -->
            <div class="quest-board text-center py-5 mt-3" id="prioritySummaryScreen" style="display: none;">
                <div class="quest-board-inner py-4">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 86px; height: 86px; background: #fde047; border: 4px solid #1e1b4b; box-shadow: 0 5px 0 #1e1b4b; color: #1e1b4b; font-size: 2.5rem;">
                            <i class="fas fa-trophy"></i>
                        </span>
                    </div>
                    <h4 style="font-weight: 900;" class="mb-2">پایان مراحل بازی حق تقدم‌ها!</h4>
                    <p class="fw-bold mb-4">
                        شما <strong id="sumWinCount" class="text-success">0</strong> تقاطع را کاملاً صحیح و
                        <strong id="sumLoseCount" class="text-danger">0</strong> تقاطع را با خطا پشت سر گذاشتید.
                    </p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn-2d btn-2d-yellow px-4 py-2" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i>
                            <span>شروع مجدد بازی</span>
                        </button>
                        <a href="index.php" class="btn-2d btn-2d-cyan px-4 py-2">
                            <i class="bx bx-home-alt"></i>
                            <span>بازگشت به داشبورد</span>
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/priority-3d-engine.js?v=2.1"></script>

    <?php if (!empty($scenarios)): ?>
    <script>
        const SCENARIOS = <?= json_encode($scenarios, JSON_UNESCAPED_UNICODE) ?>;

        const BRANCH_LABELS = {
            top: 'ضلع بالا',
            right: 'ضلع راست',
            bottom: 'ضلع پایین - من',
            left: 'ضلع چپ'
        };

        const INTERSECTION_BRANCHES = {
            cross4: ['top', 'right', 'bottom', 'left'],
            t_no_top: ['right', 'bottom', 'left'],
            t_no_bottom: ['top', 'right', 'left'],
            t_no_left: ['top', 'right', 'bottom'],
            t_no_right: ['top', 'bottom', 'left']
        };

        const VEHICLE_CATALOG = {
            car_yellow: { name: 'خودرو زرد' },
            car_green:  { name: 'خودرو سبز' },
            car_blue:   { name: 'خودرو آبی' },
            car_red:    { name: 'خودرو قرمز' },
            bus:        { name: 'اتوبوس زرد' },
            tram:       { name: 'تراموا' },
            truck:      { name: 'کامیون آبی' },
            moto:       { name: 'موتورسیکلت' },
            bike:       { name: 'دوچرخه' },
            scooter:    { name: 'اسکوتر برقی' },
            police:     { name: 'خودرو پلیس/امداد' }
        };

        const PEDESTRIAN_CATALOG = {
            pedestrian: { name: 'عابر پیاده' },
            ped_child:  { name: 'کودک در پیاده‌رو' },
            ped_bike:   { name: 'دوچرخه در گذرگاه' },
            ped_scooter:{ name: 'اسکوتر در پیاده‌رو' }
        };

        let currentStageIdx = 0;
        let currentScenarioState = null;
        let userClickedKeys = [];
        let stageEvaluated = null;
        let totalScore = 0;
        let winStagesCount = 0;
        let loseStagesCount = 0;
        let scene3D = null;

        function getStageActors() {
            if (!currentScenarioState || !currentScenarioState.branches) return [];
            const activeBranches = INTERSECTION_BRANCHES[currentScenarioState.intersectionType] || INTERSECTION_BRANCHES.cross4;
            const list = [];

            activeBranches.forEach(branch => {
                const b = currentScenarioState.branches[branch];
                if (!b) return;
                if (b.vehicle && b.vehicle.type) {
                    const meta = VEHICLE_CATALOG[b.vehicle.type] || VEHICLE_CATALOG.car_yellow;
                    list.push({
                        key: `vehicle_${branch}`,
                        branch: branch,
                        slotType: 'vehicle',
                        type: b.vehicle.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        direction: b.vehicle.direction || 'straight',
                        priority: parseInt(b.vehicle.priority || 1, 10)
                    });
                }
                if (currentScenarioState.hasRoundabout && b.ringVehicle && b.ringVehicle.type) {
                    const meta = VEHICLE_CATALOG[b.ringVehicle.type] || VEHICLE_CATALOG.car_yellow;
                    const ringLabels = { top: 'بالا', right: 'راست', bottom: 'پایین', left: 'چپ' };
                    list.push({
                        key: `ring_${branch}`,
                        branch: branch,
                        slotType: 'ring',
                        type: b.ringVehicle.type,
                        name: `${meta.name} (در میدان، قبل از خروج ${ringLabels[branch]})`,
                        direction: b.ringVehicle.direction === 'continue' ? 'continue' : 'exit',
                        priority: parseInt(b.ringVehicle.priority || 1, 10)
                    });
                }
                if (b.pedestrian && b.pedestrian.type) {
                    const meta = PEDESTRIAN_CATALOG[b.pedestrian.type] || PEDESTRIAN_CATALOG.pedestrian;
                    list.push({
                        key: `pedestrian_${branch}`,
                        branch: branch,
                        slotType: 'pedestrian',
                        type: b.pedestrian.type,
                        name: `${meta.name} (${BRANCH_LABELS[branch]})`,
                        direction: 'across',
                        priority: parseInt(b.pedestrian.priority || 1, 10)
                    });
                }
            });
            return list;
        }

        function loadStage(index) {
            const sc = SCENARIOS[index];
            if (!sc) return;

            userClickedKeys = [];
            stageEvaluated = null;

            currentScenarioState = sc.scenario_data || { intersectionType: sc.intersection_type || 'cross4', branches: {} };

            document.getElementById('currentStageLabel').textContent = (index + 1);
            const pct = Math.round(((index + 1) / SCENARIOS.length) * 100);
            document.getElementById('stageProgressBar').style.width = pct + '%';

            document.getElementById('stageTitleBadge').textContent = sc.title || `مرحله ${index + 1}`;
            document.getElementById('stageQuestionFa').textContent = sc.question_text || '';
            const deEl = document.getElementById('stageQuestionDe');
            if (sc.question_de && sc.question_de.trim()) {
                deEl.textContent = sc.question_de;
                deEl.style.display = 'block';
            } else {
                deEl.style.display = 'none';
            }

            document.getElementById('priorityFeedbackPanel').style.display = 'none';
            document.getElementById('solveButtonWrapper').style.display = 'block';

            renderUserSequencePills();
            if (scene3D) {
                scene3D.resetPositions();
                scene3D.renderScenario(currentScenarioState, userClickedKeys, stageEvaluated, false);
            }
        }

        function onUserClickRoadActor(actorKey) {
            if (stageEvaluated) return;

            const actors = getStageActors();
            const actor = actors.find(a => a.key === actorKey);
            if (!actor) return;

            if (!userClickedKeys.includes(actorKey)) {
                userClickedKeys.push(actorKey);
            }
            renderUserSequencePills();
            if (scene3D) {
                scene3D.animateActorMovement(actorKey, userClickedKeys);
            }
        }

        function resetCurrentStageMoves() {
            if (stageEvaluated) return;
            userClickedKeys = [];
            renderUserSequencePills();
            if (scene3D) {
                scene3D.resetPositions();
                scene3D.renderScenario(currentScenarioState, userClickedKeys, stageEvaluated, false);
            }
        }

        function renderUserSequencePills() {
            const container = document.getElementById('userSequencePills');
            const actors = getStageActors();

            if (userClickedKeys.length === 0) {
                container.innerHTML = `<span class="text-muted small">هنوز روی هیچ کاربری در تقاطع ۳ بعدی کلیک نکرده‌اید. روی کسی که اول حق تقدم دارد کلیک کنید!</span>`;
                return;
            }

            container.innerHTML = userClickedKeys.map((key, idx) => {
                const a = actors.find(x => x.key === key);
                return `<span class="step-order-chip"><span>${idx + 1}.</span> <span>${a ? a.name : key}</span></span>`;
            }).join(' <i class="fas fa-arrow-left text-muted small"></i> ');
        }

        function checkUserPriorityAnswer() {
            if (stageEvaluated) return;

            const actors = getStageActors();
            if (userClickedKeys.length < actors.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ترتیب کامل نیست!',
                    text: `لطفاً به ترتیب حق تقدم روی تمام ${actors.length} کاربر جاده در تقاطع کلیک کنید تا حرکت کنند، سپس روی نمایش پاسخ بزنید.`,
                    confirmButtonText: 'باشه!'
                });
                return;
            }

            const sortedByAdmin = [...actors].sort((a, b) => a.priority - b.priority);
            const detailsByKey = {};
            let allCorrect = true;
            const breakdownHTML = [];

            actors.forEach(actor => {
                const userClickIdx = userClickedKeys.indexOf(actor.key);
                const expectedPriorityAtStep = sortedByAdmin[userClickIdx] ? sortedByAdmin[userClickIdx].priority : actor.priority;
                const isOk = (actor.priority === expectedPriorityAtStep);

                detailsByKey[actor.key] = isOk;
                if (!isOk) allCorrect = false;

                const samePrioCount = actors.filter(x => x.priority === actor.priority).length;
                const prioDesc = samePrioCount > 1
                    ? `اولویت ${actor.priority} (عبور همزمان)`
                    : `اولویت ${actor.priority}`;

                const userNum = userClickIdx + 1;
                if (isOk) {
                    breakdownHTML.push(`
                        <div class="actor-eval-row eval-ok">
                            <span class="fw-bold"><i class="fas fa-check-circle text-success me-1"></i> ${actor.name}</span>
                            <span class="badge bg-success">عالی! ${prioDesc} صحیح است</span>
                        </div>
                    `);
                } else {
                    breakdownHTML.push(`
                        <div class="actor-eval-row eval-err">
                            <span class="fw-bold"><i class="fas fa-times-circle text-danger me-1"></i> ${actor.name}</span>
                            <span class="badge bg-danger">انتخاب شما: ${userNum} | صحیح: ${prioDesc}</span>
                        </div>
                    `);
                }
            });

            stageEvaluated = { isAllCorrect: allCorrect, detailsByKey: detailsByKey };

            if (allCorrect) {
                winStagesCount++;
                totalScore += 50;
                document.getElementById('hudScoreVal').textContent = totalScore;
            } else {
                loseStagesCount++;
            }

            // Re-render 3D scene so trajectory lines turn Green or Red, then play simultaneous movement!
            if (scene3D) {
                scene3D.renderScenario(currentScenarioState, userClickedKeys, stageEvaluated, false);
                const hasSimultaneous = actors.some(a => actors.filter(x => x.priority === a.priority).length > 1);
                if (hasSimultaneous) {
                    scene3D.playSimultaneousSolution();
                }
            }

            const banner = document.getElementById('priorityResultBanner');
            if (allCorrect) {
                banner.className = 'overall-result-banner result-win';
                banner.innerHTML = `
                    <i class="fas fa-trophy fa-2x"></i>
                    <div>
                        <div class="fs-6">آفرین! ترتیب حق تقدم این تقاطع را کاملاً درست تشخیص دادید (+۵۰ امتیاز)</div>
                        <div class="fw-normal small">تمام مسیرهای حرکت ۳ بعدی روی تقاطع سبز شدند.</div>
                    </div>
                `;
            } else {
                banner.className = 'overall-result-banner result-lose';
                banner.innerHTML = `
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <div class="fs-6">دقت کنید! در تشخیص ترتیب حق تقدم اشتباه داشتید.</div>
                        <div class="fw-normal small">مسیرهای قرمز روی نقشه ۳ بعدی نشان‌دهنده اولویت‌هایی هستند که اشتباه انتخاب کردید.</div>
                    </div>
                `;
            }

            document.getElementById('priorityActorsBreakdown').innerHTML = breakdownHTML.join('');
            const sc = SCENARIOS[currentStageIdx];
            document.getElementById('priorityExplanationText').textContent = (sc && sc.explanation) ? sc.explanation : 'توضیحی برای این تقاطع ثبت نشده است.';

            document.getElementById('nextStageBtnText').textContent = (currentStageIdx >= SCENARIOS.length - 1) ? 'مشاهده نتیجه پایانی' : 'تقاطع بعدی';

            document.getElementById('solveButtonWrapper').style.display = 'none';
            const fbPanel = document.getElementById('priorityFeedbackPanel');
            fbPanel.style.display = 'block';
            fbPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function repeatCurrentStage() {
            loadStage(currentStageIdx);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goToNextStage() {
            if (currentStageIdx < SCENARIOS.length - 1) {
                currentStageIdx++;
                loadStage(currentStageIdx);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                document.getElementById('activePriorityGameArea').style.display = 'none';
                document.getElementById('sumWinCount').textContent = winStagesCount;
                document.getElementById('sumLoseCount').textContent = loseStagesCount;
                document.getElementById('prioritySummaryScreen').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stageEl = document.getElementById('priority3DStage');
            if (stageEl && window.Priority3DScene) {
                scene3D = new window.Priority3DScene(stageEl, {
                    showDropZones: false,
                    onActorClick: function(actorKey) {
                        onUserClickRoadActor(actorKey);
                    }
                });
            }
            loadStage(0);
        });
    </script>
    <?php endif; ?>
</body>
</html>
